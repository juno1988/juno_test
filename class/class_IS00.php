<?
require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_lock.php";
require_once "class_ui.php";
require_once "class_category.php";
require_once "class_multicategory.php";


////////////////////////////////
// class name: class_IS00
// 
//
class class_IS00 extends class_top 
{
	function IS00()
	{
		global $template, $connect;

		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	
	function IS01()
	{
		global $template, $connect;

		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	
	function IS02()
	{
		global $template, $connect;

		if ( $_REQUEST["page"] )
		{
//			$stock_option = 1;
//			$result = class_C::get_product_supply_list( &$total_rows, $stock_option );
		}

		$realtime_stock_categories = $this->get_realtime_stock_categories();

		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}

	function IS03()
	{
		global $template, $connect;
		
		foreach($_REQUEST as $key => $val) $$key = $val;
		
        $par = array("template","action","product_id","id","start_date","end_date","query_type", "query_str");
        $link_url = $this->build_link_url3( $par );
        
        if ( $page )
            $result = $this->get_log_list( &$total_rows, $page, &$total_search_rows);


		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	
	function get_log_list(&$max_row, $page, &$max_rows2)
	{
	    
	    global $template, $connect;
	    
	    foreach($_REQUEST as $key => $val) $$key = $val;
	    
	    $sub_sql = "";
		
		if($start_date || $end_date)
		    $sub_sql .= " and a.crdate >= '$start_date 00:00:00' and a.crdate <= '$end_date 23:59:59' ";
		else
		    $sub_sql .= " and a.crdate >= date_format(curdate(), '%Y-%m-%d' ) and a.crdate <= date_format(curdate(), '%Y-%m-%d' ) ;";
		    
		if($query_str)
		    $sub_sql .= " and b.$query_type like '%$query_str%' ";
		
		
	    
	    $option = " group by a.product_id ";
	    
	    $order = " order by cnt desc ";
	    
	    $sql = "select a.*, b.name, count(*) as cnt
                from realtime_stock_log a inner join products b on a.product_id = b.product_id
                where 1=1
                $sub_sql
                $option";
                

        $result = mysql_query( $sql, $connect );
        
        
        while($data = mysql_fetch_assoc($result)){
            
            $max_rows2 += $data[cnt];
            
        }
        
        
        
        $result = mysql_query( $sql, $connect );
        
        $max_row = mysql_num_rows( $result );
	    
	    
	    if ( !$page ) $page = 1;
	        $starter = ($page-1) * _line_per_page50;
            
            
        $limit = " limit $starter, " . _line_per_page50;
        
        
        
        $query_final = $sql . $order . $limit;
        
        $result = mysql_query ( $query_final, $connect );
        
        return $result;

	}
	
	function IS04()
	{
		global $template, $connect;
		
		foreach($_REQUEST as $key => $val) $$key = $val;
		
		$par = array("template","action","product_id","id","start_date","end_date","query_type", "query_str");
        $link_url = $this->build_link_url3( $par );
	    
                
        $sql = "select *
                from realtime_stock_log
                where 1=1
                and product_id = '$id'";                
		        
        $result = mysql_query( $sql, $connect );
        
        $total_rows = mysql_num_rows($result);
        
        if ( !$page ) $page = 1;
            $starter = ($page-1) * _line_per_page50;
            
            
        $limit = " limit $starter, " . _line_per_page50;
        
        $query_final = $sql . $limit;
        
        
        $result = mysql_query ( $query_final, $connect );
        

		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	

	function get_realtime_stock_categories()
	{
		global $connect;

		$query = "select * from realtime_stock_category order by category";
        $result = mysql_query( $query, $connect );
        while( $list = mysql_fetch_assoc( $result ) )
        {
			$realtime_stock_categories[] = $list;
		}

		return $realtime_stock_categories;
	}
	
	function stockConfigList()
	{
        global $template, $connect;
        
        $code = "";
        $head = false;
        
        $sql = "select * 
                from realtime_stock_category
                order by category";

        $result = mysql_query($sql, $connect) or die(mysql_error());
        
        // 카테고리 순서대로 테이블 생성
        while($c_list = mysql_fetch_assoc($result)){

            
            $code .= "<tr><td class='tbl_border'>";
            $code .= "<table id=tbl_$c_list[category] name='tbl_$c_list[category]' class='tbl' border=0>";
            
            if(!$head){
                $code .= "<tr>";
                $code .= "<td class='w100 header' align=center>카테고리</td>";
                $code .= "<td class='w150 header' align=center>카테고리 설명</td>";
                $code .= "<td class='w150 header' align=center>품절기준</td>";
                $code .= "<td class='w400 header' align=center>품절상태</td>";
                $code .= "<td class='header w100' align='center'>수정</td>";
                $code .= "</tr>";
                
                $head = true;
            }
            
            $code .= "<tr>";
            $code .= "  <td class='w100' align='center' style=\"font-size: 15px; font-weight: bold;\"><input type=hidden name='category' value='$c_list[category]' >$c_list[category]</td>";
            $code .= "  <td class='w150' align='center'><input type=text name='name' value='$c_list[name]'  class='input_text_border'></td>";

            $code .= "  <td align='center' class='w150'><div style='width: 120px; text-align: left;'>";
            $code .= "<input type=radio name=type_$c_list[category] value=1 ";
            if($c_list[type] == 1) $code .= " checked";
            $code .= ">현재고<br/>";
            $code .= "<input type=radio name=type_$c_list[category] value=2 ";
            if($c_list[type] == 2) $code .= " checked";
            $code .= ">현재고 - 송장<br/>";
            $code .= "<input type=radio name=type_$c_list[category] value=3 ";
            if($c_list[type] == 3) $code .= " checked";
            $code .= ">현재고 - 접수<br/>";
            $code .= "<input type=radio name=type_$c_list[category] value=4 ";
            if($c_list[type] == 4) $code .= " checked";
            $code .= ">현재고 - 송장 - 접수<br/>";
            $code .= "</div></td>";
            $code .= "<td align='center' class='w400'>";
            $code .= "<input type=checkbox name=use_soldout_$c_list[category] ";
            if($c_list[use_soldout] == 1) $code .= " checked";
            $code .= ">품절상태&nbsp;&nbsp;";
            $code .= "<input type='text' name='soldout_text_$c_list[category]' value='$c_list[soldout_text]' class='input_text2' >";
            $code .="</td>";
            $code .= "  <td align='center' class='w100'><img src='./images/takeback_btn_del.gif' onclick='delCategory(this)'></td>";
            $code .= "</tr>";
            

            
            $sql = "select * 
                    from realtime_stock_detail
                    where category = '$c_list[category]'
                    order by seq";
                    
            $res = mysql_query($sql, $connect) or die(mysql_error());
            
            $num = 1;
                
            $code .= "<tr>";
            $code .= "  <td class='subHeader w100' align=center>번호</td>";
            $code .= "  <td class='subHeader w150' align=center>최소 재고 수</td>";
            $code .= "  <td class='subHeader w150' align=center>최대 재고 수</td>";
            $code .= "  <td class='subHeader w400' align=center>재고 상태</td>";
            $code .= "  <td class='subHeader w100' align=center><img src='./images/takeback_btn_add.gif' onclick='addRow(this)'></td>";
            $code .= "</tr>";   
            
            while($list = mysql_fetch_assoc($res)){
                
                $code .= "<tr>";
                $code .= "  <td class='rows w100'><input type=text name='seq' value='$num' class='readOnly' readonly class='input_text_border'></td>";
                $code .= "  <td class='rows w150' align='center'><input type='text' value='$list[qty_min]' name='qty_min' class='input_text_border w100 amount'></td>";
                $code .= "  <td class='rows w150' align='center'><input type='text' value='$list[qty_max]' name='qty_max' class='input_text_border w100 amount'></td>";
                $code .= "  <td class='rows' align='center'><input type='text' value='$list[msg]' name='msg' class='input_text_border w95p'></td>";
                $code .= "  <td class='rows w100' align=center><img src='./images/takeback_btn_del.gif' onclick='delRow(this)'></td>";
                $code .= "</tr>";
                
                $num++;
            }
            
            $code .= "</table>";
            $code .= "</td></tr><tr><td height=10></td></tr>";

        }
        
        
        if(mysql_num_rows($result) <= 0){
            
            $code .= "<tr><td class='tbl_border'>";
            $code .= "<table id=tbl_A name=tbl_A width=1000 class='tbl'>";
            $code .= "<tr>";
            $code .= "<td class='w100 header' align=center>카테고리</td>";
            $code .= "<td class='w150 header' align=center>카테고리 설명</td>";
            $code .= "<td class='w150 header' align=center>품절기준</td>";
            $code .= "<td class='w400 header' align=center>품절상태</td>";
            $code .= "<td class='header w100' align='center'>수정</td>";
            $code .= "</tr>";
            $code .= "<tr>";
            $code .= "  <td class='w100' align='center' style=\"font-size: 15px; font-weight: bold;\"><input type=hidden name='category' value='A' >A</td>";
            $code .= "  <td class='w150' align='center'><input type=text name='name' class='input_text_border'></td>";
            $code .= "  <td align='center' class='w150'><div style='width: 120px; text-align: left;'>";
            $code .= "<input type=radio name=type_A value=1 checked>현재고<br/>";
            $code .= "<input type=radio name=type_A value=2 >현재고 - 송장<br/>";
            $code .= "<input type=radio name=type_A value=3 >현재고 - 접수<br/>";
            $code .= "<input type=radio name=type_A value=4 >현재고 - 송장 - 접수<br/>";
            $code .= "</div></td>";
            $code .= "<td align='center' class='w400'>";
            $code .= "<input type='checkbox' name='use_soldout_A' checked='checked'>품절상태&nbsp;&nbsp;";
            $code .= "<input type='text' name='soldout_text_A' class='input_text2' >";
            $code .= "</td>";
            $code .= "  <td align='center' class='w100'><img src='./images/takeback_btn_del.gif' onclick='delCategory(this)'></td>";
            $code .= "</tr>";
            $code .= "  <td class='subHeader w100' align=center>번호</td>";
            $code .= "  <td class='subHeader w150' align=center>최소 재고 수</td>";
            $code .= "  <td class='subHeader w150' align=center>최대 재고 수</td>";
            $code .= "  <td class='subHeader w400' align=center>재고 상태</td>";
            $code .= "  <td class='subHeader w100' align=center><img src='./images/takeback_btn_add.gif' onclick='addRow(this)'></td>";
            $code .= "</tr>"; 
            $code .= "<tr>";
            $code .= "  <td class='rows w100'><input type=text name='seq' value='1' class='readOnly' readonly class='input_text_border'></td>";
            $code .= "  <td class='rows w150' align='center'><input type='text' name='qty_min' class='input_text_border w100 amount'></td>";
            $code .= "  <td class='rows w150' align='center'><input type='text' name='qty_max' class='input_text_border w100 amount'></td>";
            $code .= "  <td class='rows w400' align='center'><input type='text' name='msg' class='input_text_border w95p'></td>";
            $code .= "  <td class='rows w100' align=center><img src='./images/takeback_btn_del.gif' onclick='delRow(this)'></td>";
            $code .= "</tr>";
            $code .= "</table>";
            $code .= "</td></tr>";
        }
        
        echo $code;
	}
	
	function delStock()
	{
	
	    global $template, $connect;
	    
        foreach($_REQUEST as $key => $val) $$key = $val;
        
        print_r($_REQUEST);
        
        // 세부 설정 삭제
        $sql = "delete
                from realtime_stock_detail";
        echo $sql."\n";
        mysql_query($sql, $connect) or die(mysql_error());
        
        
        // 카테고리 삭제
        $sql = "delete
                from realtime_stock_category";
        echo $sql."\n";
        mysql_query($sql, $connect) or die(mysql_error());

	}
	
	function setCategory()
	{
        global $template, $connect;
	    
        foreach($_REQUEST as $key => $val) $$key = $val;
        
        foreach( $param as $a1 ){
            
            $check = true;
            
            foreach( $a1 as $v ){
                if( is_array ( $v ) ){
                    
                    $category = $v['category'];
                    $seq = $v['seq'];
                    $qty_min = $v['qty_min'];
                    $qty_max = $v['qty_max'];
                    $msg = $v['msg'];
                    
                    $sql = "insert into realtime_stock_detail
                            set category = '$category',
                                seq = '$seq',
                                qty_min = '$qty_min',
                                qty_max = '$qty_max',
                                msg = '$msg'";
        
                    echo $sql."\n";
                    mysql_query($sql, $connect);
                    
                }else{
                    
                    if($check){
                        $category = $a1['category'];
                        $name = $a1['name'];
                        $type = $a1['type'];
                        $use_soldout = $a1['use_soldout'];
                        $soldout_text = $a1['soldout_text'];
                        
                        
                        $sql = "insert into realtime_stock_category
                                set category = '$category',
                                    name = '$name',
                                    type = '$type',
                                    use_soldout = '$use_soldout',
                                    soldout_text = '$soldout_text'";
                    
                        echo $sql."\n";
                        mysql_query($sql, $connect);
                        
                        $check = false;
                    }
                       
                }
            }
            
        }
        
	}
	
	function delCategory()
	{
        global $template, $connect;
	    
        foreach($_REQUEST as $key => $val) $$key = $val;
        
        
        //-- 카테고리에 해당하는 스타일 삭제
        $sql = "delete
                from realtime_stock_style
                where category = '$category'";
                
        mysql_query($sql, $connect);
        
        
        //-- 카테고리에 해당하는 세부항목 삭제
        $sql = "delete
                from realtime_stock_detail
                where category = '$category'";
                
        mysql_query($sql, $connect);
        
        
        //-- 카테고리 삭제
        $sql = "delete
                from realtime_stock_category
                where category = '$category'";
                
        mysql_query($sql, $connect);
        
        
        // products에서 realtime_stock_category 필드 공란
        $sql = "update products
                set realtime_stock_category = ''
                where realtime_stock_category = '$category'";
                
        mysql_query($sql, $connect);
	}
	
	function categoryList()
	{
        global $template, $connect;
	    
        foreach($_REQUEST as $key => $val) $$key = $val;
        
        $sql = "select *
                from realtime_stock_category
                order by category";
                
        $result = mysql_query($sql, $connect);
        $code = "";
        
        while($list = mysql_fetch_assoc($result)){
            $code .= "<option value='$list[category]' ";
            if($list[category] == $category)
                $code .= " selected ";
            $code .= " >$list[category] - $list[name]</option>";
        }
        
        echo $code;
	}
	
	function saveStyle()
	{
        global $template, $connect;
	    
        foreach($_REQUEST as $key => $val) $$key = $val;
        
        echo "<pre>";
        print_r($_REQUEST);
        //exit;
        
        
        // 카테고리 테이블에 style번호 저장
        $sql = "update realtime_stock_category
                set style = '$style'
                where category = '$category'";
        
        //echo $sql;        
        mysql_query($sql, $connect);
        
        
        
        // 기존 style 삭제
        $sql = "delete
                from realtime_stock_style
                where category = '$category'";
        //echo $sql;                
                
        mysql_query($sql, $connect);
        
        
        // 15번 DB 연결
        $c15 = mysql_connect("66.232.146.171", "ezadmin", "pimz8282");
        mysql_select_db("ezadmin", $c15);
        
        $sql = "select name, value
                from sys_realtime_stock_style
                where style_no = $style";
                
        $result = mysql_query($sql, $c15) or die(mysql_error());
        
        
        while($list = mysql_fetch_assoc($result)){
            
            $sql = "insert into realtime_stock_style
                    set category = '$category',
                        name = '$list[name]',
                        value = '$list[value]'";
            
                        
            mysql_query($sql, $connect);
            
        }
	}
	
	function getStyle()
	{
        global $template, $connect;
	    
        foreach($_REQUEST as $key => $val) $$key = $val;
        
        $sql = "select distinct style from realtime_stock_category where category = '$category'";
        
        $list = mysql_fetch_assoc(mysql_query($sql, $connect));
        
        
        echo $list[style];
        
	}
	
	function search()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, $category,
               $stock_start, $stock_end, $bad_start, $bad_end, $notrans_day, 
			   $notrans_cnt, $stock_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, 
			   $inout_type, $is_stock, $stock_type;

        global $str_supply_code, $query_type, $query_str, $notrans_status, 
			   $products_sort, $except_soldout, $soldout_zero;

		global $realtime_stock_category;

        $total_rows = 0;
        $_arr = $this->get_list( &$total_rows );
        $_json = json_encode( $_arr );

        echo "
        <script language='javascript'>
        parent.disp_rows( $_json )
        </script>
        ";
    }	

    function get_list( &$total_rows, $page_all=0 )
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, $query_type, 
			   $query_str, $page, $category,
               $stock_start, $stock_end, $stock_type, $notrans_day, 
			   $notrans_cnt, $stock_status, $notrans_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, 
			   $inout_type, $is_stock, $soldout_zero;

        global $supply_tel, $supply_brand, $supply_options, $org_price, 
			   $enable_sale, $products_sort, $except_soldout;
        global $str_supply_code, $s_group_id, $str_category;

		global $realtime_stock_category;

        if ( $str_supply_code == "")
        {
            // 
            $str_supply_code = "";
            foreach( $supply_code as $_c )
            {
                $str_supply_code .= $str_supply_code ? "," : "";
                $str_supply_code .= $_c;
            }
        }

        //
        if ( $s_group_id != "")
        {
            $str_supply_code = $this->get_group_supply( $s_group_id );
        }
	
		// 본상품 제외 & 삭제상품 제외 & 재고관리 상품
        $query_products = "select product_id, name, supply_code, category, 
									realtime_stock_category, link_id, str_category
                             from products";

        $query_products .= " where is_represent=1 and is_delete=0";
		$query_products .= " and link_id != ''";	

        if( $str_supply_code )
            $query_products .= " and supply_code in ( $str_supply_code ) ";

        if( $query_str )
        {
            if( $query_type == 'product_id' )
                $query_products .= " and product_id = '$query_str' ";

            if( $query_type == 'name' )
				$query_products .= " and replace(name,' ','') like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), trim($query_str)) . "%' ";

            if( $query_type == 'options' )
                $query_products .= " and options like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), trim($query_str)) . "%' ";

            if( $query_type == 'name_options' )
                $query_products .= " and replace(concat(name, options),' ','') like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), trim($query_str)) . "%' ";

            if( $query_type == 'barcode')
                $query_products .= " and barcode = '$query_str' ";

            if( $query_type == 'supply' )
            {
                $supply_arr = array();
                $query_supply = "select code from userinfo where level=0 and name like '%".str_replace(array('%','_'),array('\\%','\\_'),$query_str)."%' ";
                $result_supply = mysql_query($query_supply, $connect);
                while( $data_supply = mysql_fetch_assoc($result_supply) )
                    $supply_arr[] = $data_supply[code];

                $query_products .= " and supply_code in (".implode(",", $supply_arr).") ";
            }

            if( $query_type == 'brand' )
                $query_products .= " and brand like '%" . str_replace(" ", "%", trim($query_str)) . "%' ";

            if( $query_type == 'supply_options' )
                $query_products .= " and supply_options like '%" . str_replace(" ", "%", trim($query_str)) . "%' ";

            if( $query_type == 'location' )
                $query_products .= " and location = '$query_str' ";

            if( $query_type == 'origin' )
                $query_products .= " and origin like '%$query_str%' ";

            if( $query_type == 'product_memo' )
                $query_products .= " and memo like '%$query_str%' ";

            if( $query_type == 'link_id' )
                $query_products .= " and link_id like '%$query_str%' ";

            if( $query_type == 'location' )
                $query_products .= " and location like '%$query_str%' ";

            if( $query_type == 'reg_date' )
            {
                $query_str_arr = explode("~", $query_str);
                if(is_null($query_str_arr[1]))
                {
                    // yyyy-mm-dd
                    $format_arr = explode("-", $query_str_arr[0]);
                    if($format_arr[0] && $format_arr[1] && $format_arr[2])
                    {
                        $s_date = date("Y-m-d",strtotime($query_str_arr[0]));
                        $e_date = date("Y-m-d",strtotime($query_str_arr[0]));
                    }
                    else
                        $date_check = false;
                }
                else if($query_str_arr[0]=="")
                {
                    // ~yyyy-mm-dd
                    $format_arr = explode("-", $query_str_arr[1]);
                    if($format_arr[0] && $format_arr[1] && $format_arr[2])
                    {
                        $s_date = "0000-00-00";
                        $e_date = date("Y-m-d",strtotime($query_str_arr[1]));
                    }
                    else
                        $date_check = false;
                }
                else if($query_str_arr[1]=="")
                {
                    // yyyy-mm-dd~
                    $format_arr = explode("-", $query_str_arr[0]);
                    if($format_arr[0] && $format_arr[1] && $format_arr[2])
                    {
                        $s_date = date("Y-m-d",strtotime($query_str_arr[0]));
                        $e_date = "2050-12-31";
                    }
                    else
                        $date_check = false;
                }
                else if($query_str_arr[0] && $query_str_arr[1])
                {
                    // yyyy-mm-dd ~ yyyy-mm-dd
                    $format_arr_1 = explode("-", $query_str_arr[0]);
                    $format_arr_2 = explode("-", $query_str_arr[1]);
                    if($format_arr_1[0] && $format_arr_1[1] && $format_arr_1[2] && $format_arr_2[0] && $format_arr_2[1] && $format_arr_2[2])
                    {
                        $s_date = date("Y-m-d",strtotime($query_str_arr[0]));
                        $e_date = date("Y-m-d",strtotime($query_str_arr[1]));
                    }
                    else
                        $date_check = false;

                }
                if((is_null($s_date) && is_null($e_date)))
                {
                    echo '<script> alert("검색어 입력이 잘못되었습니다.\\n아래 형식 중 하나의 방법으로 입력하시기 바랍니다.\\n\\n입력일 당일 : 2014-03-10\\n입력일 이후 : 2014-03-10~\\n입력일 이전 : ~2014-03-10\\
n입력일 기간 : 2014-03-10~2014-03-10");</script>';
                    $s_date = $e_date = date("Y-m-d");
                    $query_str = $s_date."~".$e_date;
                }
                $query_products .= " and reg_date >= '$s_date 00:00:00'
                                     and reg_date <= '$e_date 23:59:59'";
            }
            if( $query_type == 'sale_stop_date' )
            {
                $query_str_arr = explode("~", $query_str);
                if(is_null($query_str_arr[1]))
                {
                    // yyyy-mm-dd
                    $format_arr = explode("-", $query_str_arr[0]);
                    if($format_arr[0] && $format_arr[1] && $format_arr[2])
                    {
                        $s_date = date("Y-m-d",strtotime($query_str_arr[0]));
                        $e_date = date("Y-m-d",strtotime($query_str_arr[0]));
                    }
                    else
                        $date_check = false;
                }
                else if($query_str_arr[0]=="")
                {
                    // ~yyyy-mm-dd
                    $format_arr = explode("-", $query_str_arr[1]);
                    if($format_arr[0] && $format_arr[1] && $format_arr[2])
                    {
                        $s_date = "0000-00-00";
                        $e_date = date("Y-m-d",strtotime($query_str_arr[1]));
                    }
                    else
                        $date_check = false;
                }

                else if($query_str_arr[1]=="")
                {
                    // yyyy-mm-dd~
                    $format_arr = explode("-", $query_str_arr[0]);
                    if($format_arr[0] && $format_arr[1] && $format_arr[2])
                    {
                        $s_date = date("Y-m-d",strtotime($query_str_arr[0]));
                        $e_date = "2050-12-31";
                    }
                    else
                        $date_check = false;
                }
                else if($query_str_arr[0] && $query_str_arr[1])
                {
                    // yyyy-mm-dd ~ yyyy-mm-dd
                    $format_arr_1 = explode("-", $query_str_arr[0]);
                    $format_arr_2 = explode("-", $query_str_arr[1]);
                    if($format_arr_1[0] && $format_arr_1[1] && $format_arr_1[2] && $format_arr_2[0] && $format_arr_2[1] && $format_arr_2[2])
                    {
                        $s_date = date("Y-m-d",strtotime($query_str_arr[0]));
                        $e_date = date("Y-m-d",strtotime($query_str_arr[1]));
                    }
                    else
                        $date_check = false;

                }
                if((is_null($s_date) && is_null($e_date)))
                {
                    echo '<script> alert("검색어 입력이 잘못되었습니다.\\n아래 형식 중 하나의 방법으로 입력하시기 바랍니다.\\n\\n입력일 당일 : 2014-03-10\\n입력일 이후 : 2014-03-10~\\n입력일 이전 : ~2014-03-10\\
n입력일 기간 : 2014-03-10~2014-03-10");</script>';
                    $s_date = $e_date = date("Y-m-d");
                    $query_str = $s_date."~".$e_date;
                }
                $query_products .= " and sale_stop_date >= '$s_date 00:00:00'
                                     and sale_stop_date <= '$e_date 23:59:59'";
            }

        }

        if( $query_type == 'stock_alarm1' )
            $query_products .= " and stock_alarm1 > 0 ";

        if( $query_type == 'stock_alarm2' )
            $query_products .= " and stock_alarm2 > 0 ";

        if( $category )
            $query_products .= " and category = '$category' ";

		$query_products .= " and realtime_stock_category = '$realtime_stock_category' ";

        //        
        // for multicategory
        // 2012.1.29 - jk str_category => m_category1, m_category2, m_category3로 구조 변경
        global $m_sub_category_1,$m_sub_category_2,$m_sub_category_3;
        $arr_search_id = class_multicategory::get_search_id($m_sub_category_1,$m_sub_category_2,$m_sub_category_3);
        if ( $arr_search_id[$m_sub_category_1] )
            $query_products .= " and m_category1=" . $arr_search_id[$m_sub_category_1];

        if ( $arr_search_id[$m_sub_category_2] )
            $query_products .= " and m_category2=" . $arr_search_id[$m_sub_category_2];

        if ( $arr_search_id[$m_sub_category_3] )
            $query_products .= " and m_category3=" . $arr_search_id[$m_sub_category_3];

        // 품절
        if( $except_soldout == 1 )
            $query_products .= " and enable_sale=1 ";
        else if( $except_soldout == 2 )
            $query_products .= " and enable_sale=0 ";

        $result = mysql_query( $query_products, $connect );
        $total_rows = mysql_num_rows($result);

		// all pages 
		if ( $page_all != "1" )
		{
	        $page  = $page ? $page : 1;
    	    $limit = 30;
	        $start = ($page - 1) * $limit;
    	    $condition_page = " limit $start, $limit";
		}

        $result = mysql_query( $query_products . $condition_page, $connect );

		$_arr = array();
        $_arr['total'] = $total_rows;

        while ( $data = mysql_fetch_array( $result ) )
        {
			$data["supply_name"] = class_supply::get_name( $data["supply_code"] );

			$category_name = "-";
			if ( $_SESSION[MULTI_CATEGORY] )
				$category_name = class_multicategory::get_category_str($data["str_category"]);
			else 
				$category_name = class_category::get_category_name($data["category"]);
			$data["category_name"] = $category_name;

			$_arr['list'][$start] = $data;

			$start++;
		}

		$query = "select category, name from realtime_stock_category order by category";
		$result = mysql_query( $query, $connect );
		while ( $list = mysql_fetch_assoc( $result ) )
		{
			$realtime_stock_categories[] = $list;
		}

		$_arr["realtime_stock_categories"] = $realtime_stock_categories;

		return $_arr;
	}

	function change_realtime_stock_category()
	{
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, $query_type, 
			   $query_str, $page, $category,
               $stock_start, $stock_end, $stock_type, $notrans_day, 
			   $notrans_cnt, $stock_status, $notrans_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, 
			   $inout_type, $is_stock, $soldout_zero;

        global $supply_tel, $supply_brand, $supply_options, $org_price, 
			   $enable_sale, $products_sort, $except_soldout;
        global $str_supply_code, $s_group_id, $str_category;

		global $realtime_stock_category, $new_realtime_stock_category;
		global $product_ids;

		$product_ids = stripslashes($product_ids);

		$query = "update products set 
					realtime_stock_category = '$new_realtime_stock_category' 
				where product_id in ( $product_ids ) ";

		mysql_query( $query, $connect );

        $total_rows = 0;
        $_arr = $this->get_list( &$total_rows );
        $_json = json_encode( $_arr );

        echo "
        <script language='javascript'>
        parent.disp_rows( $_json )
        </script>
        ";
	}

	function change_realtime_stock_category_all()
	{
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, $query_type, 
			   $query_str, $page, $category,
               $stock_start, $stock_end, $stock_type, $notrans_day, 
			   $notrans_cnt, $stock_status, $notrans_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, 
			   $inout_type, $is_stock, $soldout_zero;

        global $supply_tel, $supply_brand, $supply_options, $org_price, 
			   $enable_sale, $products_sort, $except_soldout;
        global $str_supply_code, $s_group_id, $str_category;

		global $realtime_stock_category, $new_realtime_stock_category;
	
        $total_rows = 0;

        $_arr = $this->get_list( &$total_rows, "1" );

		foreach( $_arr["list"] as $key => $val )
		{
			$product_id = $val["product_id"];
			if ( !$product_ids )
				$product_ids = "'" . $product_id . "'";
			else
				$product_ids .= ",'" . $product_id . "'";
		}

		$query = "update products set 
					realtime_stock_category = '$new_realtime_stock_category' 
				where product_id in ( $product_ids ) ";
		mysql_query( $query, $connect );

		$_json["total"] = 0;

        echo "
        <script language='javascript'>
        parent.disp_rows( $_json );
        </script>
        ";
	}
}

