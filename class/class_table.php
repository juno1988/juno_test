<?
class class_table
{
    //*****************************
    // 조회 필드 읽기
    //*****************************
    function get_setting($field_set = 0)
    {
        global $connect, $sys_connect, $page_code;
        
        $f = array();

        // 원가조회불가
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) !== FALSE )
            $enable_org_price = false;
        else 
            $enable_org_price = true;
            
        // 조회
        $query = "select * from field_set_detail where page_code='$page_code' order by chk DESC, order_no";
        $result = mysql_query($query, $connect);

        // 화면 최초 오픈시 자료 없을 경우, 또는 조회 필드 설정 팝업인 경우 시스템 설정으로 세팅.
        if( !mysql_num_rows($result) || $field_set )
        {
            $query = "select * from sys_field_set_detail where page_code = '$page_code'";
            $result = mysql_query($query, $sys_connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $query = "insert field_set_detail
                             set page_code  = '$data[page_code]',
                                 order_no   = '$data[order_no]',
                                 field_id   = '$data[field_id]',
                                 field_name = '$data[field_name]',
                                 chk        = '$data[chk]',
                                 header     = '$data[header]',
                                 sort       = '$data[sort]',
                                 org_info   = '$data[org_info]',
                                 data_type  = '$data[data_type]',
                                 tag        = '$data[tag]',
                                 width      = '$data[width]',
                                 is_num     = '$data[is_num]',
                                 align      = '$data[align]',
                                 click      = '$data[click]',
                                 dblclick   = '$data[dblclick]',
                                 use_sum    = '$data[use_sum]'";
                mysql_query($query, $connect);
            }
            
            $query = "select * from field_set_detail where page_code='$page_code' order by chk DESC, order_no";
            $result = mysql_query($query, $connect);
        }

        $i = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            // 원가조회불가(필드 설정 팝업이 아니고, 사용자 권한에 원가조회불가 설정일 경우)
            if( !$field_set && !$enable_org_price && $data[org_info] )  continue;

            $f[$i++] = array(
                "field_id"   => $data[field_id],
                "field_name" => $data[field_name],
                "chk"        => $data[chk],
                "header"     => $data[header],
                "sort"       => $data[sort],
                "data_type"  => $data[data_type],
                "tag"        => $data[tag],
                "width"      => $data[width],
                "is_num"     => $data[is_num],
                "use_sum"    => $data[use_sum],
                "align"      => $data[align],
                "click"      => $data[click],
                "dblclick"   => $data[dblclick]
            );
        }
        
        return $f;
    }

    //*****************************
    // INPUT 조회 필드 읽기
    //   -- 페이지에서 변경내용저장, 변경내용리셋 기능 사용시
    //*****************************
    function get_setting_input()
    {
        global $connect, $page_code;
        
        $val = array();

        $field_list = "";
        // 조회
        $query = "select field_id from field_set_detail where page_code='$page_code' and tag='input' and chk=1";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $field_list .= $data[field_id] . ",";

        $val["field_list"] = substr($field_list, 0, -1);

        echo json_encode($val);
    }

    //*****************************
    // 조회 필드 설정
    //*****************************
    function save_setting()
    {
        global $connect, $page_code, $setting;
        
        // setting 파싱
        foreach( explode(",", $setting) as $set_val )
        {
            list($order_no, $chk, $field_id, $field_name, $header, $sort) = explode(":", $set_val);

            $field_name = stripslashes($field_name);
            $field_name = urldecode($field_name);
            $field_name = addslashes($field_name);

            $header = stripslashes($header);
            $header = urldecode($header);
            $header = addslashes($header);
            
            $query = "update field_set_detail
                         set order_no   = '$order_no',
                             field_name = '$field_name',
                             chk        = '$chk',
                             header     = '$header',
                             sort       = '$sort'
                       where page_code  = '$page_code' and
                             field_id   = '$field_id'";
            mysql_query($query, $connect);
        }
    }
    
    //*****************************
    // show td
    //*****************************
    function show_td($par)
    {
        $val           = $par[val];          
        $field         = $par[field];        
        $tag           = $par[tag];          
        $width         = $par[width];        
        $is_num        = $par[is_num];       
        $align         = $par[align];        
        $click_func    = $par[click_func];   
        $dblclick_func = $par[dblclick_func];
        $style         = $par[style];
        $param		   = $par[param];
        $rowspan       = "1";
        
        if(isset($par['rowspan']))
        	$rowspan = $par['rowspan'];
        
        if( $is_num )
            $data = number_format($val);
        // a 태그 이면서 값이 없으면 클릭을 할수 있게 하기 위해 &nbsp;
        else if( ($tag == "a")&& !$val )
            $data = "&nbsp;";
        else if( $tag == "img" )
            list($data, $_temp) = explode("|", $val);
        else if( $tag == "raw" )
            $data = $val;
        else
            $data = htmlspecialchars($val, ENT_QUOTES);

        if( $click_func )
        {
        	if( $param )
            	$click_func = "javascript:$click_func($param)";
            else
            	$click_func = "javascript:$click_func(this)";
        }

        if( $dblclick_func )
        {
        	if( $param )
            	$dblclick_func = "javascript:$dblclick_func($param)";
            else
            	$dblclick_func = "javascript:$dblclick_func(this)";
        }

        // 링크
        if( ($tag == "a" || $tag == "img") && ($click_func || $dblclick_func) )
            $data = "<a class=atd href='#' onclick='$click_func' ondblclick='$dblclick_func'>$data</a>";
        // 버튼2
        else if( ($tag == "button2" ) && ($click_func || $dblclick_func) )
            $data = "<a class=btn_premium2 href='$click_func'>$val</a>";
        // 버튼4
        else if( ($tag == "button4") && ($click_func || $dblclick_func) )
            $data = "<a class=btn_premium4 href='$click_func'>$val</a>";
        // 버튼6
        else if( ($tag == "button6") && ($click_func || $dblclick_func) )
            $data = "<a class=btn_premium6 href='$click_func'>$val</a>";
        // 입력
        else if( $tag == "input" )
        {
            // 정수만 입력가능 class
            global $template;
            $num_int_str = "";
            if( $template == 'IM10' && $field == "stockin_qty" )
                $num_int_str = "num_int";
                
            $data = "<input type='text' class='input22 $align $num_int_str' style='width:{$width}px' onkeyup='javascript:search_keyup(this,\"$field\")' value='" . addslashes($data) . "'></input>";
        }
        // 체크박스
        else if( $tag == "check" )
            $data = "<input type='checkbox' class='center' checked></input>";
        // raw
        else if( $tag == "raw" )
            // 그대로 표시
            $data;
        // span
        else
            $data = "<span>$data</span>";
        
        if( $style )
            $style = "style=$style";
        else
            $style = "";

        // 미배송 상태
        
        if( _DOMAIN_ == 'hanstyle' && ($field == 'product_name' || $field == 'options' || $field == 'supply_product_name') )
            $str = "<td class='$field $align' nowrap width=250 $style>$data</td>";
        else if($rowspan)
            $str = "<td class='$field $align' rowspan=$rowspan nowrap $style>$data</td>";
        return $str;
    }
    
    //*****************************
    // print xls
    //*****************************
    function print_xls($d_val, $is_num, &$sheet, $col, $row)
    {
//		$cell = $sheet->getCellByColumnAndRow($col, $row);
//        if( $is_num )
//        {
//            
//            $cell->setValueExplicit($d_val, PHPExcel_Cell_DataType::TYPE_NUMERIC);
//            $cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat()->setFormatCode('#,##0');
//        }
//        else
//        {
//            $sheet->getCellByColumnAndRow($col, $row)->setValueExplicit($d_val, PHPExcel_Cell_DataType::TYPE_STRING);
//            $cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat()->setFormatCode('#,##0');
//        }
        
         if( $is_num )
        {
            $cell = $sheet->getCellByColumnAndRow($col, $row);
            $cell->setValueExplicit($d_val, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat()->setFormatCode('#,##0');
        }
        else
            $sheet->getCellByColumnAndRow($col, $row)->setValueExplicit($d_val, PHPExcel_Cell_DataType::TYPE_STRING);

    }
    
    //*****************************
    // 미배송 수량 구하기
    //*****************************
    function get_not_trans_qty($product_id, $start_date, $end_date)
    {
        global $connect;
        
        $not_trans = array(
            "status1" => 0,
            "status7" => 0
        );
        
        if( !$start_date )
        {
            $end_date = date("Y-m-d");
            $start_date = date("Y-m-d", strtotime("-1 year"));
        }
        
        $query = "select a.status a_status,
                         sum(b.qty) sum_b_qty
                    from orders a, order_products b
                   where a.seq = b.order_seq and
                         a.collect_date>='$start_date' and
                         a.collect_date<='$end_date' and
                         a.status in (1,7) and 
                         b.product_id = '$product_id' and 
                         b.order_cs not in (1,2,3,4) 
                   group by a.status";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data[a_status] == 1 )
                $not_trans[status1] = $data[sum_b_qty];
            else
                $not_trans[status7] = $data[sum_b_qty];
        }
        
        return $not_trans;
    }

    //*****************************
    // 필드 데이터 공급처 정보 생성
    //*****************************
    function get_supply_arr_data($field_id, $supply_info, $supply_code)
    {
        $val = "";
        // 공급처코드
        if( $field_id == "supply_code" ) 
            $val = ($supply_code ? $supply_code : "");
        // 공급처그룹
        else if( $field_id == "supply_group" ) 
            $val = $supply_info[$supply_code][group_name];
        // 공급처명
        else if( $field_id == "supply_name" ) 
            $val = $supply_info[$supply_code][supply_name];
        // 공급처연락처
        else if( $field_id == "supply_tel" ) 
            $val = $supply_info[$supply_code][tel] . " / " . $supply_info[$supply_code][mobile];
        // 공급처주소
        else if( $field_id == "supply_address" ) 
            $val = $supply_info[$supply_code][address1] . " " . $supply_info[$supply_code][address2];
        // 공급처이메일
        else if( $field_id == "supply_email" ) 
            $val = $supply_info[$supply_code][email];
        // 공급처담당자
        else if( $field_id == "supply_md" ) 
            $val = $supply_info[$supply_code][md];
        // 공급처MD
        else if( $field_id == "supply_admin" ) 
            $val = $supply_info[$supply_code][admin];
        // 공급처계좌
        else if( $field_id == "supply_account" ) 
            $val = $supply_info[$supply_code][account_number];
        // 공급처 어드민 MD
    	else if( $field_id == "supply_ez_md" ) 
            $val = $supply_info[$supply_code][ez_md];
        // 공급처 어드민담당자
        else if( $field_id == "supply_ez_admin" ) 
            $val = $supply_info[$supply_code][ez_admin];
    	// 공급처 어드민담당자
        else if( $field_id == "supply_group_name" ) 
            $val = $supply_info[$supply_code][group_name];
        return $val;
    }

    //*****************************
    // 변경된 공급처 정보
    //*****************************
    function get_supply_new_data($supply_code)
    {
        global $connect;
        
        $val = array();

        $query = "select a.code a_code,
                         b.name b_name,
                         a.name a_name,
                         a.tel a_tel,
                         a.mobile a_mobile,
                         a.address1 a_address1,
                         a.address2 a_address2,
                         a.email a_email,
                         a.md a_md,
                         a.account_number a_account_number
                    from userinfo a left outer join supply_group b on a.group_id = b.group_id
                   where a.code = '$supply_code' ";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $val["supply_code"   ] = $data[a_code];
        $val["supply_group"  ] = $data[b_name];
        $val["supply_name"   ] = $data[a_name];
        $val["supply_tel"    ] = $data[a_tel] . " / " . $data[a_mobile];
        $val["supply_address"] = $data[a_address1] . " " . $data[a_address2];
        $val["supply_email"  ] = $data[a_email];
        $val["supply_md"     ] = $data[a_md];
        $val["supply_account"] = $data[a_account_number];
        
        return json_encode($val);
    }

    //*****************************
    // 공급처 그룹 정보
    //*****************************
    function get_supplygroup_data()
    {
        global $connect;
        
        $val = array();
        
        $query = "select a.code a_code, 
                         b.name b_name 
                    from userinfo a,
                         supply_group b
                   where a.group_id = b.group_id";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $val[$data[a_code]] = $data[b_name];

        echo json_encode($val);
    }

    //*****************************
    // 변경된 상품 정보
    //*****************************
    function get_product_new_data($product_id)
    {
        global $connect;
        
        $val = array();

        $query = "select org_id from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $p_list = array();
        $query_opt = "select product_id 
                        from products 
                       where org_id in ('$product_id'" . ($data[org_id] ? ",'$data[org_id]'" : "") . ") or
                             product_id in ('$product_id'" . ($data[org_id] ? ",'$data[org_id]'" : "") . ") ";
        $result_opt = mysql_query($query_opt, $connect);
        while( $data_opt = mysql_fetch_assoc($result_opt) )
            $p_list[] .= "'" . $data_opt[product_id] . "'";

        $p_str = implode(",", $p_list);

        $query = "select product_id,
                         barcode,
                         location,
                         name,
                         options,
                         memo,
                         org_price,
                         supply_price,
                         shop_price,
                         brand,
                         supply_options
                    from products
                   where product_id in ($p_str)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $val[$data[product_id]] = array(
                "barcode"             => $data[barcode],
                "location"            => $data[location],
                "name_options"        => $data[name] . " " . $data[options],
                "options"             => $data[options],
                "org_price"           => $data[org_price],
                "product_memo"        => $data[memo],
                "product_name"        => $data[name],
                "shop_price"          => $data[shop_price],
                "supply_options"      => $data[supply_options],
                "supply_price"        => $data[supply_price],
                "supply_product_name" => $data[brand]
            );
        }
        
        return json_encode($val);
    }

    //*****************************
    // 상품의 공급처 변경 정보
    //*****************************
    function get_change_supply_data($product_id)
    {
        global $connect;
        
        $val = array();

        $query = "select org_id, supply_code from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        // 공급처코드
        $supply_code = $data[supply_code];
        
        $p_list = array();
        $query_opt = "select product_id 
                        from products 
                       where org_id in ('$product_id'" . ($data[org_id] ? ",'$data[org_id]'" : "") . ") or
                             product_id in ('$product_id'" . ($data[org_id] ? ",'$data[org_id]'" : "") . ") ";
        $result_opt = mysql_query($query_opt, $connect);
        while( $data_opt = mysql_fetch_assoc($result_opt) )
            $p_list[] = $data_opt[product_id];

        $query = "select a.code a_code,
                         b.name b_name,
                         a.name a_name,
                         a.tel a_tel,
                         a.mobile a_mobile,
                         a.address1 a_address1,
                         a.address2 a_address2,
                         a.email a_email
                    from userinfo a left outer join supply_group b on a.group_id = b.group_id
                   where a.code = '$supply_code' ";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $val["product_str"   ] = $p_list;
        $val["supply_code"   ] = $data[a_code];
        $val["supply_group"  ] = $data[b_name];
        $val["supply_name"   ] = $data[a_name];
        $val["supply_tel"    ] = $data[a_tel] . " / " . $data[a_mobile];
        $val["supply_address"] = $data[a_address1] . " " . $data[a_address2];
        $val["supply_email"  ] = $data[a_email];

        return json_encode($val);
    }
}
?>
