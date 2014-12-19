<?
require_once "class_top.php";
require_once "class_B.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_file.php";
require_once "class_combo.php";
require_once "class_stock.php";
require_once "class_product.php";
require_once "class_ui.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_CK00 extends class_top
{
   function CK09()
   {
      global $template, $id, $d_date;

      $data = $this->get_detail( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // image detail view or del popup
   function CK04()
   {
      global $template;
      global $id,$param, $top_url;

//echo "top: $top_url";

      // 상세 정보 가져온다
      $data = $this->get_detail( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // disp match table information
   function CK05()
   {
      global $template;
      global $connect;
      global $id,$param, $link_url;

      // match list
      $query = "select * from code_match a, shopinfo b 
                 where a.shop_id = b.shop_id
                   and a.id='$id'";
//echo $query;

      $result = mysql_query ( $query, $connect ); 

      // shop list
      $shop_result = class_B::get_shop_list();

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   
   // disp match table information
   function CK07()
   {
      global $template;
      global $connect;
      global $id,$param;

      $query = "select * from products
                 where stock_manage=1
                   and org_id='$id'";

      $result = mysql_query ( $query, $connect ); 

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
    
   // stock manage copy popup
   function CK12()
   {
      global $template;
      global $id,$top_url;

      $val_item = array ( "supply_code"=>"공급처 코드" );
      $this->validate ( $val_item );

      // 상세 정보 가져온다
      $data = $this->get_detail( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // copy popup
   function CK02()
   {
      global $template;
      global $id;

      $val_item = array ( "supply_code"=>"공급처 코드" );
      $this->validate ( $val_item );

      // 상세 정보 가져온다
      $data = $this->get_detail( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
 
   // price popup 
   function CK10()
   {
      global $template, $id;

      $end_date= date('Y-m-d', strtotime('2 year'));

      // $val_item = array ( "supply_code"=>"공급처 코드" );
      // $this->validate ( $val_item );

      // 상세 정보 가져온다
      $data = $this->get_supply_code( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   # 판매 불가 처리
   function block_sale( $product_id )
   {
	global $connect;
	$query = "update products set enable_sale=0, sale_stop_date=Now() where product_id='$product_id'";
	mysql_query ( $query , $connect );
   }
 
   function get_supply_code( $id )
   {
      global $connect;

      $query = "select supply_code from products where product_id='$id'";
      $result = mysql_query ( $query, $connect );
      return mysql_fetch_array( $result );
   }
   
 
   // price popup 
   function CK11()
   {
      global $template;


      $val_item = array ( "supply_code"=>"공급처 코드" );
      $this->validate ( $val_item );

      // 상세 정보 가져온다
      $data = $this->get_price_detail();

      $shop_id = $data[shop_id];
      $start_date = $data[start_date];

      if ( $data[end_date] == "0000-00-00" )
          $end_date= date('Y-m-d', strtotime('2 year'));
      else 
          if ( !$data[end_date] )
              $end_date= date('Y-m-d', strtotime('2 year'));
          else
              $end_date = $data[end_date];

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   /////////////////////////////////
   // 벤더를 위한 상품 리스트 
   // date: 2005.12.30
   function CK15()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();     
    
      if ( $_REQUEST["page"] )
         $result = $this->get_list( &$total_rows, $page );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   //////////////////////////////////////
   // 프린트를 위한 상품 리스트 
   // date: 2006.1.2
   function CK16()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();     
    
      $result = $this->get_list( &$total_rows, $page );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // 상품 리스트 
   function CK00()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();     
    
      if ( $_REQUEST["page"] )
         $result = $this->get_list( &$total_rows, $page );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   //////////////////////////////////////////////
   // 상품 변경 폼
   // 벤더를 위한 상품 변경 폼
   function CK13()
   {
      global $template;
      global $id, $connect;

      $link_url = "?" . $this->build_link_url();     
      /////////////////////////////////////
      $option_list = $this->option_list($id);
      // $obj_combo = new category_combo($connect, "tbl_category","myform","","", $option_list);

      // 상세 정보 가져온다
      $data = $this->get_detail( $id );

      if ( $data[stock_manage] )
      {
         global $top_url;
         $this->redirect("?template=CK14&id=$id" . "&top_url=$top_url");
         exit;
      }

      // copy 상품의 정보 가져온다.
//    $result = $this->get_copy_list( $id );

      // price table의 정보 가져온다.
      $result_price = $this->get_price_history( 1 );

      // $obj_combo->disp_script_data();	

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";

      // $obj_combo->disp_script_engine();
   }


   // 상품 변경 폼
   function CK01()
   {
      global $template;
      global $id, $connect, $top_url, $link_url;

//echo "top: $top_url ";

      $link_url = "?" . $this->build_link_url();     

      /////////////////////////////////////
      $option_list = $this->option_list($id);
      // $obj_combo = new category_combo($connect, "tbl_category","myform","","", $option_list);

      // 상세 정보 가져온다
      $data = $this->get_detail( $id );

      if ( $data[stock_manage] )
      {
         global $top_url;
         $this->redirect("?template=CK08&id=$id&top_url=$top_url");
         exit;
      }

      // copy 상품의 정보 가져온다.
//    $result = $this->get_copy_list( $id );

      // price table의 정보 가져온다.
      $result_price = $this->get_price_history();

      // $obj_combo->disp_script_data();	

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";

      // $obj_combo->disp_script_engine();
   }

   //==================================================================
   //
   // 상품 복사
   // date: 2007.11.7
   //
   function copy_product( $_product_id = 0 )
   {
	global $product_id, $top_url, $template;
	$product_id = $product_id ? $product_id : $_product_id;

	$obj = new class_product();
	$new_product_id = $obj->copy_product($product_id);

	$this->jsAlert("상품 추가 되었습니다.");
	$this->redirect("?template=CK01&id=$new_product_id&top_url=$top_url");
   }
   //========================================
   // 
   // date: 2007.5.9 옵션 수정
   //
   function changeOption()
   {
	global $connect, $product_id, $org_id, $option_id, $options, $name, $enable_sale, $barcode;

	$name    = iconv("UTF-8", "CP949", $name);
	$options = iconv("UTF-8", "CP949", $options);
	$options = addslashes ( $options );
        $transaction    = $this->begin(" 상품변경 $product_id $org_id-> $option_id / $optins");

	$query = "update products set product_id='$option_id', barcode='$barcode', options='$options',name='$name',enable_sale='$enable_sale' where product_id='$org_id'";
	mysql_query ( $query, $connect );

// debug ( $query );
//echo $query;

	//================================
	// 3pl을 위한 부분
	// date: 2007.11.12 - jk
	if ( $_SESSION[USE_3PL] )
	{
		$obj = new class_3pl();
		$obj->option_update ( $org_id, $option_id );
	}

	$option_id="변경완료";
	$this->listoption();
   }

   //=========================================
   //
   // date: 2007.5.7 옵션 리스트
   //
   function listoption( )
   {
	global $connect, $product_id, $option_id;

        //copy 상품의 정보 가져온다.
        $result = $this->get_stock_list( $product_id );
?>
	<table border=0 cellpadding=0 cellspacing=1  bgcolor="666666" width="100%">
            <tr>
              <td class=header1 width=10%>상품 CODE</td>
              <td class=header1 width=190>바코드</td>
              <td class=header1 >상품명</td>
              <td class=header1 width=20%>옵션</td>
              <td class=header1 width=10%>갱신일</td> 
              <td class=header1 width=10%>상태</td> 
              <td class=header1 width=10%>메뉴</td>
        </tr>
        <?
        while ( $data = mysql_fetch_array ( $result )) 
        {
		if ( $data[product_id] == $option_id )
		{
		?>
			<tr bgcolor="#FFFFFF">
			      <td bgcolor="ffffff" align=center height=30>
				<input type=hidden name="_product_id" value='<?= $data[org_id] ?>'>
				<input type=hidden name="_org_id" value='<?= $data[product_id] ?>'>
				<input type=text name="_option_id" class=input style='align:ceneter' value='<?= $data[product_id] ?>' size=7></td>
			      <td bgcolor="ffffff">
				&nbsp;<input type=text name="_barcode" class="input" value="<?= $data[barcode]?>" size=20>
                              </td>
			      <td bgcolor="ffffff">
				&nbsp;<input type=text name="_name" class="input" value="<?= $data[name]?>" size=30>
                              </td>
			      <td bgcolor="ffffff">&nbsp;
				<input type=text name="_options" value="<?= $data[options]?>" class=input size=30></td>
			      <td bgcolor="ffffff" align=center width=100>&nbsp;<?= substr( $data[last_update_date],0,10 ) ?></td>
			      <td bgcolor="ffffff" align=center width=100>
			      <select name="_enable_sale">
				<option value="1" <?= $data[enable_sale]?"selected":"" ?>>판매가능</option>
				<option value="0" <?= $data[enable_sale]?"":"selected" ?>>판매불가</option>
                              </select>
                              </td>
			      <td bgcolor="ffffff" align=center width=100>
			      <a href="javascript:change_option()" class=btn3><span class=red>변경</span></a>&nbsp;
			      </td>
			</tr>  
		<?
		}
		else
		{
		?>
			<tr bgcolor="#FFFFFF" onClick="javascript:dispOptionList('<?= $data[product_id]?>')" OnMouseOver="swapClass(this, 'over')" OnMouseOut="swapClass(this, 'roll')">
			      <td bgcolor="ffffff" align=center height=30><?= $data[product_id] ?></td>
			      <td bgcolor="ffffff" align=center height=30><?= $data[barcode] ?></td>
			      <td bgcolor="ffffff" height=25>&nbsp;<?= $data[name]?></td>
			      <td bgcolor="ffffff">&nbsp;<?= $data[options]?></td>
			      <td bgcolor="ffffff" align=center width=100>&nbsp;<?= substr( $data[last_update_date],0,10 ) ?></td>
			      <td bgcolor="ffffff" align=center width=100><?= $data[enable_sale]?"판매가능":"<span class=red>판매불가</span>" ?></td>
			      <td bgcolor="ffffff" align=center width=100>
			      <a href="javascript:del_option('<?= $data[product_id] ?>')" class=btn3>삭제</a>&nbsp;
			      </td>
			</tr>  
        	<?
		}
        }
        ?>
      </table>
<?
   }

   function option_list( $id )
   {
      global $connect;
      $option_list = array ();
      $query = "select cid from tbl_product_category where pid='$id'";
      $result = mysql_query ( $query, $connect );
      
      while ( $data = mysql_fetch_array ( $result ) )
         array_push( $option_list, $data[cid] );

      return $option_list;
   }
 
   //////////////////////////////////////////////
   // 벤더를 위한 상품 변경 폼 ( 재고관리)
   function CK14()
   {
      global $template;
      global $id;

      $link_url = "template.htm?" . $this->build_link_url();

      // 상세 정보 가져온다
      $data = $this->get_detail( $id );

      if ( $data[org_id] )
      {
          $id = $data[org_id];
          $data = $this->get_detail( $data[org_id] );
      }

      // copy 상품의 정보 가져온다.
      $result = $this->get_stock_list( $id );

      // price table의 정보 가져온다.
      $result_price = $this->get_price_history();

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // 상품 변경 폼 ( 재고관리)
   function CK08()
   {
      global $template;
      global $id,$top_url;

      $link_url = "template.htm?" . $this->build_link_url();

      // 상세 정보 가져온다
      $data = $this->get_detail( $id );

      if ( $data[org_id] )
      {
          $id = $data[org_id];
          $data = $this->get_detail( $data[org_id] );
      }

      $data[product_desc] = htmlspecialchars( $data[product_desc] );

      // copy 상품의 정보 가져온다.
      //$result = $this->get_stock_list( $id, $data[is_delete] );

      // price table의 정보 가져온다.
      $result_price = $this->get_price_history();

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // 상품 변경 폼 ( 재고 관리 폼)
   function CK06()
   {
      global $template;
      global $id;


      // 상세 정보 가져온다
      $data = $this->get_detail( $id );

      // copy 상품의 정보 가져온다.
      $result = $this->get_copy_list( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function CK03()
   {
      global $template;
      global $id, $org_id;

      // 상세 정보 가져온다
      $data = $this->get_detail( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function match_input()
   {
      global $connect;
      global $product_id, $shop_id, $shop_pid;

      $transaction = $this->begin("매치테이블등록");

      class_C::match_input();

      $this->end( $transaction );
   }

   function match_delete ()
   {
      global $connect;
      global $product_id, $shop_id, $shop_pid;
      
      class_C::match_delete();
   }

   //////////////////////////////
   // 상품 상세 정보
   function get_detail ( $id )
   {
      global $connect;
      return class_C::get_detail ( $id );
   }

   //////////////////////////////
   // 상품 리스트
   function get_list( &$max_row, $page, $download=0 )
   {
      global $connect;
      global $string, $disp_copy, $enable_sale, $group_id;

      if ( !$group_id ) 
         return class_C::get_list ( &$max_row, $page, $download );
      else
         return class_C::get_list2 ( &$max_row, $page, $download );
   }   

   /////////////////////////////////////
   // copy list
   function get_copy_list ( $id )
   {
      global $connect;
      return class_C::get_copy_list ( $id );
   }

   function get_stock_list ( $id, $is_delete=0 )
   {
      global $connect;

      $query = "select * from products 
                 where is_delete = ";
      $query .= $is_delete ? $is_delete : 0;
      $query .= " and org_id = '$id'
                   and stock_manage = 1";

      $result = mysql_query( $query, $connect );

      return $result;
   }

   function init_product_reg()
   {
	$this->items = array("name","origin","brand","org_price","supply_price","shop_price","is_free_deliv","supply_code",
                           "options","product_desc", "enable_sale", "tax", "market_price", "trans_fee");

        if ( _DOMAIN_ == "sabina" )
            $this->items[] = "trans_code";
	//=============================================
	//
	// 3pl을 사용할 경우에만 use_3pl을 추가 함 - jk.ryu
	//
	if ( $_SESSION["USE_3PL"] )
	    array_push ( $this->items, "use_3pl" );

	$this->val_items = array("name"=>"상품명", "org_price"=>"원가", "supply_price"=>"공급가","supply_code"=>"공급처" );
   }
 
   // copy
   function add_options()
   {
      global $connect, $org_id;

      $transaction = $this->begin("옵션 추가");

      // id 생성 
      $table_name = "products";
      $query = "select max(max) m from $table_name";
      $result = mysql_query($query);
      $data = mysql_fetch_array($result);
      $max = $data[m] + 1;
      $id = $id_index . sprintf("%05d", $max);
      $id = "S" . $id;

      // 기존 data 가져오는 작업
      $query = "select * from products where product_id='$org_id'";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_array($result);
 
      $org_items = array ("name", "origin", "brand", "img_500", "img_desc1", "img_desc2","img_desc3", "img_desc4", "img_desc5", "img_desc6",
                   "product_desc","org_price", "supply_price", "shop_price", "is_free_deliv", "supply_code", "enable_sale" );

	//=============================================
	//
	// 3pl을 사용할 경우에만 use_3pl을 추가 함 - jk.ryu
	//
	if ( $_SESSION["USE_3PL"] )
	    array_push ( $org_items, "use_3pl" );

      $copy_items = array ("options", "org_id"); 

      // query 만드는 부분      
      $query = "insert into products set product_id='$id', reg_date=Now(), reg_time=Now(), ";

      foreach ( $copy_items as $item )
      {
         global $$item;
         $query .= "$item = '" . addslashes($$item) . "',";
      }

      $i = 1;
      foreach ( $org_items as $item )
      {
         // get supply code from db 
         if ( $item == "supply_code" ) $supply_code = $data[$item];

         $query .= "$item = '" . addslashes($data[$item]) . "'";
         if ( $i != count( $org_items ) )
            $query .= ",";
         $i++;
      }

      $query .= ", max=$max, stock_manage=1";

//echo $query;
//exit;

      mysql_query ( $query, $connect );
      
      //////////////////////////////////////////
      // 가격 table에 값 추가
      $query = "insert into price_history set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax', product_id='$id', start_date=Now(), update_time=Now()";
      mysql_query ( $query, $connect );

     //--------------------------------------
     // jaego table에 값 입력
     if ( _DOMAIN_ == "ckcompany" )
     {
	     $obj = new class_stock();
	     $obj->insert_jaegolist($id);
     }

        //=====================================
	// 뭐가 이리 기냐~~??
	// update products infos form 3pl
	// date: 2007.11.12
	if ( $_SESSION[USE_3PL] )
	{
		$obj = new class_3pl();
		$obj->product_reg( $id );
	}

     $this->end( $transaction );

     // self.close 한 후 opener의 location을 변경해야 
     global $top_url;
     $this->opener_redirect ( "template.htm?template=CK08&id=$org_id&top_url=$top_url" );
     $this->jsAlert("옵션 추가 되었습니다.");
     $this->closewin(); 

   }

  

   // copy
   function copy()
   {
      global $connect, $org_id;

      $transaction = $this->begin("복사");

      // id 생성 
      $table_name = "products";
      $query = "select max(max) m from $table_name";
      $result = mysql_query($query);
      $data = mysql_fetch_array($result);
      $max = $data[m] + 1;
      $id = $id_index . sprintf("%05d", $max);
      $id = "C" . $id;

      // 기존 data 가져오는 작업
      $query = "select * from products where product_id='$org_id'";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_array($result);
 
      $org_items = array ("origin", "brand", "img_500", "img_desc1", "img_desc2",
                   "img_desc3", "img_desc4", "img_desc5", "img_desc6","product_desc");

      $copy_items = array ("name","org_price", "supply_price", "shop_price", "is_free_deliv", "org_id", "supply_code"); 

      // query 만드는 부분      
      $query = "insert into products set product_id='$id', reg_date=Now(), reg_time=Now(), ";

      foreach ( $copy_items as $item )
      {
         global $$item;
         $query .= "$item = '" . addslashes($$item) . "',";
      }

      $i = 1;
      foreach ( $org_items as $item )
      {
         // get supply code from db 
         if ( $item == "supply_code" ) $supply_code = $data[$item];

         $query .= "$item = '" . $data[$item] . "'";
         if ( $i != count( $org_items ) )
            $query .= ",";
         $i++;
      }

      $query .= ", max=$max";

      mysql_query ( $query, $connect );
      
      //////////////////////////////////////////
      // 가격 table에 값 추가
      $query = "insert into price_history set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax', product_id='$id', start_date=Now(), update_time=Now()";
      mysql_query ( $query, $connect );

     // 재고 입력
     //--------------------------------------
     // jaego table에 값 입력
     if ( _DOMAIN_ == "ckcompany" )
     {
	     $obj = new class_stock();
	     $obj->insert_jaegolist($id);
     }

     $this->end( $transaction );

     // self.close 한 후 opener의 location을 변경해야 
     global $top_url;
     $this->opener_redirect ( "template.htm?template=CK01&id=$org_id&top_url=$top_url" );
     $this->jsAlert("복사 되었습니다.");
     $this->closewin(); 

   }

   //////////////////////////////
   // 정보 변경
   function modify()
   {
      global $connect;
      global $id,$org_id;

      $id = $org_id;

      $transaction = $this->begin( "상품변경" );

      $this->init_product_reg();

      /////////////////////////////////////////////////////
      // 해당 상품이 stock manage use인지 여부를 알아야 함
      $query = "select stock_manage,org_id from products where product_id='$id'";
      $result = mysql_query ( $query,$connect );
      $data = mysql_fetch_array ( $result );
      $stock_manage = $data[stock_manage];

      if ( $data[org_id] )
         $id = $data[org_id];

      // image 저장
      $images = array( "img_500", "img_desc1", "img_desc2","img_desc3","img_desc4","img_desc5","img_desc6" );

      //============================================
      //
      // 하부 업체가 상품 정보를 변경할 경우 판매 불가
      // date: 2007.4.13 - jk.ryu
      //
      if ( !$_SESSION[LOGIN_LEVEL] )
          $this->block_sale( $id );

      ///////////////////////////////////////////////////////////////
      // query 생생
      $query = "update products set last_update_date=Now()";

      foreach( $images as $item )
      {
           global $$item;
           $index = split("_", $item) ;

           $key = $item . "_name";
           global $$key;
           if ( $$key )
           {
              $filename = class_file::save($$item, $$key, $id, $index[1]);
              $query .= ", $item = '$filename'";
           }
      }

      $i = 1;
      foreach( $this->items as $item )
      {
           global $$item;
           ////////////////////////////////////////////
           // 재고관리 중(옵션별 발주)일경우 options가 사라진다
           if ( $stock_manage && $item == "options" )
               continue;

           if ( $$item || 
		$item == "enable_sale" || 	// 값이 0 일때도 처리 해야 함
		$item == "product_desc" ||
		$item == "use_3pl"
		)  
              $query .= ", $item='" . addslashes( $$item ) . "'";
      }

      /////////////////////////////////////////
      // 저장
      $query .= " where product_id = '$id'";

      mysql_query( $query, $connect );

      if ( $supply_code )
      { 
          ///////////////////////////////////////////////////
          // 복사본의 supply_code도 모두 변경해야 한다. 
          $query = "update products set supply_code = '$supply_code' where org_id='$id'"; 
          mysql_query( $query, $connect );

          ///////////////////////////////////////////////////
          // price_history의 supply_code도 모두 변경해야 한다. 
          $query = "update price_history set supply_code = '$supply_code', update_time=Now() where product_id='$id'"; 
          mysql_query( $query, $connect );
      }

	// 3pl관련 update
	if ( $_SESSION[USE_3PL] )
	{
            $query = "update products set use_3pl= '$use_3pl' where org_id='$id'"; 
	    mysql_query ( $query, $connect ); 
	}

      ///////////////////////////////////////////////
      // 가격 변경 여부 저장
      if ( $_REQUEST["change_price"] )
      {
         $query = "update code_match set modify_code = 3 where id = '$id'";
         $transaction = $this->end( $transaction, "가격변경" );
      }
      else
         $query = "update code_match set modify_code = 4 where id = '$id'";

      mysql_query ( $query );

      //////////////////////////////////////////////////////////
      // sale_stop_date or sale_start_date 입력
      $org_enable_sale = $_REQUEST["org_enable_sale"];
      $enable_sale = $_REQUEST["enable_sale"];

      if ( $org_enable_sale == 1 && $enable_sale == 0 )
      {
         $query = "update products set sale_stop_date = Now() where product_id = '$id'";
         mysql_query( $query, $connect );
         $query = "update code_match set modify_code = 1 where id = '$id'";
         mysql_query( $query, $connect );

         // 품절 처리
         $transaction = $this->end( $transaction, "품절처리" );
      }
      else if ( $org_enable_sale == 0 && $enable_sale == 1 )
      {
         $query = "update products set sale_start_date = Now() where product_id = '$id'";
         mysql_query( $query, $connect );
         $query = "update code_match set modify_code = 2 where id = '$id'";
         mysql_query( $query, $connect );

         // 판매 가능
         $transaction = $this->end( $transaction, "판매가능" );
      }
      else
      {

      }


      /////////////////////////////////////////
      // tbl_product_category table에 값 추가
      $query = "delete from tbl_product_category where pid='$id'";
      mysql_query( $query, $connect );

      for( $i=1 ; $i<=4 ; $i++ )
      {
         $key = "option" . $i;
         global $$key;
         $value = $$key;
         if( $value )
         {
            $query = "insert into tbl_product_category set pid='$id', cid='$value'";
            mysql_query( $query, $connect );
         }
         else
            break;
      }

      if ( $transaction )
         $this->end( $transaction );

      /////////////////////////////////////////////
      // set child products are disabled or not
      global $total_disable_sale;
//echo "tot->$total_disalbe_sale<br>";

      if ( $total_disable_sale )
      {
         $query = "update products set enable_sale='$enable_sale', last_update_date = Now(), current_stock=0 where org_id = '$id'";
         mysql_query( $query, $connect );
      }

      ////////////////////////////////////////////////////////////////////////////
      // 사입처 상품명
      if ( $brand )
      {
         $query = "update products set brand='$brand' where org_id = '$id'";
         mysql_query( $query, $connect );
      }

      ///////////////////////////////////////////// 
      // 묶음상품인경우 해당정보를 UPDATE한다.sy.hwang 2006.7.17
      $sql = "select * from products where product_id = '$id'";
      $list = mysql_fetch_array(mysql_query($sql, $connect));
      if ($list[packed])
      {
	$pack_mgr = $_REQUEST[pack_mgr];
	$p1 = $_REQUEST[p1];
	$p2 = $_REQUEST[p2];
	$p3 = $_REQUEST[p3];
	$p4 = $_REQUEST[p4];
	$p5 = $_REQUEST[p5];

	// 상품을 7개까지 늘림 jk.ryu 2008.1.14
	$p6 = $_REQUEST[p6];
	$p7 = $_REQUEST[p7];

	// 상품을 12개 까지 늘림 jkryu 2008.2.13
	$p8 = $_REQUEST[p8];
	$p9 = $_REQUEST[p9];
	$p10 = $_REQUEST[p10];
	$p11 = $_REQUEST[p11];
	$p12 = $_REQUEST[p12];

	$pack_list = $p1.",".$p2.",".$p3.",".$p4.",".$p5.",".$p6.",".$p7.",$p8,$p9,$p10,$p11,$p12";
	$upd_sql = "update products set
			pack_mgr = '$pack_mgr',
			pack_list = '$pack_list'
		     where product_id = '$id'";
	mysql_query($upd_sql, $connect) or die(mysql_error());
      }

      ///////////////////////////////////////////// 
      // 자식 상품의 이름 변경
      $query = "update products set name='" . addslashes( $name ) . "' where org_id='$id'";
      mysql_query( $query, $connect );

      global $template;
//echo $template;
//exit;
      if ( $template != "CK14" )
	      if ( $stock_manage )
		 $redirect = "CK08";
	      else
		 $redirect = "CK01";
      else
              $redirect = $template;

	//=====================================
	// 뭐가 이리 기냐~~??
	// update products infos form 3pl
	// date: 2007.11.12
	if ( $_SESSION[USE_3PL] )
	{
		$obj = new class_3pl();
		$obj->product_update ( $id );
	}

      global $top_url;
      $this->redirect("?template=$redirect&id=$id&top_url=$top_url");
      $this->jsAlert("변경 작업 완료");
      exit;
   }

   /////////////////////////////////////
   // copy modify
   function copy_modify()
   {
      global $connect;
      global $org_id, $id;

      $copy_items = array ("name", "org_price", "supply_price", "shop_price", "is_free_deliv", "org_id", "supply_code", "enable_sale");

      // query 만드는 부분      
      $query = "update products set ";

      $i = 1;
      foreach ( $copy_items as $item )
      {
         global $$item;
         $query .= "$item = '" . addslashes($$item) . "'";
         if ( $i != count ( $copy_items ))
            $query .= ",";
         $i++;
      }
      $query .= " where product_id = '$id'";
      
      mysql_query ( $query, $connect);
     
     // self.close 한 후 opener의 location을 변경해야 
     global $top_url;
     $this->opener_redirect ( "template.htm?template=CK01&id=$org_id&top_url=$top_url" );
     $this->jsAlert("변경 되었습니다.");
     $this->closewin(); 
   }

   function copy_delete()
   {
      global $connect, $org_id, $id;

      $query = "update products set is_delete = 1 where product_id='$id'";
      mysql_query ($query, $connect );

     // self.close 한 후 opener의 location을 변경해야 
     global $top_url;
     $this->opener_redirect ( "template.htm?template=CK01&id=$org_id&top_url=$top_url" );
     $this->jsAlert("삭제 되었습니다.");
     $this->closewin(); 

   }
  
   function img_delete()
   {
      global $connect, $img, $id, $param;
      global $top_url;

//echo "top: $top_url <br>";

      // update db
      $query = "update products set $param = '' where product_id='$id'";
      mysql_query ( $query, $connect );

      // delete file 
      class_file::del ( $img );

      // 대표 이미지 삭제 할 경우 thumb nail도 함께 삭제 한다.
      // 2008.5.6 - jk
      if ( $param == "img_500" )
      {
	  $img = str_replace( "_500", "_100", $img );
          class_file::del ( $img );
      }

      $this->opener_redirect ( "template.htm?template=CK01&id=$id&top_url=$top_url" );
      $this->jsAlert("삭제 되었습니다.");
      $this->closewin(); 
   }

   //////////////////////////////////////////
   // match data를 지우지 않음
   // 2005.12.22
   // match data를 지우는 버젼을 변경
   function product_delete()
   {
      global $connect;

      $transaction = $this->begin("상품삭제");

      // $data = $this->get_detail( $_POST[id] );
      // 이미지 삭제 - 원본을 삭제할 경우에만 이미지를 삭제 함
      /*
      if ( !$data[org_id] ) 
      {
         $arr_img = array( "img_500", "img_desc1", "img_desc2", "img_desc3", "img_desc4" );

         foreach ( $arr_img as $img )
         {
            $img = $data[$img];

            if ( $img )
               class_file::del ( $img );
         } 

         // 복사본 삭제
         $query = "delete from products where org_id = '$_POST[id]'";
         mysql_query ( $query, $connect );
      }
      */

      ////////////////////////////////// 
      // code match 삭제
      $query = "delete from code_match where id= '$_POST[id]'";
      mysql_query ( $query, $connect );

      ////////////////////////////////// 
      // name match 삭제
      $query = "delete from name_match where id= '$_POST[id]'";
      mysql_query ( $query, $connect );

      // 원본 삭제
      // $query = "delete from products where product_id = '$_POST[id]'";
      $query = "update products set is_delete=1, delete_date=Now() where product_id = '$_POST[id]'";
      mysql_query ( $query, $connect );

      // 옵션 삭제
      $query = "update products set is_delete=1, delete_date=Now() where org_id = '$_POST[id]'";
      mysql_query ( $query, $connect );
 
      $transaction = $this->end( $transaction );

      global $top_url, $link_url;

      $this->redirect ( $link_url . "&top_url=$top_url" );
      exit;
   }
  
   /////////////////////////////////////////
   // stock_build
   function stock_build()
   {
	global $product_id, $connect;
	$query = "select options,name from products where product_id='$product_id'";
	$result = mysql_query( $query, $connect );
	$data = mysql_fetch_array ( $result );
	//$options = str_replace( array("\n", "\r", "\r\n"),"", $data[options] );
        class_C::stock_build( $data[name], $data[options] );
   }

   function stock_delete()
   {
      global $connect, $org_id;
      $id = $org_id;

      $transaction = $this->begin( "옵션별 관리 취소" , $id);

      // 주문이 있는지 여부를 확인한다.
      $query = "select product_id from products where org_id='$id'";
      $result = mysql_query ( $query, $connect );
      $i = 0;
      while ( $data = mysql_fetch_array ( $result )) 
      {
          if ( $i ) $ids .= ", ";
          $ids .= "'". $data[product_id] . "'";
          $i++;
      }

      $query = "select count(*) cnt from orders where product_id in ( $ids )";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      global $top_url;
      //**************************************************
      // 매칭된 주문이 있으면 상품의 옵션을 풀 수 없다
      if ( $data[cnt] )
      {
	      $this->jsAlert ( "매칭된 정보가 있어서 상품 옵션을 삭제 할 수 없습니다");
              $this->redirect ( "?template=CK08&id=$id&top_url=$top_url" );
      }
      else
      //**************************************************
      // 매칭된 주문이 없으면 상품의 옵션을 풀 수 있다
      {
	      $query = "update products set stock_manage=0 where product_id='$id'";
	      mysql_query ( $query, $connect );

	      // 매칭을 삭제??
	      $query = "delete from products where org_id='$id' and stock_manage=1";
	      mysql_query ( $query, $connect );
      	      $this->redirect ( "?template=CK01&id=$id&top_url=$top_url" );
      }
      
      $this->end( $transaction);
      exit;
   }

   function stock_change()
   {
      global $connect, $safe_stock, $current_stock, $id;

      $transaction = $this->begin( "재고 변경" , $id);
      // safe stock
      foreach ( $safe_stock as $product_id=>$value )
      {
         $query = "update products set safe_stock = $value where product_id='$product_id'";
         mysql_query ( $query , $connect );
      }

      // current stock
      foreach ( $current_stock as $product_id=>$value )
      {
         $query = "update products set current_stock = $value, last_update_date=Now() ";

         if ( $value >= 0 )
            $query .= ", enable_sale=1";
         else
            $query .= ", enable_sale=0";

         $query .= " where product_id='$product_id'";

         mysql_query ( $query , $connect );
      }

      // 상태 변경
      $query = "update products set enable_sale = 1 where org_id = '$id' and current_stock > 0";
      mysql_query ( $query, $connect );

      $this->end( $transaction);
      global $top_url;
      $this->redirect("?template=CK08&id=$id&top_url=$top_url"); 
   }
   
   function download()
   {
      global $connect, $saveTarget;

      $transaction = $this->begin("상품 다운로드 (CK00)");

      $handle = fopen ($saveTarget, "w");

      $download = 1;
      $result = $this->get_list( &$total_rows , 0, $download );

      ////////////////////////////////////////
      // writing datas to file
      $i = 1;
 
      $download_items = array ( "아이디"=>"product_id",
                                "상태"=>"enable_sale",
                                "상품명"=>"name",
                                "옵션"=>"options",
                                "공급처"=>"supply_code",
                                "원가"=>"org_price",
                                "판매가"=>"shop_price",
                                "상품설명"=>"product_desc",
                                "품절"=>"enable_sale",
                              );

      while ( $data = mysql_fetch_array ( $result ) )
      {
         if ( $i == 1 )
         {
            foreach ( $download_items as $key=>$value )
               $buffer .= $key . "\t";
            $buffer .= "\n";
         }

         foreach ( $download_items as $value )
         {
            switch ( $value )
            {
               case "supply_code":
                  $buffer .= $this->get_supply_name2 ( $data[$value] ) . "\t";
               break;
               case "enable_sale";
                  $buffer .=  $data[enable_sale] ? "판매가능"."\t" : "판매불가"."\t";
               break;
               default:
                  $val = $data[$value] ? $data[$value] : ".";
                  $buffer .= str_replace( array("\r", "\n", "\r\n","\t" ), " ", $val ) . "\t";
               break;
            }
         }

         fwrite($handle, $buffer . "\n" );
         $buffer = "";
         $i++;
      }

      // file 삭제
      fclose($handle);

      if (is_file($saveTarget)) {
          $fp = fopen($saveTarget, "r");
          fpassthru($fp);
      }

      ////////////////////////////////////// 
      // file close and delete it 
      fclose($fp);
      unlink($saveTarget);

      $transaction = $this->end( $transaction );

      exit;
   }

   //////////////////////////////////////////////////////////////
   // option이 1일 경우는 하부 업체에서 가격을 가져와야 할 경우
   function get_price_history( $option=0 )
   {
      global $connect, $id;

      $query = "select * from price_history where product_id='$id' ";

      if ( !$_SESSION[LOGIN_LEVEL] )
          $query .= " and supply_code = '" . $_SESSION[LOGIN_CODE] . "'";

      if ( !$option ) 
         $query .= " order by seq desc";
      else
         $query .= " order by seq limit 1";

      $result = mysql_query ( $query , $connect );

      return $result;
   }

   function get_price_detail()
   {
      global $connect, $seq;

      $query = "select * from price_history where seq='$seq'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      return $data; 
   }

   function add_price()
   {
      global $connect, $product_id, $start_date, $end_date, $link_url;
      global $shop_id, $supply_code, $org_price, $supply_price, $shop_price, $is_free_deliv, $tax;
 
      // end data가 없는 row에 end_date입력
      /*
      $query = "select max(seq) m from price_history where product_id='$product_id'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $max_seq = $data[m];
      $query = "update price_history set end_date='$start_date' where seq='$max_seq'";
      mysql_query ( $query, $connect );
      */

      //============================================
      //
      // 하부 업체가 상품 정보를 변경할 경우 판매 불가
      // date: 2007.4.13 - jk.ryu
      //
      if ( !$_SESSION[LOGIN_LEVEL] )
          $this->block_sale( $product_id );
 
      $query = "insert into price_history set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax', product_id='$product_id', start_date='$start_date', end_date='$end_date', shop_id='$shop_id', update_time=Now()";
      mysql_query ( $query, $connect );

      // product_table변경
      // 하부 상품의 정보 변경
      // jk.ryu - 2006.8.1 
       $query = "update products set org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax' 
                where product_id='$product_id' or org_id='$product_id'";
      mysql_query ( $query , $connect );

//echo $query;
//exit;

      $self_url = "?template=CK10&id=$product_id&link_url=$link_url";

      $link_url = $link_url ? base64_decode( $link_url ) : "template.htm?template=CK01&id=$product_id"; 

      global $top_url;
      $this->opener_redirect( $link_url . "&top_url=$top_url" );

      $this->redirect( $self_url );
      // $this->closewin();

   }

   function modify_price()
   {
      global $connect, $product_id, $start_date, $end_date, $seq, $link_url;
      global $shop_id, $supply_code, $org_price, $supply_price, $shop_price, $is_free_deliv, $tax;

      // end data가 없는 row에 end_date입력
      /*
      $query = "select max(seq) m from price_history where product_id='$product_id' and seq < $seq";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $max_seq = $data[m];
      $query = "update price_history set end_date='$start_date' where seq='$max_seq'";
      mysql_query ( $query, $connect );
      */
      //============================================
      //
      // 하부 업체가 상품 정보를 변경할 경우 판매 불가
      // date: 2007.4.13 - jk.ryu
      //
      if ( !$_SESSION[LOGIN_LEVEL] )
          $this->block_sale( $product_id );

      $query = "update price_history set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax', shop_id='$shop_id', start_date='$start_date', end_date='$end_date', update_time=Now()
                where seq='$seq'";
      mysql_query ( $query, $connect );

      // product table의 org_id가 $product_id인 상품의 가격 변경
       $query = "update products set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax' 
                where org_id='$product_id' or product_id='$product_id'";

//echo $query;
//exit;
       mysql_query ( $query , $connect );

      $link_url = $link_url ? base64_decode ( $link_url ) : "template.htm?template=CK01&id=$product_id"; 

      global $top_url;
      $this->opener_redirect( $link_url . "&top_url=$top_url" );
      $this->closewin();

   }

   //////////////////////////////////////////////
   // 삭제 
   // date : 2005.10.10 - jk
   function del_option()
   {
      global $org_id, $connect, $link_url;

      $transaction = $this->begin("상품삭제");

      // 주문에 정보가 있을 경우 삭제 불가
      $query = "select product_id from orders where product_id='$org_id'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      if ( !$data[product_id] )
      {
	      $query = "update products set is_delete = 1, delete_date=Now() where product_id='$org_id'";
	      mysql_query ( $query, $connect );
	      $this->jsAlert ( "삭제 되었습니다");
      }
      else
	      $this->jsAlert ( "이미 주문된 상품이 있어서 삭제 불가!!");

      $this->end( $transaction );

      global $top_url;
      $this->redirect ( $link_url . "&top_url=$top_url");
      exit;
   }

   //////////////////////////////////////////////
   // 삭제 
   // date : 2005.10.10 - jk
   function del()
   {
      global $org_id, $connect, $link_url;
      $id = $org_id;

      $transaction = $this->begin("상품삭제");

      $query = "update products set is_delete = 1, delete_date=Now() where product_id='$id'";
      mysql_query ( $query, $connect );

      // match data 삭제
      $query = "delete from code_match where id='$id'";      
      mysql_query ( $query, $connect );

//echo $query;
//exit;

      // delete copy products
      $query = "update products set is_delete = 1, delete_date=Now() where org_id='$id'";
      mysql_query ( $query, $connect );

      $this->end( $transaction );

      $this->jsAlert ( "삭제 되었습니다");

      global $top_url;
      $this->redirect ( $link_url . "&top_url=$top_url" );

      // modified by sy.hwang 2006.1.5
      //$this->redirect ( $link_url . "&id=$id&top_url=$top_url" );

      exit;
   }

   //////////////////////////////////////////////
   // 되살리기
   // date : 2005.10.11 - jk
   function restore()
   {
      global $product_id, $connect, $link_url;

      $transaction = $this->begin("상품 되살리기");

      $query = "update products set is_delete = 0 where product_id='$product_id'";
      mysql_query ( $query, $connect );

      // delete copy products
      $query = "update products set is_delete = 0 where org_id='$product_id'";
      mysql_query ( $query, $connect );

      $this->end( $transaction );
      $this->jsAlert ( "부활 되었습니다");

      global $top_url;
      $this->redirect ( "?template=CK01&id=$product_id" . "&top_url=$top_url" );
      exit;
   }

   function download2()
   {
//echo "download2";
//exit;

      require_once 'Spreadsheet/Excel/Writer.php';

      global $connect, $saveTarget, $filename, $search_date;
      // download format에 대한 정보를 가져온다

      $download_items = array(
	  "product_id" 		=> "자체상품코드",
	  "name" 	        => "제품명",
	  "empty2"		=> "카테고리",	// D
	  "img_500"	=> "500 이미지",
	  "img_desc1"	=> "img1",
	  "img_desc2"	=> "img2",
	  "img_desc3"	=> "img3",
	  "img_desc4"	=> "img4",	// H
	  "options"	=> "옵션",	// I
	  "brand"	=> "브랜드",	// J
	  "origin"	=> "제조국",	// k
	  "product_desc" => "상세설명", // l
	  "is_free_deliv"	=> "배송비", // M
	  "trans_price"	=> "배송비", // N
	  "empty4"	=> "배송시 유의사항",
	  "market_price"  => "소비자가격",
	  "org_price"	=> "공급받는가격",
	  "supply_price" => "정산가격",
	  "shop_price"   => "판매가",
	  "empty5"	 => "제품상태",
	  "tax"		 => "과면세 유형",
	  "supply_code"  => "벤더구분",
	  "enable_sale"  => "품절여부",

	);
      //////////////////////////////////////////////
      // step 1.전체 출력 
      $opt = "";
      $download = 1;
      $result = $this->get_list( &$total_rows , 0, $download );

      //if ( _DOMAIN_ == "goodnjoy" or _DOMAIN_ == "ezadmin" or _DOMAIN_ == "sabina" )
      //{
		// file open
        	$handle = fopen ($saveTarget, "w");
		$buffer .= "<html><table border=1>";

                $buffer .= "<tr>";

                //for( $i=0; $i < count( $download_items ); $i++ ) 
		foreach ( $download_items as $key=>$value )
                {
                        // print "$key / $value <br>";
                        $buffer .= "<td align=center>&nbsp; " . htmlentities( $value ) .  "</td>";
                }
                $buffer .= "</tr>\n";

	        fwrite($handle, $buffer);

		//===================================================
		//
		// data부분 download
		//
		while ( $data = mysql_fetch_array ( $result ) )
		{
			$buffer = "<tr>\n";

			// org_price, shop_price, supply_price 가져온다
			// date: 2008.6.9 - jk
			$arr_price = class_product::get_primary_price( $data[product_id] );
			$data[market_price] = $arr_price[market_price];
			$data[org_price]    = $arr_price[org_price];
			$data[supply_price] = $arr_price[supply_price];
			$data[shop_price]   = $arr_price[shop_price];

			//for( $i=0; $i < count( $download_items ); $i++ )
			foreach ( $download_items as $key=>$value )
			{
				$buffer .= "<td>";
				//$buffer .= htmlentities( stripslashes($this->get_data( $data, $key, $value )) );
				$buffer .=  htmlspecialchars( stripslashes($this->get_data( $data, $key, $value )) ); 
				$buffer .= "</td>";
			}

			$buffer .= "</tr>\n";
			fwrite($handle, $buffer);
			$buffer = "";
		}

		// footer 기록
		fwrite($handle, "</table>");

		$saveTarget2 = $saveTarget . "_";
		$run_module = "/usr/bin/perl /home/ezadmin/public_html/shopadmin/html2xls.pl -o $saveTarget -o $saveTarget2";
      		exec( $run_module );

		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=상품_" . date('Ymd') . ".xls");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		header("Pragma: public");

	      if (is_file($saveTarget2)) {
		  $fp = fopen($saveTarget2, "r");
		  fpassthru($fp);
	      }

	      ////////////////////////////////////// 
	      // file close and delete it 
	      fclose($fp);
	      unlink($saveTarget);
	      unlink($saveTarget2);

      //}
      //else
      //{
	        // Creating a workbook
	      	$workbook = new Spreadsheet_Excel_Writer();

	      	// sending HTTP headers
	      	$workbook->send( $filename . ".xls" );

	      	// Creating a worksheet
	      	$worksheet =& $workbook->addWorksheet('Sheet1');

        	$this->write_excel ( $worksheet, $result, $download_items, $rows );

          	// Let's send the file
          	$workbook->close();
      //}
   }    

   /////////////////////////////////////////////////////// 
   // table 에 write 함
   // date: 2005.10.20
   function write_table ( $worksheet, $result, $download_items, $rows = 0 )
   {
      $i = $rows ? $rows : 0;
      $j = 0;
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // header
         if ( $i == 0 && $header != -99 )
         {
            $j = 0;
            foreach ( $download_items as $key=>$value )
            {
               $worksheet->write($i, $j, $value );
               $j++;
            }
            $i++;
         }

         // increase column
         $j = 0;
         foreach ( $download_items as $key=>$value )
         {
            $worksheet->write($i, $j, $this->get_data( $data, $key, $i ) );
            $j++;
         }
         // increase row
         $i++;
      }
   }


   /////////////////////////////////////////////////////// 
   // excel에 write 함
   // date: 2005.10.20
   function write_excel ( $worksheet, $result, $download_items, $rows = 0 )
   {
      $i = $rows ? $rows : 0;
      $j = 0;
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // header
         if ( $i == 0 && $header != -99 )
         {
            $j = 0;
            foreach ( $download_items as $key=>$value )
            {
               $worksheet->write($i, $j, $value );
               $j++;
            }
            $i++;
         }

         // increase column
         $j = 0;
         foreach ( $download_items as $key=>$value )
         {
            $worksheet->write($i, $j, $this->get_data( $data, $key, $i ) );
            $j++;
         }
         // increase row
         $i++;
      }
   }

   function get_data ( $data, $key, $i )
   {
      switch ( $key )
      {
          case "image":
	      $file = "/home/ezadmin/public_html/shopadmin/images/noimage.gif";
	      return join("", file($file));
              break;
          case "supply_name":
              return $this->get_supply_name2($data[supply_id]);
              break;
          case "org_id":
              require_once "class_E.php";
              return class_E::get_org_id( $data[product_id] );
              break;
          case "option":
              $arr_chars = array("\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'","<br>" );
              require_once "class_D.php"; 
              return class_D::get_product_option ( $data[product_id] ) ? 
                     str_replace( $arr_chars, " " , class_D::get_product_option($data[product_id])) : 
                     str_replace( $arr_chars, " ", $data[options] );
              break;
	  case "enable_sale":
                  return  $data[enable_sale] ? "판매가능" : "판매불가";
	      break;
          default:
              $val = $data[$key] ? $data[$key] : "";
              return  str_replace( array("\r", "\n", "\r\n","\t" ), " ", $val );
		// return $val;
           break; 
      }
   }

   ///////////////////////////////////////////
   // 상품을 그룹에 추가
   function copytogroup()
   {
      // group_id : 쿼리시 선택한 id
      // group_id2 : 상품을 옮기기 위한 그룹 id
      global $connect, $link_url, $product_ids, $group_id, $group_id2;

      $arr_products = explode(",",  $product_ids);

      if ( $arr_products )
      {
         foreach ( $arr_products as $value )
         {
            if ( $value )
            {
               $query = "insert into product_group set group_id='$group_id2', product_id='$value', regdate=Now()";
               mysql_query ( $query, $connect );
            }
         }
      }
      else
         echo "what?"; 

      $this->jsAlert(" 복사 되었습니다");
      $this->redirect($link_url);
   }

   // 사은품 add by sy.hwang
   function CK17()
   {
      global $template;
      global $connect;
    
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   // 전시물품등록
   function CK18()
   {
      global $template;
      global $connect;
    
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // 상품메모
   function CK19()
   {
      global $template;
      global $connect;
    
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

    //////////////////////////////////////////
    // copy to ez_store table
    function add_store()
    {
	global $template;
	global $connect;

	$sys_connect = sys_db_connect();
	$product_id = $_REQUEST[product_id];
	$product_name = addslashes($_REQUEST[product_name]);
	$shop_price = $_REQUEST[shop_price];
	$options = $_REQUEST[options];
	$memo = addslashes($_REQUEST[memo]);


	$sql = "select product_id from ez_store 
		 where domain = '"._DOMAIN_."'  
		   and product_id = '$product_id'";
	$list0 = mysql_fetch_array(mysql_query($sql, $sys_connect));


	$sql = "select * from products where product_id = '$product_id'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	
	////////////////////////////////////////
	// make thumb image
	if ($list[img_500] && file_exists(_save_dir.$list[img_500]))
	{
	    $img_150 = _save_dir . str_replace("_500", "_150", $list[img_500]);
	    if (!file_exists($img_150))
	    {
		$cmd = "/usr/local/bin/convert -resize 150x150 "._save_dir.$list[img_500]." ".$img_150;
		exec($cmd);
	    }
	    $img_250 = _save_dir . str_replace("_500", "_250", $list[img_500]);
	    if (!file_exists($img_250))
	    {
		$cmd = "/usr/local/bin/convert -resize 250x250 "._save_dir.$list[img_500]." ".$img_250;
		exec($cmd);
	    }
	}

	if ($list0[product_id])		// update
	{
	    $upd_sql = "update ez_store set
				name = '$product_name',
				shop_price = '$shop_price',
				options = '$options',
				memo = '$memo',
				is_sale = 1
			 where domain = '"._DOMAIN_."'
			   and product_id = '$product_id'";
	    mysql_query($upd_sql, $sys_connect) or die(mysql_error());
	}
	else				// insert
	{
	    ////////////////////////////////////////
	    $ins_sql = "insert into ez_store set
			domain 		= '" . _DOMAIN_ . "',
			product_id 	= '$list[product_id]',
			name		= '$list[name]',
			origin		= '$list[origin]',
			brand		= '$list[brand]',
			options		= '$list[options]',
			shop_price	= '$list[shop_price]',
			trans_fee	= '$list[trans_fee]',
			product_desc	= '$list[product_desc]',
			img_500		= '$list[img_500]',
			img_desc1	= '$list[img_desc1]',
			img_desc2	= '$list[img_desc2]',
			img_desc3	= '$list[img_desc3]',
			img_desc4	= '$list[img_desc4]',
			img_desc5	= '$list[img_desc5]',
			img_desc6	= '$list[img_desc6]',
			reg_date 	= now(),
			reg_time 	= now(),
			is_sale 	= 1
	    ";
	    mysql_query($ins_sql, $sys_connect) or die(mysql_error());
	}

	// add by sy.hwang 2007.5.4
	$sql = "select seq from ez_store 
		 where domain = '"._DOMAIN_."'
		   and product_id = '$product_id'";
	$list = mysql_fetch_array(mysql_query($sql, $sys_connect));

	$store_id = sprintf("A%05d", $list[seq]);
	$upd_sql = "update ez_store set store_id = '$store_id' 
		 where domain = '"._DOMAIN_."'
		   and product_id = '$product_id'";
	mysql_query($upd_sql, $sys_connect) or die(mysql_error());

	$upd_sql = "update products set is_store = 1 where product_id = '$product_id'";
	mysql_query($upd_sql, $connect) or die(mysql_error());
	
        $this->redirect("popup.htm?template=CK18&product_id=$product_id");
        exit;
    }

    //////////////////////////////////////////
    function stop_store()
    {
	global $template;
	global $connect;

	$sys_connect = sys_db_connect();
	$product_id = $_REQUEST[product_id];

	$upd_sql = "update ez_store set
			   is_sale = -1
		     where domain = '"._DOMAIN_."'
		       and product_id = '$product_id'";

	mysql_query($upd_sql, $sys_connect) or die(mysql_error());

	$upd_sql = "update products set is_store = -1 where product_id = '$product_id'";
	mysql_query($upd_sql, $connect) or die(mysql_error());

        $this->redirect("popup.htm?template=CK18&product_id=$product_id");
        exit;
    }
    //////////////////////////////////////////
    function del_store()
    {
	global $template;
	global $connect;

	$sys_connect = sys_db_connect();
	$product_id = $_REQUEST[product_id];

	$upd_sql = "update ez_store set
			   is_sale = -2
		     where domain = '"._DOMAIN_."'
		       and product_id = '$product_id'";

	mysql_query($upd_sql, $sys_connect) or die(mysql_error());

	$upd_sql = "update products set is_store = -2 where product_id = '$product_id'";
	mysql_query($upd_sql, $connect) or die(mysql_error());

        $this->redirect("popup.htm?template=CK18&product_id=$product_id");
        exit;
    }

   ////////////////////////////////////
   function giftadd()
   {
	global $template;
	global $connect;

	$id = $_REQUEST[id];
	$product_id = $_REQUEST[product_id];
	$amount = $_REQUEST[amount];
	$gift = $_REQUEST[gift];

	/////////////////////////////////
	// 1. 해당 아이디가 있는지 확인한다.
	$gift = str_replace("\"", "", $gift);
	$gift = str_replace("'", "", $gift);
	if ($id)
	{
	  $sql = "update gift set amount = '$amount', gift = '$gift'
		   where id = '$id'";
	}
	else
	{
	  $sql = "insert into gift (product_id, amount, gift, crdate)
		   values ('$product_id', '$amount', '$gift', now())";
	}
	mysql_query($sql, $connect) or die(mysql_error());

        $this->redirect("popup.htm?template=CK17&product_id=$product_id");
        exit;
   }

   function giftdel()
   {
	global $template;
	global $connect;

	$id = $_REQUEST[id];
	$product_id = $_REQUEST[product_id];
	$amount = $_REQUEST[amount];
	$gift = $_REQUEST[gift];

	// 1. 해당 아이디로 무조건 삭제
	if ($id)
	{
	  $sql = "delete from gift where id = '$id'";
	  mysql_query($sql, $connect) or die(mysql_error());
	}

        $this->redirect("popup.htm?template=CK17&product_id=$product_id");
        exit;
   }
}
?>
