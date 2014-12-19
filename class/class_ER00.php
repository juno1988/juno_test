<?
require_once "class_top.php";
require_once "class_shop.php";
include_once "lib/lib_common.php";
include_once "jqSuite/jq-config.php";
include_once "jqSuite/php/jqGrid.php";
include_once "jqSuite/php/jqGridArray.php";

class class_ER00 extends class_top
{
    //////////////////////////////////////
    function ER00()
    {
        global $connect, $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function ER01()
    {
        global $connect, $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    function ER02()
    {
        global $connect, $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function ER03()
    {
        global $connect, $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


	function leftbox()
	{
		global $connect;
        foreach ($_REQUEST as $key=>$value) $$key = trim($value);


		//-----------------------------------------------
		$today = date("Y-m-d");

		$sql = "select distinct recv_mobile, max(seq) max_seq, max(crdate) crdate, count(*) cnt
				  from sms_msg_history 
				 where date(crdate) = '$today'
				   and msg_type = 'RX'
				 group by recv_mobile";
		$result = mysql_query($sql, $connect) or die(mysql_error());

		$data_arr = array();
		$j = 0;

		$strHTML .= "";
		while ($list = mysql_fetch_assoc($result))
		{
			$sql = "select * from sms_msg_history
					 where recv_mobile = '$list[recv_mobile]'
					   and seq = '$list[max_seq]'";
			$list2 = mysql_fetch_assoc(mysql_query($sql, $connect));

			$type = ($list2[msg_type] == "TX") ? "발신" : "<font color=red><b>수신</b></font>";
			$status = ($list2[msg_type] == "RX" && $list2[status] == 0) ? "읽지않음" : "읽음";

			$work = "<a href=\"javascript:chat_window('$send_mobile', '$list[recv_mobile]', '$list2[key_string]');\"><img src=/images15/btn_chat_small.gif align=absmiddle></a>";
			if ($list2[key_string]) {
				$work .= " <a href=\"javascript:cs_window('$list[recv_mobile]', '$list2[key_string]');\"><img src=/images15/btn_cs_small.gif align=absmiddle></a>";
			}

			$list2[msg] = str_replace("", "", $list2[msg]);
			$list2[msg] = str_replace("\n", "| ", $list2[msg]);

			$done = ($list2[is_done] == 0) ? "<a href=\"javascript:set_done('$list[recv_mobile]', '$list2[seq]');\"><b><font color=red>미처리</font></b></a>" : "<a href=\"javascript:set_undone('$list[recv_mobile]', '$list2[seq]');\"><font color=#666666>완료</font></a>";

			if (!$list2[category]) {
				$category = " <a href=\"javascript:ER03('$list[recv_mobile]');\"><img src='/images2/btn_move_folder.gif' align=absmiddle></a>";
			} else {
				$category = $list2[category];
			}

			list($prefix, $null) = explode("-", $list2[shop_name]);
			$shop_name = ($list2[shop_name]) ? $prefix : "";

			$j++;

			$phone = make_phone_num($list[recv_mobile]);
			$crdate = substr($list2[crdate],11,8);
			$msg = cutstr2($list2[msg], 200);

			$strHTML .= "<div class='item' id='item_${phone}'>";
			$strHTML .= "<table>
						   <tr>
							  <td rowspan=2 class=face><img src='/images/ezsms/chat_face.gif'></td>
							  <td class=phone>$phone</td>
							  <td class=crdate>$crdate</td>
						   </tr>
						   <tr>
							  <td colspan=2 class=msg>$msg</td>
						   </tr>
						</table>
			";
			$strHTML .= "</div>";

		}

		echo $strHTML;
	}

	function orderinfo()
	{
		global $connect;

		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$strHTML = "<table cellspacing=0 cellpadding=0 class=tbl_order1>";

		$sql = "select * from orders where recv_mobile = '$mobile' order by collect_date desc";
		$result = mysql_query($sql, $connect) or die(mysql_error());
		$rows = mysql_num_rows($result);
		while ($list = mysql_fetch_assoc($result))
		{
			$shop_name = class_shop::get_shop_name($list[shop_id]);
			$strHTML .= "<tr>
						<th height=28>발주일</td>
						<td align=center>$list[collect_date]</td>
						<th>판매처</td>
						<td align=center>$shop_name</td>
						<th>수령자</td>
						<td align=center>$list[recv_name]</td>
						<th>CS</td>
						<td align=center><a href=#><img src='/images15/btn_cs_small.gif' align=absmiddle></a></td>
					  </tr>";
			$sql = "select a.*, b.name as product_name, b.options as product_options 
					  from order_products a, products b
					 where a.order_seq = '$list[seq]'
					   and a.product_id = b.product_id";
			$result2 = mysql_query($sql, $connect) or die(mysql_error());
			while ($list2 = mysql_fetch_assoc($result2))
			{
				$strHTML .= "<tr>
								<td colspan=8 height=40>&nbsp;
									$list2[product_id] | 
									$list2[product_name] | 
									$list2[product_options]
								</td>
							</tr>";
			}
		}

		if (!$rows) 
			$strHTML = "<tr><td colspan=10 align=center>조회된 데이터가 없습니다.</td></tr>";

		$strHTML .= "</table>";

		echo $strHTML;
	}



	function csinfo()
	{
		global $connect;

		foreach ($_REQUEST as $key=>$value) $$key = $value;


		$strHTML = "<table cellspacing=0 cellpadding=3 class=tbl_csinfo>
		";

		$sql = "select seq from orders where recv_mobile = '$mobile' order by seq desc limit 3";
		$result = mysql_query($sql, $connect) or die(mysql_error());
		while ($list = mysql_fetch_assoc($result))
		{
			$seqs = "$list[seq],";
		}
		$seqs = substr($seqs,0,-1);

		$sql = "select * from csinfo where order_seq in ($seqs) order by seq desc limit 30";
		$result = mysql_query($sql, $connect) or die(mysql_error());
		while ($list = mysql_fetch_assoc($result))
		{
			$strHTML .= "<tr>
						<th><b>$list[input_date] $list[input_time]</td>
						<th>$list[writer]</td>
						<th class=last>$list[cs_reason]</td>
					  </tr>
					  <tr>
						<td colspan=3 class=last>$list[content]</td>
					  </tr>
			";
		}


		$strHTML .= "</table>";

		echo $strHTML;
	}


	function grid_tab2()
	{
		global $connect;

        foreach ($_REQUEST as $key=>$value) $$key = trim($value);

		$line_per_page = 12;
		$page = (!$page) ? 1 : $page;

		$starter = ($page-1) * $line_per_page;

		if ($tx_inc =='true') $msg_type = "('RX', 'TX')";
		else $msg_type = "('RX')";

		if ($category) {
			$options .= "and category = '$category'";
		}

		//-----------------------------------------------
		$sql = "select distinct recv_mobile, count(*) cnt
				  from sms_msg_history 
				 where date(crdate) between '$start_date' and '$end_date'
				   and msg_type in ${msg_type}
					   ${options}
				 group by recv_mobile";
		$r = mysql_query($sql, $connect) or die(mysql_error());
		$total_rows = mysql_num_rows($r);


		$sql = "select distinct recv_mobile, max(seq) max_seq, max(crdate) crdate, count(*) cnt
				  from sms_msg_history 
				 where date(crdate) between '$start_date' and '$end_date'
				   and msg_type in ${msg_type}
					   ${options}
				 group by recv_mobile
				 order by crdate desc limit $starter, $line_per_page";
		$result = mysql_query($sql, $connect) or die(mysql_error());

		$data_arr = array();
		$j = 0;

		$strHTML = "<table class=tbl_tab2>";
		$strHTML .= "<tr>
						<th width=20><input type=checkbox id='check_all'></td>
						<th width=90>수신번호</td>
						<th width=90>카테고리</td>
						<th width=90>일자</td>
						<th width=80>판매처</td>
						<th width=80>구매자</td>
						<th>최근대화내용</td>
						<th width=100>주문번호</td>
						<th width=50>상세</td>
					 </tr>";
		while ($list = mysql_fetch_assoc($result))
		{
			$sql = "select * from sms_msg_history
					 where recv_mobile = '$list[recv_mobile]'
					   and seq = '$list[max_seq]'";
			$list2 = mysql_fetch_assoc(mysql_query($sql, $connect));

			$type = ($list2[msg_type] == "TX") ? "발신" : "<font color=red><b>수신</b></font>";
			$status = ($list2[msg_type] == "RX" && $list2[status] == 0) ? "읽지않음" : "읽음";


			$work = "<a href=\"javascript:view_messenger('$list[recv_mobile]');\"><img src=/images15/btn_chat_small.gif align=absmiddle></a>";

			$msg = str_replace(array("","<br>"),  "", $list2[msg]);
			$msg = cutstr_utf8(str_replace("\n", "| ", $msg), 64);

			$msg = "<a href=\"javascript:view_messenger('$list[recv_mobile]');\">$msg</a>";

			$crdate = substr($list2[crdate],5,14);

			$strHTML .= "<tr>
						  <td align=center><input type=checkbox class=chk id='chk_$list[recv_mobile]''></td>
						  <td align=center>$list[recv_mobile]</td>
						  <td align=center>$list2[category]</td>
						  <td align=center>$crdate</td>
						  <td>$list2[shop_name]</td>
						  <td>$list2[name]</td>
						  <td>$msg</td>
						  <td>$list[key_string]</td>
						  <td align=center>$work</td>
					    </tr>";
		}

		$strHTML .= "</table>";


		echo $strHTML;

		$pages = class_ER00::page_count($total_rows, $page, $line_per_page, $category);
		echo $pages;
	}

	function page_count($total_rows, $page, $line_per_page, $category)
	{
		$strHTML = "
			<table class='pager' width='100%' border='0' align='center' cellpadding='0' cellspacing='0'>
  				<tr height=25>
    				<td align=center><font face=tahoma>
		";

		if (!$page) $page = 1;

     	if ($page%10 == 0) $fix = $page - 1;
        else $fix = $page;


		if ($fix < 0) $fix = 0;
        $first_page = ((int)($fix/10)) * 10 + 1;

        if ( $first_page < 1 ) $first_page = 1;
        $prev = $first_page - 1;

        $last_page = $first_page + 9;

        if ( $last_page > (int)($total_rows / $line_per_page + 1) ) {

		if ($total_rows % $line_per_page == 0)
		  $last_page = (int)@($total_rows/$line_per_page);
        else
		  $last_page = (int)@($total_rows/$line_per_page + 1);

                $next = 0;
        }
        else
                $next = $last_page + 1;

		if ( $prev != 0 ) {
			$strHTML .= "<a href=\"javascript:Query('$category', '$prev');\"><<</a>";
		}

		for ( $i = $first_page; $i <= $last_page ; $i++ ) {
			if ($i == $page)
		  		$strHTML .= "<a href=\"javascript:Query('$category', '$i');\"><span class='pagelink_selected'>$i</span></a> ";
			else
		  		$strHTML .= "<a href=\"javascript:Query('$category', '$i');\" class=pagelink>$i</a> ";
		}

		if ( $next != 0 ) {
			$strHTML .= "<a href=\"javascript:Query('$category', '$next');\"> >></a>";
		}

		$strHTML .= "</font></td>
		  		</tr>
			</table>
		";

		echo $strHTML;
	}


	function grid_ER02()
	{
		global $template;
		$connect = db_connect();

		$grid = new jqGridRender();

		foreach ($_REQUEST as $key=>$value) $$key = trim($value);

        // Lets create the model manually 
        $Model = array(
            array(
                "name"      => "category"
                ,"label"    => "카테고리"
                ,"width"    => 200  
                ,"sorttype" => "string"
                ,"align"    => "center"
            ),
     		array(
                "name"          => "work"
                ,"label"        => "작업"
                ,"width"        => 80
                ,"align"        => "center"
			)
        );
    	
        // Let the grid create the model 
        $grid->setColModel($Model);
                
                
        // Set grid option datatype to be local 
        $grid->setGridOptions(array(
                 "rowList"          => array(10,20,50)
                ,"datatype"         => "local"
                ,"cellsubmit"       => "clientArray"
                ,"width"            => 300
                ,"height"           => 300
                ,"shrinkToFit"      => false
        )); 

		$data = array();

		$sql = "select sms_category from ez_config";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));

		$category_list = explode(",", $list[sms_category]);
		if (strlen($list[sms_category])) {
			foreach ($category_list as $category)
			{
				$btn = "<a href=\"javascript:Del('$category');\"><img src='/images/icon_del.gif' align=absmiddle></a>";
				$data[] = array(
						"category"      => $category,
						"work"          => $btn);
			}
		}

        $grid->callGridMethod("#grid", 'addRowData', array("category",$data));
        $grid->renderGrid('#grid','',true, null, null, true,true);

	}

	function grid_ER03()
	{
		global $template;
		$connect = db_connect();

		$grid = new jqGridRender();

		foreach ($_REQUEST as $key=>$value) $$key = trim($value);

        // Lets create the model manually 
        $Model = array(
            array(
                "name"      => "category"
                ,"label"    => "카테고리"
                ,"width"    => 200  
                ,"sorttype" => "string"
                ,"align"    => "center"
            ),
     		array(
                "name"          => "work"
                ,"label"        => "작업"
                ,"width"        => 80
                ,"align"        => "center"
			)
        );
    	
        // Let the grid create the model 
        $grid->setColModel($Model);
                
                
        // Set grid option datatype to be local 
        $grid->setGridOptions(array(
                 "rowList"          => array(10,20,50)
                ,"datatype"         => "local"
                ,"cellsubmit"       => "clientArray"
                ,"width"            => 300
                ,"height"           => 300
                ,"shrinkToFit"      => false
        )); 

		$data = array();

		$sql = "select sms_category from ez_config";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));

		$category_list = explode(",", $list[sms_category]);
		if (strlen($list[sms_category])) {
			foreach ($category_list as $category)
			{
				$btn = "<a href=\"javascript:Move('$category');\"><img src='/images2/btn_move_folder.gif' align=absmiddle></a>";
				$data[] = array(
						"category"      => $category,
						"work"          => $btn);
			}
		}

        $grid->callGridMethod("#grid", 'addRowData', array("category",$data));
        $grid->renderGrid('#grid','',true, null, null, true,true);

	}
	//-- write cs
	function write_cs()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$sql = "select * from sms_msg_history
				 where recv_mobile = '$recv_mobile'	
				   and seq = '$seq'";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));

		if ($list[key_string]) {
			//-- csinfo에 내용 저장
            $ins_sql = "insert csinfo 
                           set order_seq  = '$list[key_string]',
                               pack       = '$list[key_string]',
                               input_date = '$list[crdate]',
                               input_time = '$list[crdate]',
                               writer     = '$_SESSION[LOGIN_NAME]',
                               cs_type    = '31',
                               cs_reason  = '$list[key_string]',
                               cs_result  = '0',
                               content    = '[문자대화 수신] $list[msg] (수신시간:$list[crdate])'";
            @mysql_query ($ins_sql, $connect);

			$upd_sql = "update sms_msg_history set
							   write_cs = 1
						 where recv_mobile = '$recv_mobile'
						   and seq = '$seq'";
            @mysql_query ($upd_sql, $connect);
	
			echo 1;
		} else {
			echo 0;
		}
	}

	//-- del cs
	function del_cs()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$del_sql = "delete from sms_msg_history
				 where recv_mobile = '$recv_mobile'	
				   and seq = '$seq'";
		mysql_query($del_sql, $connect) or die(mysql_error());
	}


	//-- 미처리->처리완료
	function set_done()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$upd_sql = "update sms_msg_history set
						   status = 1, is_done = 1
					 where recv_mobile = '$recv_mobile'
					   and seq = '$seq'";
		mysql_query($upd_sql, $connect) or die(mysql_error());
	}


	//-- 미처리<-처리완료
	function set_undone()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$upd_sql = "update sms_msg_history set
						   is_done = 0
					 where recv_mobile = '$recv_mobile'
					   and seq = '$seq'";
		mysql_query($upd_sql, $connect) or die(mysql_error());
	}

	//-- get category
	function list_category()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$unread = $this->get_unread_msg_count($connect);
		$cnt = ($unread) ? $unread : "";

		if ($category == "") $strHTML = "<b>";
		$strHTML .= "<span class='btn_pack medium'><a href='javascript:Query();'>전체 ${cnt}</a></span>&nbsp;";
		if ($category == "") $strHTML .= "</b>";

		$sql = "select sms_category from ez_config";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));

		if (strlen($list[sms_category])) {
			$arr = explode(",", $list[sms_category]);

			$selectHTML = "<select name='tab2_cate_sel' id='tab2_cate_sel'>";
			$selectHTML .= "<option value=''>= 카테고리 선택 =</option>";
			foreach ($arr as $cate) {
				$selectHTML .= "<option value='$cate'>$cate</option>";
			}
			$selectHTML .= "</select>&nbsp;";
			$selectHTML .= "<a href='javascript:set_category();'><input type=button value='이동'></a>&nbsp; 카테고리별 보기 : ";

			foreach ($arr as $cate) {
				$unread = $this->get_unread_msg_count($connect, $cate);
				$cnt = ($unread) ? $unread : "";
		
				if ($category == $cate) $strHTML .= "<b>";
				$strHTML .= "<span class='btn_pack medium'><a href=\"javascript:Query('$cate');\">$cate ${cnt}</a></span>&nbsp;";
				if ($category == $cate) $strHTML .= "</b>";
			}
		}

		$strHTML = $selectHTML . $strHTML;

		echo $strHTML;

	}

	function get_unread_msg_count($connect, $category="")
	{
		if ($category) {
			$options = "and category = '$category'";
		}

		$sql = "select count(*) cnt from sms_msg_history
				 where status = 0 and msg_type = 'RX' $options";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));

		return $list[cnt];
	}

	//-- add category
	function add_category()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$sql = "select sms_category from ez_config";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));

		if (strlen($list[sms_category])) {
			$arr = explode(",", $list[sms_category]);
			if (!in_array($category, $arr)) {
				$sms_category = $list[sms_category] . "," . $category;

				$upd_sql = "update ez_config set sms_category = '$sms_category'";
				mysql_query($upd_sql, $connect) or die(mysql_error());
			}
		} else {
			$upd_sql = "update ez_config set sms_category = '$category'";
			mysql_query($upd_sql, $connect) or die(mysql_error());
		}

	}

	//-- del category
	function del_category()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$sql = "select sms_category from ez_config";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));

		if (strlen($list[sms_category])) {
			$arr = explode(",", $list[sms_category]);
			if (in_array($category, $arr)) {
				array_splice($arr, array_search($category, $arr),1);
				$sms_category = implode(",", $arr);

				$upd_sql = "update ez_config set sms_category = '$sms_category'";
				mysql_query($upd_sql, $connect) or die(mysql_error());
			}
		}

		//-- sms_msg_history에 전부 삭제
		$upd_sql = "update sms_msg_history set category = '' where category = '$category'";
		mysql_query($upd_sql, $connect) or die(mysql_error());
	}

	//-- move category
	function move_category()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;

		//---------------------------
		//-- 
		$arr = explode("|", $phone);
		foreach ($arr as $recv_mobile)
		{
			if (!$recv_mobile) continue;

			$upd_sql = "update sms_msg_history set
						   category = '$category'
					 where recv_mobile = '$recv_mobile'";
			debug($upd_sql);
			mysql_query($upd_sql, $connect) or die(mysql_error());
		}
	}

	//-- get_macro
	function get_macro()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;
		
		//--
		$strHTML = "<ul>";
		$sql = "select * from sms_config where type = 'macro' and title != '' order by title";
		$result = mysql_query($sql, $connect) or die(mysql_error());
		while ($list = mysql_fetch_assoc($result))
		{
			$title = substr($list[title], 0, 50);
			$list[value] = stripslashes($list[value]);
			$strHTML .= "<li><a href=\"javascript:select_macro('$list[seq]', '$list[title]', '$list[value]')\">$title</a></li>";
		}
		$strHTML .= "</ul>";

		echo $strHTML;
	}

	function macro_add()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;
		
		$ins_sql = "insert into sms_config (crdate, type, title, value)
				values (now(), 'macro', '$title', '$content')";
		mysql_query($ins_sql, $connect) or die(mysql_error());
	}

	function macro_mod()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;
		
		$content = addslashes($content);
		$upd_sql = "update sms_config set
						   title = '$title',
						   value = '$content'
					 where seq = '$seq'";
		mysql_query($upd_sql, $connect) or die(mysql_error());
	}

	function macro_del()
	{
		global $template;
		$connect = db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;
		
		$del_sql = "delete from sms_config where seq = '$seq'";
		mysql_query($del_sql, $connect) or die(mysql_error());
	}

	function get_history()
	{
        global $connect, $msg, $receiver, $seq, $mode;
        
		$strHTML = "<div id='sms_history'><table class='sms'>";
		$receiver = str_replace("-", "", $receiver);

		$sql = "select * from sms_msg_history where recv_mobile = '$receiver' order by seq limit 200";
		$result = mysql_query($sql, $connect) or die(mysql_error());
		while ($list = mysql_fetch_assoc($result))
		{
			$pos = ($list[msg_type] == 'TX') ? "right" : "left";

			if ($list[status] == 0) $status = "전송중";
			else if ($list[status] == 2) $status = "전송완료";
			else if ($list[status] == -2) $status = "전송오류";

			//-- unread mark
			if ($list[msg_type] == "RX" && $list[status] == 0) 
				$unread = "unread";
			else 
				$unread = "";
			
			$today = date("Y-m-d");
			$day = ($today == substr($list[crdate],0,10)) ? "오늘" : str_replace("-", "/", substr($list[crdate],5,5));

			$crdate = $day . " " . substr($list[crdate],10,6) . "&nbsp;&nbsp;";

			if ($mode == "chat" && $list[msg_type] == "RX" && $list[write_cs] == 0) {
				$write_cs = "<a href=\"javascript:write_cs('$list[recv_mobile]', '$list[seq]')\"><img src='/images2/btn_add_cs.gif' title='C/S에 내용 추가' align=absmiddle></a> <a href=\"javascript:del_cs('$list[recv_mobile]', '$list[seq]')\"><img src='/images2/btn_del_cs.gif' title='삭제' align=absmiddle></a>";
			} else {
				$write_cs = "";
			}


			$list[msg] = nl2br($list[msg]);

			$strHTML .= "<tr>";
			$strHTML .= "<td align=${pos}><span class='title'>$crdate</span><span id='cs_$list[seq]'>$write_cs</span></td>";
			$strHTML .= "</tr>";
			$strHTML .= "<tr>";
			$strHTML .= "<td align=${pos}><div class='box ${pos} ${unread}'>$list[msg]</div></td>";
			$strHTML .= "</tr>";
		}
		$strHTML .= "</table>";
		$strHTML .= "</div>";

		$strHTML .= "<script>$('#sms_history').scrollTop($('#sms_history')[0].scrollHeight);</script>";
		echo $strHTML;
    }

  	function set_read_all()
  	{
		global $connect, $receiver;

		$upd_sql = "update sms_msg_history set status = 2 
				 where recv_mobile = '$receiver'
				   and msg_type = 'RX'
			  	   and status = 0";
    	@mysql_query($upd_sql, $connect);
  	}
}


?>
