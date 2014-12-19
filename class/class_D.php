<?
////////////////////////////////////////
//
// 발주에 관련된 class
// date: 2005.8.10
//
/////////////////////////////////////////
require_once "class_top.php";


class class_D extends class_top
{
    function get_shop_name ( $shop_id )
    {
	if( !$shop_id ) return;
	global $connect;
	$query = "select shop_name from shopinfo where shop_id=$shop_id";
	$result = mysql_query( $query, $connect );
	$data = mysql_fetch_array ( $result );
	return $data[shop_name];
    }
//////////////////////////////////////////////////////////////////////
   function get_product_name_option3( $product_id, &$product_name, &$product_option )
   {
       global $connect;
       $query = "select brand, options from products where product_id='$product_id'";
       $result = mysql_query ( $query );
       $data = mysql_fetch_array( $result );

       $product_name = $data[brand];
       $product_option = $data[options];
   }

   function get_product_name_option2( $product_id, &$product_name, &$product_option )
   {
       global $connect;
       $query = "select name,options from products where product_id='$product_id'";

       $result = mysql_query ( $query , $connect);
       $data = mysql_fetch_array( $result );

	if ( _DOMAIN_ == "yuen" 
	or _DOMAIN_ == "yeowoowa" )
		preg_match("/\[?\](.*)/", $data[name], $matches);

        if ( $matches[1] )
                $product_name = $matches[1];
        else
                $product_name = $data[name];

        $product_option = $data[options];
   }

    function confirm_order()
    {
	global $connect;

	$keyword = $_REQUEST["keyword"];
	$page = $_REQUEST["page"];
	$start_date = $_REQUEST["start_date"] ? $_REQUEST["start_date"] : strftime('%Y-%m-%d', strtotime('now'));
	$end_date = $_REQUEST["end_date"] ? $_REQUEST["end_date"] : strftime('%Y-%m-%d', strtotime('now'));
        $supply_code = $_REQUEST["supply_code"];
        $shop_id = $_REQUEST["shop_id"];        // 판매처

        //////////////////////////////////////////////
        // 상태를 2로 변경
        $query = "update orders set download_date=Now() ";

	$options = " where status in ( 1, 2, 11 )";

	if ($start_date)
	  $options .= " and collect_date >= '$start_date 00:00:00' ";
        if ($end_date)
	  $options .= " and collect_date <= '$end_date 23:59:59' ";

        ///////////////////////////////////////////
        // supply_code 가 있을 경우
        if ( $supply_code )
           $options .= " and supply_id = '$supply_code'";

	$result = mysql_query($query . $options, $connect);
	return $result;

    }

    ///////////////////////////////////////////////////////////
    // limit_option 이 0 일 경우는 전체 출력 주로 download받을때 사용
    // 검색 기준일이 주문일일 경우 : 
    // 검색 기준일이 송장 입력일
    function get_order_list( &$total_rows , $limit_option=0, $search_date='order_date', $no_cancel=0)
    {
	global $connect, $start_date, $end_date, $pos_confirm, $trans_who, $transonly, $trans_corp;

	$line_per_page = _line_per_page;
	$keyword = $_REQUEST["keyword"];
	$page = $_REQUEST["page"];

	//$start_date = $_REQUEST["start_date"] ? $_REQUEST["start_date"] : strftime('%Y-%m-%d', strtotime('now'));
	//$end_date = $_REQUEST["end_date"] ? $_REQUEST["end_date"] : strftime('%Y-%m-%d', strtotime('now'));

        if ( $_SESSION[LOGIN_LEVEL] )
            $supply_code = $_REQUEST["supply_code"];// 공급처
        else
            $supply_code = $_SESSION["LOGIN_CODE"];// 공급처

        $shop_id = $_REQUEST["shop_id"];        // 판매처

	$query = "select a.*, b.name as supply_name, c.shop_name as shop_name ";
	$query_cnt = "select count(*) cnt ";

	$options = " from orders a, userinfo b , shopinfo c 
                    where a.order_id != '' 
                      and a.supply_id = b.code
                      and a.shop_id = c.shop_id ";

	if ( $keyword )
	  $options .= " and (a.order_id = '$keyword' or a.order_name = '$keyword' or a.product_name like '%$keyword%') ";

	if ($start_date)
	  $options .= " and a." . $search_date . ">= '$start_date 00:00:00' ";
	if ($end_date)
	  $options .= " and a." . $search_date . "<= '$end_date 23:59:59' ";
//echo $options;
//exit;
        ///////////////////////////////////////////
        // shop_id 가 있는 경우
        if ( $shop_id)
           $options .= " and a.shop_id= '$shop_id'";

        if ( $trans_corp && $trans_corp != 99)
           $options .= " and a.trans_corp= '$trans_corp'";

        //////////////////////////////
        // trans_who가 있는 경우
        if ( $trans_who )
           $options .= " and a.trans_who = '$trans_who'";

	// not_trans
	if ( $transonly )
	   $options .= " and a.status = 1 ";

        //////////////////////////////////////////
        // supply_code 가 있을 경우
	if (_DOMAIN_ == "jyms") $options .= "";
	else if (_DOMAIN_ == 'ecstorm') $options .= "";
	else
	{
          if ( $supply_code )
            $options .= " and a.supply_id = '$supply_code'";
	}

        ///////////////////////////////////////////
        // status 가 있는 경우
        // pos 확인 전
        if ( $pos_confirm == 1 )
        {
           $options .= " and a.status ='" ._trans_no .  "'";
        }
        // pos 확인 후
        else if ( $pos_confirm == 2 )
        {
           $options .= " and a.status in (" . _trans_confirm . " )";
        }
        // 송장 입력 
        else if ( $pos_confirm == 3 )
        {
           $options .= " and a.status in (" ._trans_no . "," . _trans_confirm . " )";
        }
 
        else
        {
           if ( $search_date == "trans_date" && $_REQUEST["status"] == 99 )
              $options .= " and a.status in (" ._trans_no . "," . _trans_confirm . " )";
           else
              if ( $_REQUEST["status"] )
                 $options .= " and a.status = '". $_REQUEST["status"] ."'";
        }

        // 취소의 출력여부 check
        if ( $no_cancel )
        {
		$options .= " and a.order_cs not in (" . _cancel_req_b . "," . _cancel_req_a . "," . _cancel_req_confirm . "," . _cancel_com_b . "," . _cancel_com_a. " )";

                if ( $no_cancel == 2 ) // 교환 주문도 나오지 않는다
                    $options .= " and substring(a.order_id,1,1) <> 'C' ";
        }

        // $options .= " and substring(a.order_id,1,1) <> 'C' ";
	$options .= " order by a.seq desc ";

        if ( !$limit_option )
        {
	   $starter = $page ? ($page-1) * $line_per_page : 0;
	   $limit = " limit $starter, $line_per_page";
        }

	////////////////////////////////////////////////// 
	// total count 가져오기
	$list = mysql_fetch_array(mysql_query($query_cnt . $options, $connect));
	$total_rows = $list[cnt];

// echo $query . $options;

	$result = mysql_query($query . $options . $limit, $connect);
	return $result;
    }

    ////////////////////////////////////////////////////////
    //  seq를 넘기고 택배사 코드와 송장 번호 받는다
    function get_trans_info ( $seq, &$trans_corp, &$trans_no )
    {
       global $connect;
       $query = "select trans_corp, trans_no from orders where seq='$seq'";
       $result = mysql_query ( $query, $connect );
       
       $data = mysql_fetch_array ( $result );
       
       $trans_corp = $data[trans_corp];
       $trans_no = $data[trans_no];
    }

   function get_product_option( $product_id )
   {
       global $connect;
       $query = "select options from products where product_id='$product_id'";
       $result = mysql_query ( $query , $connect);
       $data = mysql_fetch_array( $result );

       return $data[options] ? $data[options] : ""; 
   }

   function get_product_name( $product_id )
   {
       global $connect;
       $query = "select name from products where product_id='$product_id'";
       $result = mysql_query ( $query );
       $data = mysql_fetch_array( $result );
       return $data[name]; 
   }

   function get_brand_name( $product_id )
   {
       global $connect;
       $query = "select brand from products where product_id='$product_id'";
       $result = mysql_query ( $query );
       $data = mysql_fetch_array( $result );
       return $data[brand]; 
   }

   // product_name: 상품명
   // brand: 사입처 상품명
   function get_product_name2( $product_id, &$product_name, &$brand )
   {
       global $connect;
       $query = "select name,brand from products where product_id='$product_id'";
       $result = mysql_query ( $query );
       $data = mysql_fetch_array( $result );
       
       $product_name = $data[name];
       $brand = $data[brand];
   }


   //////////////////////////////////////////////////////
   function get_org_id($product_id)
   {
	global $connect;

	$sql = "select org_id from products where product_id = '$product_id'";
	$prod = mysql_fetch_array(mysql_query($sql, $connect));
	return $prod[org_id];
   }
  
   //////////////////////////////////////////
   // use D101_move.php
   function make_trans_who($list, $org_trans_who, &$using)
   {
	global $connect;

	$amount = $list[price] * $list[qty];
	if ($list[amount] > 0 && $amount == 0) $amount = $list[amount];

	if (_DOMAIN_ == "paint") $amount = $list[amount];

	$qty    = $list[qty];

	$stock_manage_use = $_SESSION[STOCK_MANAGE_USE];
	$trans_who = $org_trans_who;
	$basename = $_SERVER[REQUEST_URI]. " | ";

	$shop_id = $list[shop_id];
	// 확장 판매처인경우 기본판매처코드로 변경 (10102 -> 10002)
	if (($shop_id-10000) > 100) 
		$shop_id = ($shop_id%100) + 10000;


	////////////////////////
	// type1
	$sql = "select * from trans_rule where type = 1";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));

	if ($rule)
	{
		if ($org_trans_who == "") $trans_who = $rule[trans_who];
		debug("$basename TYPE #1 ($trans_who)");
		$using=1;
	}

	////////////////////////
	// type 2
	$sql = "select * from trans_rule where type = 2 and shop_id = 'all'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		$trans_who = $rule[trans_who];
		debug("$basename TYPE #2 shop : all ($trans_who)");
		$using=2;
	}

	$sql = "select * from trans_rule where type = 2 and shop_id = '$shop_id'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		$trans_who = $rule[trans_who];
		debug("$basename TYPE #2 shop : $shop_id ($trans_who)");
		$using=2;
	}

	////////////////////////
	// type 3
	$sql = "select * from trans_rule where type = 3 and shop_id = 'all'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		if ($amount >= $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #3 shop : all $amount > $rule[amount] ($trans_who)");
			$using=3;
		}
	}

	$sql = "select * from trans_rule where type = 3 and shop_id = '$shop_id'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		if ($amount >= $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #3 shop : $shop_id $amount > $rule[amount] ($trans_who)");
			$using=3;
		}
	}

	////////////////////////
	// type 4
	$sql = "select * from trans_rule where type = 4 and shop_id = 'all'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		if ($amount < $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #4 shop : all  $amount < $rule[amount] ($trans_who)");
			$using=4;
		}
	}

	$sql = "select * from trans_rule where type = 4 and shop_id = '$shop_id'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		if ($amount < $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #4 shop : $shop_id  $amount < $rule[amount] ($trans_who)");
			$using=4;
		}
	}

	////////////////////////
	// type 5
	$sql = "select * from trans_rule where type = 5 order by shop_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($rule = mysql_fetch_array($result))
	{
		if ($rule[shop_id] == $shop_id || $rule[shop_id] == 'all')
		{
			if ($stock_manage_use && !is_numeric($list[self_no]))
		    		$work_id = class_D::get_org_id($list[self_no]);
			else
		    		$work_id = $list[self_no];
		 
			$ids = explode(",", $rule[product_id]);
			if ((!$rule[cutoff] &&  in_array($work_id, $ids)) || 
		   	    ( $rule[cutoff] && !in_array($work_id, $ids))) 
			{
		    		if ($amount >= $rule[amount]) 
		    		{
					$trans_who = $rule[trans_who];
					debug("$basename TYPE #5 shop : $shop_id  $amount >= $rule[amount] ($trans_who)");
					$using=5;
		    		}
			}
		}
	}

	////////////////////////
	// type 6
	$sql = "select * from trans_rule where type = 6 order by shop_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($rule = mysql_fetch_array($result))
	{
		if ($rule[shop_id] == $shop_id || $rule[shop_id] == 'all')
		{
			if ($stock_manage_use && !is_numeric($list[self_no]))
		    		$work_id = class_D::get_org_id($list[self_no]);
			else
		    		$work_id = $list[self_no];

			$ids = explode(",", $rule[product_id]);
			if ((!$rule[cutoff] &&  in_array($work_id, $ids)) || 
		   	    ( $rule[cutoff] && !in_array($work_id, $ids)))
			{
		    		if ($qty >= $rule[amount]) 
		    		{
					$trans_who = $rule[trans_who];
					debug("$basename TYPE #6 shop : $shop_id  $qty >= $rule[amount] ($trans_who)");
					$using=6;
				}
			}
		}
	}


	////////////////////////
	// type 7
	$sql = "select * from trans_rule where type = 7 and shop_id = 'all'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		if ($qty >= $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #7 shop : all $qty > $rule[amount] ($trans_who)");
			$using=7;
		}
	}

	$sql = "select * from trans_rule where type = 7 and shop_id = '$shop_id'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		if ($qty >= $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #7 shop : $shop_id $qty > $rule[amount] ($trans_who)");
			$using=7;
		}
	}

	////////////////////////
	// type 8
	$sql = "select * from trans_rule where type = 8 order by shop_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($rule = mysql_fetch_array($result))
	{
		if ($rule[shop_id] == $shop_id || $rule[shop_id] == 'all')
		{
			$work_id = $list[product_no];

			$ids = explode(",", $rule[product_id]);
			if ((!$rule[cutoff] &&  in_array($work_id, $ids)) || 
		   	    ( $rule[cutoff] && !in_array($work_id, $ids)))
			{
		    		if ($qty >= $rule[amount]) 
		    		{
					$trans_who = $rule[trans_who];
					debug("$basename TYPE #8 shop : $shop_id  $qty >= $rule[amount] ($trans_who)");
					$using=8;
				}
			}
		}
	}

	////////////////////////
	// type 9
	$sql = "select * from trans_rule where type = 9 order by shop_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($rule = mysql_fetch_array($result))
	{
		if ($rule[shop_id] == $shop_id || $rule[shop_id] == 'all')
		{
			$work_id = $list[product_no];
		 
			$ids = explode(",", $rule[product_id]);
			if ((!$rule[cutoff] &&  in_array($work_id, $ids)) || 
		   	    ( $rule[cutoff] && !in_array($work_id, $ids))) 
			{
		    		if ($amount >= $rule[amount]) 
		    		{
					$trans_who = $rule[trans_who];
					debug("$basename TYPE #9 shop : $shop_id  $amount >= $rule[amount] ($trans_who)");
					$using=9;
		    		}
			}
		}
	}


	if ($trans_who == "0") {
		debug("make_trans_who warning ($old_trans_who) ($trans_who)");
		$trans_who = "";
	}
	


	debug("make_trans_who finished : ($old_trans_who) ($trans_who) ($using)");
	return $trans_who;
   }

   //////////////////////////////////////////////////////////////////////////////
   // 합포에서 사용하는 선착불 관리 (** 기본선착불과 사이트선착불은 사용안함 **)
   // use class_DE00.php
   function make_trans_pack($packno, &$using)
   {
	global $connect;

	$stock_manage_use = $_SESSION[STOCK_MANAGE_USE];
	$basename = $_SERVER[REQUEST_URI]. " | ";

	debug("make_trans_pack: packno($packno)");

	////////////////////////
	// get amount, qty
	if (_DOMAIN_ == "heelstar")
	{
		$sql = "select sum(shop_price*qty) amount, sum(qty) qty from orders where pack = '$packno' and (options != '추가상품' && options != '[추가]')";

	}
	else if (_DOMAIN_ == "paint")
	{
		$sql = "select sum(amount) amount, sum(qty) qty from orders where pack = '$packno'";
	}
	else
	{
		$sql = "select sum(shop_price*qty) amount, sum(qty) qty from orders where pack = '$packno'";
	}
debug($sql);
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	$amount = $list[amount];
	$qty = $list[qty];

	// get shop_id (여러개인경우에는 처리 불가능한 로직임)
	$sql = "select distinct shop_id, sum(qty) qty from orders where pack = '$packno' group by shop_id order by qty desc limit 1";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	$shop_id = $list[shop_id];
	

	debug("amount = $amount");
	////////////////////////
	// type 3
	$sql = "select * from trans_rule where type = 3 and shop_id = 'all'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		debug("amount = $amount, rule_amount = $rule[amount]");

		if ($amount >= $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #3 shop : all $amount > $rule[amount] ($trans_who)");
			$using=3;
		}
	}

	$sql = "select * from trans_rule where type = 3 and shop_id = '$shop_id'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		if ($amount >= $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #3 shop : $shop_id $amount > $rule[amount] ($trans_who)");
			$using=3;
		}
	}

	////////////////////////
	// type 4
	$sql = "select * from trans_rule where type = 4 and shop_id = 'all'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		if ($amount < $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #4 shop : all  $amount < $rule[amount] ($trans_who)");
			$using=4;
		}
	}

	$sql = "select * from trans_rule where type = 4 and shop_id = '$shop_id'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		if ($amount < $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #4 shop : $shop_id  $amount < $rule[amount] ($trans_who)");
			$using=4;
		}
	}

	////////////////////////
	// type 5 (New Logic)
	$sql = "select * from trans_rule where type = 5 order by shop_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($rule = mysql_fetch_array($result))
	{
		$amount = 0;
		if ($rule[shop_id] == $shop_id || $rule[shop_id] == 'all')
		{
			$ids = explode(",", $rule[product_id]);

			$sql = "select product_id, (shop_price*qty) amount,
					amount amount2
				  from orders where pack = '$packno'";
			$result2 = mysql_query($sql, $connect) or die(mysql_error());
			while ($list2 = mysql_fetch_array($result2))
			{
				
				if ($stock_manage_use && !is_numeric($list2[product_id]))
					$work_id = class_D::get_org_id($list2[product_id]);
				else
					$work_id = $list2[product_id];

				if ((!$rule[cutoff] &&  in_array($work_id, $ids)) || 
				    ( $rule[cutoff] && !in_array($work_id, $ids))) 
				{
					if (_DOMAIN_ == "paint")
						$amount +=  $list2[amount2];
					else
						$amount +=  $list2[amount];
				}

		    		if ($amount >= $rule[amount]) 
		    		{
					$trans_who = $rule[trans_who];
					debug("$basename TYPE #5 shop : $shop_id  $amount >= $rule[amount] ($trans_who)");
					$using=5;
		    		}
				if ($trans_who == "선불") break;
			}
		}
		if ($trans_who == "선불") break;
	}


	////////////////////////
	// type 6 (New Logic)
	$sql = "select * from trans_rule where type = 6 order by shop_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($rule = mysql_fetch_array($result))
	{
		$qty = 0;
		if ($rule[shop_id] == $shop_id || $rule[shop_id] == 'all')
		{
			$ids = explode(",", $rule[product_id]);

			$sql = "select product_id, qty
				  from orders where pack = '$packno'";
			$result2 = mysql_query($sql, $connect) or die(mysql_error());
			while ($list2 = mysql_fetch_array($result2))
			{
				if ($stock_manage_use && !is_numeric($list2[product_id]))
					$work_id = class_D::get_org_id($list2[product_id]);
				else
					$work_id = $list2[product_id];

				if ((!$rule[cutoff] &&  in_array($work_id, $ids)) || 
				    ( $rule[cutoff] && !in_array($work_id, $ids))) 
				{
					$qty +=  $list2[qty];
				}

		    		if ($qty >= $rule[amount]) 
		    		{
					$trans_who = $rule[trans_who];
					debug("$basename TYPE #6 shop : $shop_id  $qty >= $rule[amount] ($trans_who)");
					$using=6;
					if ($trans_who == "선불") break;
		    		}
			}
		}
		if ($trans_who == "선불") break;
	}


	////////////////////////
	// type 7
	$sql = "select * from trans_rule where type = 7 and shop_id = 'all'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		if ($qty >= $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #7 shop : all $qty > $rule[amount] ($trans_who)");
			$using=7;
		}
	}

	$sql = "select * from trans_rule where type = 7 and shop_id = '$shop_id'";
	$rule = mysql_fetch_array(mysql_query($sql, $connect));
	if ($rule)
	{
		if ($qty >= $rule[amount]) 
		{
			$trans_who = $rule[trans_who];
			debug("$basename TYPE #7 shop : $shop_id $qty > $rule[amount] ($trans_who)");
			$using=7;
		}
	}

	////////////////////////
	// type 8 (New Logic)
	$sql = "select * from trans_rule where type = 8 order by shop_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($rule = mysql_fetch_array($result))
	{
		$qty = 0;
		if ($rule[shop_id] == $shop_id || $rule[shop_id] == 'all')
		{
			$ids = explode(",", $rule[product_id]);

			$sql = "select shop_product_id, qty
				  from orders where pack = '$packno'";
			$result2 = mysql_query($sql, $connect) or die(mysql_error());
			while ($list2 = mysql_fetch_array($result2))
			{
				$work_id = $list2[shop_product_id];

				if ((!$rule[cutoff] &&  in_array($work_id, $ids)) || 
				    ( $rule[cutoff] && !in_array($work_id, $ids))) 
				{
					$qty +=  $list2[qty];
				}

		    		if ($qty >= $rule[amount]) 
		    		{
					$trans_who = $rule[trans_who];
					debug("$basename TYPE #8 shop : $shop_id  $qty >= $rule[amount] ($trans_who)");
					$using=8;
					if ($trans_who == "선불") break;
		    		}
			}
		}

		if ($trans_who == "선불") break;
	}

	////////////////////////
	// type 9 (New Logic)
	$sql = "select * from trans_rule where type = 9 order by shop_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($rule = mysql_fetch_array($result))
	{
		$amount = 0;
		if ($rule[shop_id] == $shop_id || $rule[shop_id] == 'all')
		{
			$ids = explode(",", $rule[product_id]);

			$sql = "select shop_product_id, (shop_price*qty) amount
				  from orders where pack = '$packno'";
			$result2 = mysql_query($sql, $connect) or die(mysql_error());
			while ($list2 = mysql_fetch_array($result2))
			{
				$work_id = $list2[shop_product_id];

				if ((!$rule[cutoff] &&  in_array($work_id, $ids)) || 
				    ( $rule[cutoff] && !in_array($work_id, $ids))) 
				{
					$amount +=  $list2[amount];
				}

		    		if ($amount >= $rule[amount]) 
		    		{
					$trans_who = $rule[trans_who];
					debug("$basename TYPE #9 shop : $shop_id  $amount >= $rule[amount] ($trans_who)");
					$using=9;
		    		}
				if ($trans_who == "선불") break;
			}
		}
		if ($trans_who == "선불") break;
	}




	debug("make_trans_pack($packno): After trans_who = ($trans_who) using = ($using)");

	return $trans_who;
   }


   //////////////////////////////////////////
   // bubigirl 사용중지시 삭제해야 함
   //////////////////////////////////////////
   function get_bubilist_array($shop_xp, &$bigsize_list, &$nightgown_list, &$allinone_list, &$normal_list, &$gift3900_list, &$innerwear_list, &$gift0325_list)
   {
	$bigsize_list = array();
	$normal_list = array();
	$nightgown_list = array();
	$allinone_list = array();
	$gift3900_list = array();
	$innerwear_list = array();
	$gift0325_list = array();

	if ($shop_xp == _GMARKET_)
	{
		$innerwear_list = array ("155951914");
		$gift3900_list = array ("153843134");

		$bigsize_list 	= array (
					"120673221" , 
					"132470511", 
					"151903578", 
					"132673826", 
					"132631250"
				);

		$normal_list 	= array (  "120672910",  "124065534", "129682020", "130156781", "130156855", "130158421", "133541241", "153378303");

		$nightgown_list = array ("118432307" , "119175772" , "119495890" , "119907714" , "121362814" , "121750467");
		$allinone_list 	= array ("119964738" , "120612480" , "120612225");
		$gift0325_list = array("120506602", "120613655", "120507564", "120613857", "120965225", "118290864");
	}
	else if ($shop_xp == _AUCTION_)
	{
		$innerwear_list = array ("A513650472");
		$gift3900_list = array ("A512475170");
		$bigsize_list 	= array ( "A501239725", "A506379717" , "A506647320");
		$nightgown_list = array ("A097095885" , "A100220715" , "A107532447");
		$allinone_list 	= array ("A101976828");
		$normal_list 	= array ("A100609359" ,  "A506646741" , "A506379908" , "A506646652" );
		$gift0325_list = array ("A098027896", "A505390922");
	}
	else if ($shop_xp == _INTERPARK_OPEN_)
	{
		$bigsize_list	= array ("42879431");
		$allinone_list 	= array ("29409178" , "29410298");
		$normal_list	= array ("29082879" , "29280323", "29418642", "53777348");
	}
	else if ($shop_xp == _ONKET_)
	{
		$normal_list 	= array ("200053869990", "200053869990", "200055305599");
		$nightgown_list = array ("200049652507", "200053869990");
		$allinone_list 	= array ("200055022845");
		$bigsize_list 	= array ("200055022155");
	}
	else if ($shop_xp == _11ST_)
	{
		$normal_list 	= array ("4113514", "4113381", "4113225", "3928407", "8535989", "9529394");
		$nightgown_list = array ("4113042", "1081787", "1081880");
		$allinone_list 	= array ("2210085", "703223");
		$bigsize_list 	= array ("3934139", "5162580");
		$innerwear_list = array ("12473454");
		$gift0325_list = array ("18133020");
	}
	else if ($shop_xp == _GSESTORE_)
	{
		$normal_list 	= array ("1011384650", "1011384752", "1012864929", "1013384644");
		$nightgown_list = array ("1011426982", "1011390620", "1011387541");
		$allinone_list 	= array ("1013379798"); 
		$bigsize_list 	= array ("1011385321");
	}
   }

   function gift_single_bubigirl($list, $shop_xp, &$trans_who)
   {
	class_D::get_bubilist_array($shop_xp, &$bigsize_list, &$nightgown_list, &$allinone_list, &$normal_list, &$gift3900_list, &$innerwear_list, &$gift0325_list);

        /////////////////////////////////////////////////
        if (@in_array($list[product_no], $bigsize_list))
        {
                $gift = class_D::bubigirl_gift_bigsize($list[qty]);
                $m_trans_who = class_D::bubigirl_trans_bigsize($list[qty]);
                if ($m_trans_who) $trans_who = $m_trans_who;
        }
        else if (@in_array($list[product_no], $nightgown_list))
        {
                $gift = class_D::bubigirl_gift_nightgown($list[qty]);
                $m_trans_who = class_D::bubigirl_trans_nightgown($list[qty]);
                if ($m_trans_who) $trans_who = $m_trans_who;
        }
        else if (@in_array($list[product_no], $nightgown2_list))
        {
                $gift = class_D::bubigirl_gift_nightgown2($list[qty]);
                $m_trans_who = class_D::bubigirl_trans_nightgown2($list[qty]);
                if ($m_trans_who) $trans_who = $m_trans_who;
        }
        else if (@in_array($list[product_no], $gift3900_list))
        {
                $gift = class_D::bubigirl_gift_gift3900($list[qty]);
        }
        else if (@in_array($list[product_no], $innerwear_list))
        {
                $gift = class_D::bubigirl_gift_innerwear($list[qty]);
        }
        else if (@in_array($list[product_no], $allinone_list))
        {
                $gift = class_D::bubigirl_gift_allinone($list[qty]);
        }
        else if (@in_array($list[product_no], $gift0325_list))
        {
                $gift = class_D::bubigirl_gift_gift0325($list[qty]);
        }
        else
                $gift = class_D::bubigirl_gift_normal($list[qty]);

	return $gift;
   }


   function gift_pack_bubigirl($packno, $shop_xp)
   {
	global $connect;

	$gift = "";

	class_D::get_bubilist_array($shop_xp, &$bigsize_list, &$nightgown_list, &$allinone_list, &$normal_list, &$gift3900_list, &$innerwear_list, &$gift0325_list);

	$normal_qty = 0;
	$bigsize_qty = 0;
	$nightgown_qty = 0;
	$allinone_qty = 0;
	$nightgown2_qty = 0;
	$gift3900_qty = 0;
	$innerwear_qty = 0;
	$gift0325_qty = 0;

	$sql = "select shop_product_id, qty
	    from orders
	   where pack = '$packno'";
	$result4 = mysql_query($sql, $connect) or die(mysql_error());
	while ($list4 = mysql_fetch_array($result4))
	{
	    if (in_array($list4[shop_product_id], $bigsize_list))
		$bigsize_qty += $list4[qty];
	    else if (in_array($list4[shop_product_id], $nightgown_list))
		$nightgown_qty += $list4[qty];
	    else if (in_array($list4[shop_product_id], $allinone_list))
		$allinone_qty += $list4[qty];
	    else if (in_array($list4[shop_product_id], $normal_list))
		$normal_qty += $list4[qty];
	    else if (in_array($list4[shop_product_id], $gift3900_list))
		$gift3900_qty += $list4[qty];
	    else if (in_array($list4[shop_product_id], $innerwear_list))
		$innerwear_qty += $list4[qty];
	    else if (in_array($list4[shop_product_id], $gift0325_list))
		$gift0325_qty += $list4[qty];
	}

	if ($normal_qty >= 1)
	{
		$gift .= class_D::bubigirl_gift_normal($normal_qty);
		$m_trans_who = class_D::bubigirl_trans_normal($normal_qty);
		if ($m_trans_who) $trans_who = $m_trans_who;
	}

	if ($allinone_qty >= 1)
	{
		$gift .= class_D::bubigirl_gift_allinone($allinone_qty);
		$m_trans_who = class_D::bubigirl_trans_allinone($allinone_qty);
		if ($m_trans_who) $trans_who = $m_trans_who;
	}

	if ($nightgown_qty >= 1)
	{
		$gift .= class_D::bubigirl_gift_nightgown($nightgown_qty);
		$m_trans_who = class_D::bubigirl_trans_nightgown($nightgown_qty);
		if ($m_trans_who) $trans_who = $m_trans_who;
	}

	if ($bigsize_qty >= 3)
	{
		$gift .= class_D::bubigirl_gift_bigsize($bigsize_qty);
		$m_trans_who = class_D::bubigirl_trans_bigsize($bigsize_qty);
		if ($m_trans_who) $trans_who = $m_trans_who;
	}

	if ($gift3900_qty >= 1)
	{
		$gift = class_D::bubigirl_gift_gift3900($gift3900_qty);
	}

	if ($innerwear_qty >= 2)
	{
		$gift = class_D::bubigirl_gift_innerwear($innerwear_qty);
	}

	if ($gift0325_qty >= 1)
	{
		$gift = class_D::bubigirl_gift_gift0325($gift0325_qty);
	}


	if ( $trans_who == "선불") $trans_update = true;

	if ($gift != "")
	{
		$upd_sql = "update orders set gift = '$gift' where pack = '$packno' and seq = '$packno'";
		debug($upd_sql);
		mysql_query($upd_sql, $connect) or die(mysql_error());
	}
   }


  //////////////////////////////////////
  function bubigirl_gift_normal($qty)
  {
	switch ($qty)
	{
	    case  2 :	$gift = "[P]"; break;
	    case  3 :	$gift = "[세,P]"; break;
	    case  4 :	$gift = "[세,P]"; break;
	    case  5 :	$gift = "[세,레,P3]"; break;
	    case  6 :	$gift = "[세,레,부,P2]"; break;
	    case  7 :	$gift = "[세,레,부,잠,P3]"; break;
	    case  8 :	$gift = "[세,레,부,잠,P3]"; break;
	    case  9 :	$gift = "[세,레,부,잠,P4]"; break;
	    default :	$gift = ""; break;
	}
	if ($qty >= 20)	$gift = "[세,레,부,잠,특,P6,묶]";
	else if ($qty >= 10)	$gift = "[세,레,부,잠,특,P6]";

	return $gift;
  }

  //////////////////////////////////////
  function bubigirl_trans_normal($qty)
  {
	if ($qty >= 1) $trans_who = "선불";
	return $trans_who;
  }

  function bubigirl_gift_allinone($qty)
  {
	switch ($qty)
	{
	    case  2 :	$gift = "[거]"; break;
	    case  3 :	$gift = "[거,부]"; break;
	    case  4 :	$gift = "[거,부,공]"; break;
	    default :	$gift = ""; break;
	}
	if ($qty >= 5)	$gift = "[거,부,공,특]";

	return $gift;
  }

  function bubigirl_trans_allinone($qty)
  {
	if ($qty >= 1) $trans_who = "선불";
	return $trans_who;
  }

  /////////////////////////////////////
  function bubigirl_gift_bigsize($qty)
  {
	switch ($qty)
	{
	    case  2 :	$gift = "[P]"; break;
	    case  3 :	$gift = "[세,P]"; break;
	    case  4 :	$gift = "[세,P]"; break;
	    case  5 :	$gift = "[세,레,P3]"; break;
	    case  6 :	$gift = "[세,레,부,P2]"; break;
	    case  7 :	$gift = "[세,레,부,잠,P3]"; break;
	    case  8 :	$gift = "[세,레,부,잠,P3]"; break;
	    case  9 :	$gift = "[세,레,부,잠,P4]"; break;
	    default :	$gift = ""; break;
	}
	if ($qty >= 20)	$gift = "[세,레,부,잠,특,P6,묶]";
	else if ($qty >= 10)	$gift = "[세,레,부,잠,특,P6]";
	return $gift;
  }

  function bubigirl_gift_gift0325($qty)
  {
	if ($qty == 2) $gift = "기";
	else if ($qty >=3 && $qty <= 4) $gift = "세";
	else if ($qty >=5 && $qty <= 6) $gift = "세,P,기";
	else if ($qty >=7 && $qty <= 8) $gift = "세,부,기";
	else if ($qty >=9) $gift = "세,부,기,홈";

	return $gift;
  }

  function bubigirl_gift_gift3900($qty)
  {
	if ($qty > 0 && $qty <= 3) $gift = "[**]";
	else if ($qty > 3 && $qty <= 9) $gift = "[**박]";
	else if ($qty >9 && $qty <= 20) $gift = "[**세,박]";

	return $gift;
  }

  function bubigirl_gift_innerwear($qty)
  {
	if ($qty == 2) $gift = "[거]";
	else if ($qty == 3) $gift = "[거,P]";
	else if ($qty == 4) $gift = "[거,홈]";
	else if ($qty == 5) $gift = "[거,부]";
	else if ($qty >= 6) $gift = "[거,P,홈]";

	return $gift;
  }

  function bubigirl_trans_bigsize($qty)
  {
	if ($qty >= 3) $trans_who = "선불";
	return $trans_who;
  }


  function bubigirl_gift_nightgown($qty)
  {
	$gift = "";

	return $gift;
  }


  function bubigirl_trans_nightgown($qty)
  {
	if ($qty >= 2) $trans_who = "선불";
	return $trans_who;
  }





   //////////////////////////////////////////
   // adong 사용중지시 삭제해야 함
   //////////////////////////////////////////
   function make_trans_pack_adong($packno)
   {
	global $connect; 

	$total_qty = 0;
	$total_qty2 = 0;
	$total_qty3 = 0;
	$total_qty4 = 0;

	$arr_list = array ("09613", "09620", "10457", "09714", "09737", "09867", "24254", "24275", "24291", "24302", "24318", "24334", "09951", "24506", "24900", "24808", "25053", "25473", "25460", "25426", "25419", "25406", "25393", "25380", "25534", "25523", "25486" , "25692");

	//  26161,26177,26193,26209 추가
        // 2009-05-04 
	$arr_list2 = array ( "26161","26177","26193","26209","9800", "9819", "9826", "9833", "9937", "9944", "10030", "10041", "10052", "10283", "10305", "10316", "10327", "10492", "10481", "10150", "10149", "9680", "9679", "2651", "24136", "24350", "24169", "24188", "24221", "24375", "24386", "24424", "24443", "24471", "24490", "09982", "09551", "24824", "24843", "24517" , "24528" , "24547" , "24689" , "24705" , "24894" , "24916" , "24925", "24948" , "24961" , "24939" , "24981" , "24970", "25064", "25085", "25101", "25231", "25250", "25269", "25280", "25299", "25507" , "25439" , "25612", "25916", "25946", "25957", "25968", "25984", "25999", "26107", "26123");

	$arr_list3 = array ("23007", "23221");
	$arr_list4 = array("23619", "23489", "23232", "10481", "23490", "23465", "23476", "23441", "23409", "23378", "23310", "23619", "23705", "23803", "23773", "23988", "24070", "24108");

	  $sql = "select product_id, qty
		    from orders where pack = '$packno'";
	  $result4 = mysql_query($sql, $connect) or die(mysql_error());

	  while ($list4 = mysql_fetch_array($result4))
	  {
	    if (strlen($list4[product_id]) > 5)	// 자체코드 사용
	    {
		$sql = "select org_id from products where product_id = '$list4[product_id]'";
		$list5 = mysql_fetch_array(mysql_query($sql, $connect));
		$product_id = $list5[org_id];
	    }
	    else
		$product_id = $list4[product_id];

	    if (in_array($product_id, $arr_list))
	    {
		$total_qty += $list4[qty];
	    }
	    if (in_array($product_id, $arr_list2))
	    {
		$total_qty2 += $list4[qty];
	    }
	    if (in_array($product_id, $arr_list3))
	    {
		$total_qty3 += $list4[qty];
	    }
	    if (in_array($product_id, $arr_list4))
	    {
		$total_qty4 += $list4[qty];
	    }
	  }

	  if ( $total_qty >= 2 )
	  {
	    $trans_who = "선불";
	    $trans_update = true;
	  }

	  // 3개 이상 선불 : total_qty2
	  // jk 2009-05-04
	  if ( $total_qty2 >= 3 )
	  {
	    $trans_who = "선불";
	    $trans_update = true;
	  }
	  if ( $total_qty3 >= 3 )
	  {
	    $trans_who = "선불";
	    $trans_update = true;
	  }
	  if ( $total_qty4 >= 3 )
	  {
	    $trans_who = "선불";
	    $trans_update = true;
	  }


	return $trans_who;
   }

   //////////////////////////////////////////
   // purdream 사용중지시 삭제해야 함
   //////////////////////////////////////////
   function make_trans_pack_purdream($packno)
   {
	global $connect; 

	$total_amount = 0;
	$arr_nofree = array ("00974","00642","00639","00635","00632","00629","00626","00625","00622","00027","00035","00036","00039", "00334", "00024", "01120");

	  $sql = "select product_id, qty, (shop_price*qty) amount 
		    from orders where pack = '$packno'";
	  $result4 = mysql_query($sql, $connect) or die(mysql_error());

	  while ($list4 = mysql_fetch_array($result4))
	  {
	    if (substr($list4[product_id],0,1) == 'S')
	    {
		$sql = "select org_id from products where product_id = '$list4[product_id]'";
		$list5 = mysql_fetch_array(mysql_query($sql, $connect));
		$product_id = $list5[org_id];
	    }
	    else
		$product_id = $list4[product_id];

	    if (!in_array($product_id, $arr_nofree))
	    {
		$total_amount += $list4[amount];
	    }
	  }

	  if ( $total_amount >= 50000 )
	  {
	    $trans_who = "선불";
	    $trans_update = true;
	  }
	return $trans_who;
   }

   function make_trans_purdream($list, $amount)
   {
	global $connect;

        // 옵션별 발주임..
        $product_id = $list[self_no];
        if ( substr($list[self_no],0,1) == "S" )
        {
          $sql = "select org_id from products where product_id = '$list[self_no]'";
          $list4 = mysql_fetch_array(mysql_query($sql, $connect));
          $product_id = $list4[org_id];
        }

	// 무조건 선불
	$arr_nofree = array ("00974","00642","00639","00635","00632","00629","00626","00625","00622","00027","00035","00036","00039", "00334", "00024", "01120");

	if (!in_array($product_id, $arr_nofree))
	{
		if ($amount >= 50000) $trans_who = "선불";
	}

	return $trans_who;
   }

}
?>
