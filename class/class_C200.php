<?
require_once "class_top.php";
require_once "class_db.php";
require_once "class_B.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_file.php";
require_once "class_combo.php";
require_once "class_category.php";
require_once "class_stock.php";
require_once "class_product.php";
require_once "class_shop.php";
require_once "class_ui.php";
require_once "class_3pl.php";
require_once "class_CL00.php";
require_once "class_auto.php";
require_once "class_supply.php";
require_once "class_lock.php";
require_once "class_table.php";
//require_once "class_shoplinker.php";
require_once "class_multicategory.php"; // 2011.7.6  jk추가
require_once "class_cafe24.php";        // 2012.3.6  jk추가
require_once "class_godo.php";          // 2014.5.29 jk추가

include_once "googleapis/urlshortner.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_C200 extends class_top
{
    /********************************
    * command 추가
    ********************************/
    function add_command()
    {
        global $command_list, $product_id,$connect;

        $arr_command = split(",", $command_list );

        $obj = new class_auto();
        $obj->add_product_command( $product_id, $arr_command);
    }

    //
    //  고도몰 재고 개수를 가져온다.
    //
    function godo_get_stock()
    {
        global $connect, $product_id, $link_id;
        
        $obj = new class_godo( $connect );
        $shop_id = 10158;
        $obj->get_stock($product_id, $link_id, $shop_id);
    }

    // 
    // 
    //
    

   function get_auto_shop_list()
   {
        global $connect, $product_id;

        echo "p: $product_id";
   }

   function C221()
   {
        global $connect, $template;
        
        $query = "select * from products where supply_code=20002";
        $result = mysql_query( $query, $connect );
                
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
   }

   function C209()
   {
      global $template, $id, $d_date;

      $data = $this->get_detail( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function C220()
   {
      global $template, $id, $d_date;

      $data = $this->get_detail( $id );

      include "template/C/C220.htm";
   }
   function C222()
   {
      global $template, $connect, $id, $d_date;

      $data = $this->get_detail( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // image detail view or del popup
   function C204()
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
   function C205()
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
   
    // 판매처 리스트..
    function get_group_shop_list()
    {
        global $connect, $group_id;
        
        $query = "select * from userinfo where level=0 order by name";
        $result = mysql_query( $query, $connect );
        
        $arr_result = array();
        while( $data = mysql_fetch_assoc( $result ) )
        {
            $selected = "";
            if ( $data[group_id] == $group_id )
                $selected = "selected";
                
            $arr_result[] = array( id        => $data[code]
                                 , name      => $data[name]
                                 , group_id  => $data[group_id]
                                 , selected  => $selected );
        }
        echo json_encode( $arr_result );
    }
   
   // disp match table information
   function C207()
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
   function C212()
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
   
   // stock manage copy popup
   function C250()
   {
      global $template;
      global $id,$top_url,$options;
 
      $val_item = array ( "supply_code"=>"공급처 코드" );
      $this->validate ( $val_item );

      // 상세 정보 가져온다
      $data = $this->get_detail( $id );
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // copy popup
   function C202()
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
   function C210()
   {
      global $template, $id;

      $end_date= date('Y-m-d', strtotime('10 year'));

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
   function C211()
   {
      global $template;


      $val_item = array ( "supply_code"=>"공급처 코드" );
      $this->validate ( $val_item );

      // 상세 정보 가져온다
      $data = $this->get_price_detail();

      $shop_id = ($data[shop_id] ? $data[shop_id] : 0);
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
   function C215()
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
   function C216()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();     
    
      $result = $this->get_list( &$total_rows, $page );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
	function C232()
	{
		global $template, $connect;
		
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	function C233()
	{
		global $template, $connect;
		
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}	
	function C234()
	{
		global $template, $connect;
		global $str_supply_code,$string_type,$string,$stock_manage,$e_stock,$e_sale,$sync,$date_type,$start_date,$end_date,$include_option,$sort,$str_category,$m_sub_category_1,$m_sub_category_2,$m_sub_category_3,$category,$template,$multi_supply_group,$multi_supply;
        $is_download = 1;
        
        
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	function C235()
	{
		global $template, $connect;
		global $str_supply_code,$string_type,$string,$stock_manage,$e_stock,$e_sale,$sync,$date_type,$start_date,$end_date,$include_option,$sort,$str_category,$m_sub_category_1,$m_sub_category_2,$m_sub_category_3,$category,$template,$multi_supply_group,$multi_supply;
		
		$temp = "";
		$result = class_C::get_list( &$cnt_all, $page, 1 );
		
		echo "<script>parent.hide_waiting();</script>";
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
   // 상품 리스트 
   function C200()
   {
        global $template, $pick_soldout_date, $start_date, $end_date, $packed, $date_type,$e_stock, $stock_manage, $link_url_list, $sort, $str_category,$current_category1,$current_category2,$current_category3, $price_shop_id, $category;
        global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $m_sub_category_4, $click_index, $page;
        global $str_supply_code, $s_group_id, $str_category, $supply_code;
        global $multi_supply_group, $multi_supply;

  // var_dump($_REQUEST);
  // exit;

      $par_arr = array("template","supply_code","action","supply_code","string_type","string","date_type","start_date","end_date","category",
                       "stock_manage","e_stock","e_sale","page","sort","str_category","price_shop_id","m_sub_category_1","m_sub_category_2","m_sub_category_3","m_sub_category_4","click_index","s_group_id","multi_supply_group", "multi_supply");
      $link_url_list = $this->build_link_par($par_arr);     

      $par = array("template","action","string_type","string","date_type","start_date","end_date","category",
                       "stock_manage","e_stock","e_sale","page","sort","str_category","price_shop_id","m_sub_category_1","m_sub_category_2","m_sub_category_3","m_sub_category_4","click_index","s_group_id","multi_supply_group", "multi_supply");
      $link_url = $this->build_link_url3( $par );

      if ( $_REQUEST["page"] )
         $result = $this->get_list( &$total_rows, $page );

    
      // supply_code 는 추가
      $link_url .= "supply_code=$str_supply_code";

      $supply_code = split(",", $str_supply_code );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   //////////////////////////////////////////////
   // 상품 변경 폼
   // 벤더를 위한 상품 변경 폼
   function C213()
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
         $this->redirect("?template=C214&id=$id" . "&top_url=$top_url");
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
   function C201()
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
         $this->redirect("?template=C208&id=$id&top_url=$top_url");
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
        global $product_id, $top_url, $template, $link_url_list;
        $product_id = $product_id ? $product_id : $_product_id;

        $obj = new class_product();
        $new_product_id = $obj->copy_product($product_id);
    
        $this->jsAlert("상품이 복사 되었습니다.");
        $this->redirect("?template=C208&id=$new_product_id&top_url=$top_url&link_url_list=$link_url_list");
        
   }
   //========================================
   // 
   // date: 2007.5.9 옵션 수정
   //
   function changeOption()
   {
            global $connect, $product_id, $org_id, $option_id, $options, $name, $enable_sale, $barcode,$supply_options, $is_popup;

            // $name    = iconv("UTF-8", "CP949", $name);
            // $options = iconv("UTF-8", "CP949", $options);
            $options = addslashes ( $options );
            $transaction    = $this->begin(" 상품변경 $product_id $org_id-> $option_id / $optins");

            $query = "update products set product_id='$option_id', barcode='$barcode', options='$options'
                                 ,name='$name',enable_sale='$enable_sale',supply_options='$supply_options'";
        
            # 여부에 따라 저장
            if ( $enable_sale )
                $query .= ", sale_start_date =Now()";
            else
                $query .= ", sale_stop_date  =Now()";

            $query .= " where product_id='$org_id'";
    
            mysql_query ( $query, $connect );

//debug ( $query );
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
   // modified by syhwang 2013.4.24 (단순정렬+이지체인)
   //
   function listOption( )
   {
        global $connect, $product_id, $option_id, $is_popup;

        //copy 상품의 정보 가져온다.
        $result = $this->get_stock_list( $product_id );
        Header("Content-Type:plain/text;charset=utf-8");        

?>
        <table border=0 cellpadding=0 cellspacing=0 class="tableGridA" width="100%">
            <tr>
              <th>상품코드</th>
              <th>연동코드</th>
              <th>*</th>
              <th>품절</th>
              <th>옵션</th>
              <th>바코드</th>
              <th>로케이션</th>
              <th>공급처옵션</th>
<?
//if(_DOMAIN_=="changki77" || _DOMAIN_=="ilovej" || _DOMAIN_=="ezadmin" )  //2014.06.24 전체 열기..
{
?>
			  <th>옵션추가금액</th>
<?
}
?>
              <th>정상<br>재고</th>
              <th><?=$_SESSION[EXTRA_STOCK_TYPE]?><br>재고</th>
              <th>재고<br>경고수량</th>
              <th>재고<br>위험수량</th>
              <th>등록일</th>		
			  <th>동기화</th>		

              <th>품절일</th>
              <th>상품메모</th>
<? if( _DOMAIN_ == "aka" || _DOMAIN_ == "ezadmin") { ?>
              <th>공급처</th>
<? } ?>
<!--              <th>재고연동</th>		-->
              <th width=160>메뉴</th>
        </tr>
        <?
        while ( $data = mysql_fetch_array ( $result )) {
			$ecn_code = ($data[ecn_code]) ? "<span title='$data[ecn_code]'>O</span>" : "";
        ?>
            <tr bgcolor="#FFFFFF">
                  <td height=30>&nbsp;<?= $data[product_id] ?>&nbsp;<?= $data[enable_sale]?"<img src=images/soldout_blank.gif>":"<img src=images/soldout.gif>" ?></td>
                  <td>
                    <?= $data[link_id] ? $data[link_id] : "&nbsp;" ?>
                    <? if( $_SESSION[CAFE24_SOLUP] )  echo "<br>($data[shop_product_code])"; ?>
                  </td>
                  <td height=20 class=left>&nbsp;<?= $ecn_code?></td>
                  <td height=25 class=center><input type=checkbox id='<?="chk_".$data[product_id]?>' onclick="javascript:check_soldout('<?=$data[product_id]?>')" <?=$data[enable_sale] ? "" : "checked" ?>></td>
                  <td height=25 class=left>&nbsp;<?= $data[options]?></td>
                  <td align=center height=30>
                    <input type=text class=input22 style='width:100px' name='options_barcode'
                           value="<?= htmlspecialchars($data[barcode]) ?>"
                           org_value="<?= htmlspecialchars($data[barcode]) ?>"
                           onblur="javascript:update_options_info(this,<?= "'".$data[product_id]."'" ?>,'barcode')">
                  </td>
                  <td align=center height=30>
                    <input type=text class=input22 style='width:100px' name='options_location' 
                           value="<?= htmlspecialchars($data[location]) ?>"
                           org_value="<?= htmlspecialchars($data[location]) ?>"
                           onblur="javascript:update_options_info(this,<?= "'".$data[product_id]."'" ?>,'location')">
                  </td>
                  <td align=center height=30>
                    <input type=text class=input22 style='width:100px' name='options_supply_options' 
                           value="<?= htmlspecialchars($data[supply_options]) ?>"
                           org_value="<?= htmlspecialchars($data[supply_options]) ?>"
                           onblur="javascript:update_options_info(this,<?= "'".$data[product_id]."'" ?>,'supply_options')">
                  </td>
<?
//if(_DOMAIN_=="changki77"  || _DOMAIN_=="ilovej" || _DOMAIN_=="ezadmin") //2014.06.24 전체 열기..
{
?>
                  <td align=center height=30>
                    <input type=text class=input22 style='width:100px' name='options_extra_price' 
                           value="<?= htmlspecialchars($data[extra_price]) ?>"
                           org_value="<?= htmlspecialchars($data[extra_price]) ?>"
                           onblur="javascript:update_options_info(this,<?= "'".$data[product_id]."'" ?>,'extra_price')">
                  </td>
<?
}
?>
                  <!--td class=left>&nbsp;<?= $data[supply_options]?></td-->
                  <?
                    $stock1 = class_stock::get_current_stock( $data[product_id], 0 );
                    $stock2 = class_stock::get_current_stock( $data[product_id], 1 );
                    $stock3 = $stock1 + $stock2;
                  ?>
                  <td><?=  $stock1 ?>&nbsp;</td>
                  <td><?=  $stock2 ?>&nbsp;</td>
                  <td><?= $data[stock_alarm1]?>&nbsp;</td>
                  <td><?= $data[stock_alarm2]?>&nbsp;</td>
                  
                  <td><?= $data[reg_date]?><br><?= $data[reg_time] ?>&nbsp;</td>				
                  <?
                  $data[sync_update_date] = str_replace(" ","<br>", $data[sync_update_date]);
                  ?>
                  <td><?=  $data[sync_update_date] ?>&nbsp;</td>		
                  
                  <td style="color:red"><?= ( $data[enable_sale] == 0 ? "<a href='javascript:alert(\"" . $data[sale_stop_date] . " " . $data[soldout_worker] . "\")' style='color:red'>" . substr($data[sale_stop_date],0,10) . "</a>" : "" ) ?>&nbsp;</td>
                  <td><?= $data[link_id] ? "<img src=./images/sync_dn.png width=14 alt='판매처 연동상품'>":""?><?= $data[memo]?>&nbsp;</td>
                  <!--td bgcolor="ffffff" ><?= $data[stock_sync]?"연동":"" ?>&nbsp;</td-->
                  <?
                  //
                  $supply_info = class_supply::get_info( $data[supply_code]);
                  
                  
                  ?>
<? if( _DOMAIN_ == "aka" || _DOMAIN_ == "ezadmin") { ?>
				<td><!--공급처 -->
					<a href='javascript:openwin2("popup_utf8.htm?template=C230&is_popup=<?=$is_popup?>&id=<?= $data[product_id]?>&top_url=<?= $top_url?>&link_url_list=<?= $link_url_list?>&is_option=1","change_supply", 550, 270)' >
					<?= $supply_info[name] ?> 	
					</a>
				</td> 
<? } ?>
                  <td bgcolor="ffffff" class="center borderEnd">
                      <a href='javascript:openwin2("popup_utf8.htm?template=C231&id=<?=$data[product_id]?>&org_id=<?=$data[org_id]?>&top_url=<?= $top_url?>","change_option_product", 500, 550)' class=btn_premium2>
                        <img src=./images/icon_confirm.gif> 변경
                      </a>&nbsp;
                      <? if( !$is_popup ){ ?>
                      <a href="javascript:del_option('<?= $data[org_id] ?>', '<?= $data[product_id] ?>')" class=btn_premium2><img src=./images/del_link.gif> 삭제</a>
                      <? } ?>
                      
                      <?
                      
                      if ( $_SESSION[LOGIN_LEVEL] >= 9 || _DOMAIN_ == "changki77" )
                      {
                      	echo "<br>";
                        echo "<a href=javascript:cafe24_stock_sync('$data[product_id]','$stock3') class=btn_premium2><img src=http://naradesign.net/icon/fugue/arrow_090_medium.png> 재고</a>&nbsp;&nbsp;";
                        if ( $_SESSION[LOGIN_LEVEL] >= 9)
                        echo "<a href=javascript:godo_get_stock('$data[product_id]','$data[link_id]') class=btn_premium2><img src=http://naradesign.net/icon/fugue/arrow_090_medium.png> 고도</a>";                      
                      }
                      ?>
                  </td>
            </tr>  
        <? } ?>
      </table>
<?
   }

    function cafe24_stock_sync()
    {
        global $connect,$product_id,$stock;
        
        $obj = new class_cafe24( $connect );
        
        $shop_id = 10072;
        $obj->stock_sync( $product_id, $shop_id, $stock );
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
   function C214()
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
   function C208()
   {
      global $template, $connect;
      global $id,$top_url, $link_url, $link_url_list, $is_popup;

      // 상세 정보 가져온다
      $data = $this->get_detail( $id );

      // sl_product의 정보 가져온다
//      $obj_sl  = new class_shoplinker();
//      $sl_data = $obj_sl->load( $id );

      //print_r ( $sl_data );

      if ( $data[org_id] )
      {
          $id = $data[org_id];
          $data = $this->get_detail( $data[org_id] );
      }

      $data[product_desc] = htmlspecialchars( htmlspecialchars_decode($data[product_desc]) );
      $data[product_desc2] = $data[product_desc2];

      // price table의 정보 가져온다.
      $result_price = $this->get_price_history();
      $master_code = substr( $template, 0,1);

      if( !$data[is_url_img0] && strpos($data[img_500], "http://") === false )
          $data[img_500_a]   = "'" . ( $data[img_500]   ? _IMG_SERVER_ . "/" . _upload_path . "/" . $data[img_500]   : "" ) . "'";
      else
          $data[img_500_a]   = "''";

      if( !$data[is_url_img1] && strpos($data[img_desc1], "http://") === false )
          $data[img_desc1_a] = "'" . ( $data[img_desc1] ? _IMG_SERVER_ . "/" . _upload_path . "/" . $data[img_desc1] : "" ) . "'";
      else
          $data[img_desc1_a] = "''";

      if( !$data[is_url_img2] && strpos($data[img_desc2], "http://") === false )
          $data[img_desc2_a] = "'" . ( $data[img_desc2] ? _IMG_SERVER_ . "/" . _upload_path . "/" . $data[img_desc2] : "" ) . "'";
      else
          $data[img_desc2_a] = "''";

      if( !$data[is_url_img3] && strpos($data[img_desc3], "http://") === false )
          $data[img_desc3_a] = "'" . ( $data[img_desc3] ? _IMG_SERVER_ . "/" . _upload_path . "/" . $data[img_desc3] : "" ) . "'";
      else
          $data[img_desc3_a] = "''";

      if( !$data[is_url_img4] && strpos($data[img_desc4], "http://") === false )
          $data[img_desc4_a] = "'" . ( $data[img_desc4] ? _IMG_SERVER_ . "/" . _upload_path . "/" . $data[img_desc4] : "" ) . "'";
      else
          $data[img_desc4_a] = "''";

      if( !$data[is_url_img5] && strpos($data[img_desc5], "http://") === false )
          $data[img_desc5_a] = "'" . ( $data[img_desc5] ? _IMG_SERVER_ . "/" . _upload_path . "/" . $data[img_desc5] : "" ) . "'";
      else
          $data[img_desc5_a] = "''";

      if( !$data[is_url_img6] && strpos($data[img_desc6], "http://") === false )
          $data[img_desc6_a] = "'" . ( $data[img_desc6] ? _IMG_SERVER_ . "/" . _upload_path . "/" . $data[img_desc6] : "" ) . "'";
      else
          $data[img_desc6_a] = "''";




	  
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // 상품 변경 폼 ( 재고 관리 폼)
   function C206()
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

   function C203()
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
   function get_detail ( $id, $is_org='' )
   {
      global $connect;

      if ( $is_org == "org" )
      {
         $query = "select org_id from products where product_id='$id'";
         $result = mysql_query( $query, $connect );
         $data   = mysql_fetch_array( $result );
         $id     = $data[org_id] ? $data[org_id] : $id;
      }

      return class_C::get_detail ( $id );
   }

   //////////////////////////////
   // 상품 리스트
   function get_list( &$max_row, $page, $download=0 )
   {
      global $connect;
      global $string, $disp_copy, $enable_sale, $group_id, $date_type,$e_stock, $stock_manage, $sort, $str_category,$current_category1,$current_category2,$current_category3, $sync;

      return class_C::get_list ( &$max_row, $page, $download );
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
                   and stock_manage = 1 ";
      if( _DOMAIN_ == 'sgo' || _DOMAIN_ == 'vanillashu' || _DOMAIN_ == "sappun")
        $query .= " order by options";
      else
        $query .= " order by product_id";



      $result = mysql_query( $query, $connect );

      return $result;
   }

    function init_product_reg()
    {
        // 공통필드
        // $this->items = array("name","brand","origin","weight","trans_code","enable_stock","market_price","is_url_img","stock_sync");
        $this->items = array("name","brand","origin","weight","trans_fee","trans_code","enable_stock","market_price","is_url_img","pack_disable","rt_disable","ecn_is_hide","pack_cnt","category",
                             "maker","is_free_deliv","product_gift","md","manager1","manager2","str_category","no_sync","no_stock_sync","is_url_img0","is_url_img1","is_url_img2","is_url_img3",
                             "is_url_img4","is_url_img5","is_url_img6","trans_type","start_date2");
        // 개별필드
        $this->items_d = array("options","supply_options","barcode","location","stock_alarm1","stock_alarm2","product_desc","product_desc2","match_cancel","memo","reserve_qty");

        $this->val_items = array("name"=>"상품명", "org_price"=>"원가", "supply_price"=>"공급가","supply_code"=>"공급처" );
    }

   // copy
   function add_options()
   {
      global $connect, $org_id, $options, $barcode, $auto_barcode, $supply_options, $stock_alarm1, $stock_alarm2;

      $transaction = $this->begin("옵션 추가");

      // id 생성 
      $table_name = "products";
      $query = "select max(max) m from $table_name";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_array($result);
      $max = $data[m] + 1;
      $id = $id_index . sprintf("%05d", $max);
      $id = "S" . $id;

      // 기존 data 가져오는 작업
      $query = "select * from products where product_id='$org_id'";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_array($result);
 
      $org_items = array ("supply_code","name","origin","brand","org_price","supply_price","shop_price","enable_sale",
                          "stock_manage","market_price","trans_fee","trans_code","enable_stock","maker",
                          "img_500","img_desc1","img_desc2","img_desc3","img_desc4","img_desc5","img_desc6",
                          "is_url_img","is_url_img0","is_url_img1","is_url_img2","is_url_img3","is_url_img4","is_url_img5","is_url_img6",
                          "weight","pack_disable","rt_disable","ecn_is_hide","pack_cnt","str_category","no_sync","no_stock_sync","trans_type");

      $copy_items = array ("org_id", "options", "barcode", "supply_options", "stock_alarm1", "stock_alarm2" ); 

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

         // 판매가능/품절 값이 2인 경우엔 1로 저장
         if ( $item == "enable_sale" && $data[$item] == 2 )
             $query .= "$item = 1";
         else
             $query .= "$item = '" . addslashes($data[$item]) . "'";
         if ( $i != count( $org_items ) )
            $query .= ",";
         $i++;
      }

      $query .= ", max=$max";
      mysql_query ( $query, $connect );

      // 바코드 생성
      $barcode = $this->get_barcode($id, ($auto_barcode=='on' ? 0 : 1), $barcode);
      $query = "update products set barcode='$barcode' where product_id='$id'";
      mysql_query($query,$connect);

      class_C::update_soldout( $org_id );
      
     $this->end( $transaction );    
     
     // self.close 한 후 opener의 location을 변경해야 
    echo "<script>opener.dispOptionList(0);self.close()</script>";
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
                   "img_desc3", "img_desc4", "img_desc5", "img_desc6","product_desc","product_desc2","str_category");

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
     $this->opener_redirect ( "template.htm?template=C201&id=$org_id&top_url=$top_url" );
     $this->jsAlert("복사 되었습니다.");
     $this->closewin(); 

   }

    //////////////////////////////
    // 정보 변경
    function modify()
    {
        global $connect, $product_id, $is_url_img, $link_url_list, $enable_sale, $stock_sync, $match_cancel, $name;
        global $is_url_img0, $is_url_img1, $is_url_img2, $is_url_img3, $is_url_img4, $is_url_img5, $is_url_img6, $is_popup;
        $id = $product_id;

        $transaction = $this->begin( "상품변경" );
        $this->init_product_reg();
        
        // org_id
        $query = "select * from products where product_id='$id'";
        $result = mysql_query ( $query,$connect );
        $data = mysql_fetch_array ( $result );
        $org_id = $data[org_id];
        $is_represent = $data[is_represent];
        
        // pinkage 유통바코드
        $org_is_represent = $data[is_represent];
        $org_maker = $data[maker];
        $org_barcode = $data[barcode];
    
        // image 저장
        $images = array( "img_500", "img_desc1", "img_desc2","img_desc3","img_desc4","img_desc5","img_desc6" );
    
        ///////////////////////////////////////////////////////////////
        // query 생생, 공통필드
        $query = "update products set last_update_date=Now()";
    
        // 상품 설명 이미지
        foreach( $images as $item )
        {
            global $$item;
            $index = split("_", $item) ;
         
            // 파일업로드 : input 태그 type=file 파일명
            $key = $item . "_name";
            $chk_del = $item . "_del";

            global $$key, $$chk_del;
           
            if( $$chk_del )
            {
                $query .= ", $item = null";
            }
            else
            {
                if ( ($item == "img_500"   && $is_url_img0) || 
                     ($item == "img_desc1" && $is_url_img1) || 
                     ($item == "img_desc2" && $is_url_img2) || 
                     ($item == "img_desc3" && $is_url_img3) || 
                     ($item == "img_desc4" && $is_url_img4) || 
                     ($item == "img_desc5" && $is_url_img5) ||
                     ($item == "img_desc6" && $is_url_img6) )
                {
                    $key = "txt_" . $item;
                    global $$key;
                    $filename = $$key;
                    $query .= ", $item = '$filename'";  
                }
                else if ( $$key )
                {
                    $filename = class_file::save($$item, $$key, $id, $index[1]);
                    $query .= ", $item = '$filename'";
                }
            }
        }
        foreach( $this->items as $item)
        {
            global $$item;
            if( $item == "is_url_img" || $item == "pack_disable" || $item == "rt_disable"  || $item == "ecn_is_hide" || $item == "is_url_img0" || $item == "is_url_img1" || 
                $item == "is_url_img2"|| $item == "is_url_img3" || $item == "is_url_img4" || $item == "is_url_img5" || $item == "is_url_img6")
                $query .= ", $item='" . ( $$item == "on" ? 1 : 0 ) . "'";            
            else if ( $item == "start_date2")
            	$query .= ", sale_date='" . $$item . "'";
            else
                $query .= ", $item='" . $$item . "'";
        }
           
        /////////////////////////////////////////
        // 저장
        if( $is_represent )
            $query .= " where product_id='$id' or org_id='$id'";
        else
        {
            // 품절일
            if( !$enable_sale )
                $query .= ", sale_stop_date = if(enable_sale=1, now(), sale_stop_date) ";

            if( $data[enable_sale] != $enable_sale )
                class_C::insert_products_history($product_id, ($enable_sale ? "판매가능" : "품절"), "상품정보변경에서 판매상태수정");
                
            $query .= ", enable_sale='$enable_sale' where product_id='$id'";
        }
debug("상품변경 : " . $query);
        mysql_query( $query, $connect );

        ///////////////////////////////////////////////////////////////
        // query 생성, 개별필드
        $query = "update products set last_update_date=Now()";
        foreach( $this->items_d as $item )
        {
            global $$item;
            $query .= ", $item='" . $$item . "'";
        }

        /////////////////////////////////////////
        // 저장
        $query .= " where product_id='$id'";
debug("상품변경 : " . $query);
        mysql_query( $query, $connect );

//        $obj_sl = new class_shoplinker();
//        $obj_sl->reg();

        // multi_category처리
        class_multicategory::update_multi_category( $id,$str_category );
        
        ///////////////////////////////
        // pinkage 유통바코드
        if( _DOMAIN_ == 'pinkage' )
        {
            // 유통바코드 만들기
            if( $org_maker != "유통바코드" && $maker == "유통바코드" )
            {
                // 옵션관리
                if( $org_is_represent )
                {
                    $query = "select product_id, barcode from products where org_id='$product_id' and is_delete=0";
                    $result = mysql_query($query, $connect);
                    while($data = mysql_fetch_assoc($result))
                    {
                        if( !("1000000000000" < $data[barcode] && $data[barcode] < "9999999999999") )
                        {
                            $new_barcode = $this->get_barcode($data[product_id]);
                            $query = "update products set barcode='$new_barcode' where product_id='$data[product_id]'";
                            mysql_query($query, $connect);
                        }
                    }
                }
                // 옵션관리 안함
                else
                {
                    if( !("1000000000000" < $org_barcode && $org_barcode < "9999999999999") )
                    {
                        $new_barcode = $this->get_barcode($product_id);
                        $query = "update products set barcode='$new_barcode' where product_id='$product_id'";
                        mysql_query($query, $connect);
                    }
                }                
            }

            // 유통바코드 지우기
            if( $org_maker == "유통바코드" && $maker != "유통바코드" )
            {
                // 옵션관리
                if( $org_is_represent )
                {
                    $query = "select product_id, barcode from products where org_id='$product_id' and is_delete=0";
                    $result = mysql_query($query, $connect);
                    while($data = mysql_fetch_assoc($result))
                    {
                        if( "1000000000000" < $data[barcode] && $data[barcode] < "9999999999999" )
                        {
                            $new_barcode = $this->get_barcode($data[product_id]);
                            $query = "update products set barcode='$new_barcode' where product_id='$data[product_id]'";
                            mysql_query($query, $connect);
                        }
                    }
                }
                // 옵션관리 안함
                else
                {
                    if( "1000000000000" < $org_barcode && $org_barcode < "9999999999999" )
                    {
                        $new_barcode = $this->get_barcode($product_id);
                        $query = "update products set barcode='$new_barcode' where product_id='$product_id'";
                        mysql_query($query, $connect);
                    }
                }                
            }
        }
        
        if( $is_popup )
        {
            $this->update_opener_info($product_id);
        }
        
        $this->redirect("?template=C208&id=$id&is_popup=$is_popup&link_url_list=$link_url_list");
        $this->jsAlert("저장하였습니다.");
        exit;
    }

    //
    // 샵링커 전송
    // 2011.6.14 - jk
    function sl_upload_product()
    {
        $obj_sl = new class_shoplinker();
        $obj_sl->sl_upload_product();
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
     $this->opener_redirect ( "template.htm?template=C201&id=$org_id&top_url=$top_url" );
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
     $this->opener_redirect ( "template.htm?template=C201&id=$org_id&top_url=$top_url" );
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

      $this->opener_redirect ( "template.htm?template=C201&id=$id&top_url=$top_url" );
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

   function all_stock_build()
   {
        global $product_id, $connect;

        $query = "select product_id,options,name from products where (stock_manage is null or stock_manage='') and is_delete=0";
        $result = mysql_query( $query, $connect );

        $i = 0;
        $this->show_wait();
        while ( $data = mysql_fetch_array ( $result ) )
        {
            //echo "p: $data[product_id] <br>";
            $this->show_txt( "진행중 $i" );
            flush();
            class_C::stock_build( $data[name], $data[options], $data[product_id] );
            $i++;
        }
        $this->hide_wait();
        // self.close 한 후 opener의 location을 변경해야 
        $this->jsAlert(" 작업 완료");
        $this->redirect ( "template.htm?template=C200" );
   } 
 
    /////////////////////////////////////////
    // stock_build
    function stock_build()
    {
        global $product_id, $connect, $link_url_list, $top_url;
        
        $query = "select options,name from products where product_id='$product_id'";
        $result = mysql_query( $query, $connect );

        $data = mysql_fetch_array ( $result );
        class_C::stock_build( $data[name], $data[options] );

        // self.close 한 후 opener의 location을 변경해야 
        $this->jsAlert(" 작업 완료");
        $this->redirect ( "?template=C208&id=$product_id&link_url_list=$link_url_list&top_url=$top_url" );
    }   

    /////////////////////////////////////////
    // stock_build
    function stock_build_save()
    {
        global $product_id, $connect, $link_url_list, $top_url, $options, $id;
        
        // Lock Check
        $obj_lock = new class_lock(403);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->redirect ( "?template=C208&id=$product_id&link_url_list=$link_url_list&top_url=$top_url" );
            exit;
        }

        // 먼저 옵션을 저장한다. 
        $query = "update products set options='$options' where product_id='$product_id'";
        mysql_query($query, $connect);

        // 매칭정보를 삭제한다.
        class_product::delete_match_info("'$product_id'");

        $query = "select options,name from products where product_id='$product_id'";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array ( $result );

        class_C::stock_build( $data[name], $data[options] );

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }
        
        // self.close 한 후 opener의 location을 변경해야 
        $this->jsAlert(" 작업 완료");
        $this->redirect ( "?template=C208&id=$product_id&link_url_list=$link_url_list&top_url=$top_url" );

    }   
   
    function stock_delete()
    {
        global $connect, $org_id, $link_url_list, $top_url;
        $id = $org_id;
        
        // Lock Check
        $obj_lock = new class_lock(404);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->redirect ( "?template=C208&id=$id&link_url_list=$link_url_list&top_url=$top_url" );
            exit;
        }

        $transaction = $this->begin( "옵션별 관리 취소" , $id);
        
        $query = "update products set stock_manage=0, is_represent=0, enable_sale=IF(enable_sale,1,0) where product_id='$id'";
        mysql_query ( $query, $connect );
        
        // 상품코드 목록
        $p_list = '';
        $query = "select product_id from products where org_id='$id'";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $p_list .= ( $p_list ? "," : "" ) . "'" . $data[product_id] . "'" ;
            
        // 매칭 삭제
        class_product::delete_match_info($p_list);
        
        // 상품 삭제
        $query = "update products set is_delete=1, delete_date=now() where org_id='$id'";
        mysql_query ( $query, $connect );
        
        // 기존 본상품에 바코드가 없을 경우
        $query_barcode = "select * from products where product_id='$id'";
        $result_barcode = mysql_query($query_barcode, $connect);
        $data_barcode = mysql_fetch_assoc($result_barcode);
        
        if( !($data_barcode[barcode] > '') )
        {
	    	$new_barcode = $this->get_barcode($id);
    	    $query = "update products 
                         set barcode='$new_barcode'
                       where product_id='$id'";
    	    mysql_query ( $query, $connect );
        }

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }

        $this->redirect ( "?template=C208&id=$id&link_url_list=$link_url_list&top_url=$top_url" );
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
      $this->redirect("?template=C208&id=$id&top_url=$top_url"); 
   }
   
   function download()
   {
      global $connect, $saveTarget;

    echo "download";

      $transaction = $this->begin("상품 다운로드 (C200)");

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
                                "배송코드"=>"trans_code",
                                "원가"=>"org_price",
                                "판매가"=>"shop_price",
                                "상품설명"=>"product_desc",
                                "상품설명2"=>"product_desc2",
                                "품절"=>"enable_sale",
                                "연동번호"=>"link_id",
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
        // date : 2009.3.6 원래대로 요청 (마지막 저장한게 최 우선)- 나과장
        /*
            $arr_price = class_product::get_primary_price( $data[product_id] );
            $data[market_price] = $arr_price[market_price];
            $data[org_price]    = $arr_price[org_price];
        */

            switch ( $value )
            {
               case "supply_code":
                  $buffer .= $this->get_supply_name2 ( $data[$value] ) . "\t";
               break;
               case "enable_sale":
                  $buffer .=  $data[enable_sale] ? "판매가능"."\t" : "판매불가"."\t";
               break;
                case "org_price":
                  $buffer .= $data[org_price] . "\t";
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
         
         usleep(10000);
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

      $query = "select a.seq           seq           ,
                       a.product_id    product_id    ,
                       a.start_date    start_date    ,
                       a.end_date      end_date      ,
                       a.org_price     org_price     ,
                       a.supply_price  supply_price  ,
                       a.shop_price    shop_price    ,
                       a.supply_code   supply_code   ,
                       a.shop_id       shop_id       ,
                       a.tax           tax           ,
                       a.is_free_deliv is_free_deliv ,
                       a.update_time   update_time   ,
                       b.disable       b_disable
                  from price_history a left outer join shopinfo b on a.shop_id=b.shop_id
                 where a.product_id='$id' ";

      if ( !$_SESSION[LOGIN_LEVEL] )
          $query .= " and a.supply_code = '" . $_SESSION[LOGIN_CODE] . "'";

      if ( !$option ) 
         $query .= " order by b.sort_name ";
      else
         $query .= " order by a.seq limit 1";

debug("get_price_history : " . $query);
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
      global $shop_id, $supply_code, $org_price, $supply_price, $shop_price, $is_free_deliv, $tax, $is_popup;
      global $top_url, $link_url_list;
 
      //============================================
      //
      // 하부 업체가 상품 정보를 변경할 경우 판매 불가
      // date: 2007.4.13 - jk.ryu
      //
      if ( !$_SESSION[LOGIN_LEVEL] )
          $this->block_sale( $product_id );
 
      $query = "insert price_history 
                   set supply_code='$supply_code',
                       supply_price='$supply_price', 
                       shop_price='$shop_price', 
                       is_free_deliv='$is_free_deliv',
                       tax='$tax', 
                       product_id='$product_id', 
                       start_date='$start_date', 
                       end_date='$end_date', 
                       shop_id='$shop_id', 
                       update_time=Now()";
      mysql_query ( $query, $connect );

        if ( !$shop_id )        
        {
            $query = "update products 
                         set supply_code='$supply_code',
                             supply_price='$supply_price', 
                             shop_price='$shop_price', 
                             is_free_deliv='$is_free_deliv',
                             tax='$tax' 
                       where org_id='$product_id' or product_id='$product_id'";
                   mysql_query ( $query , $connect );
        }

        $link_url = $link_url ? base64_decode ( $link_url ) : "template.htm?template=C201&id=$product_id"; 

        if( $is_popup )
            $this->opener_redirect( "popup_utf8.htm?template=C208&is_popup=1&id=$product_id&link_url_list=$link_url_list" );
        else
            $this->opener_redirect( "template15.htm?template=C208&id=$product_id&link_url_list=$link_url_list" );
        $this->redirect( $self_url );
   }

    function modify_price()
    {
        global $connect, $product_id, $start_date, $end_date, $seq, $link_url, $top_url, $link_url_list;
        global $shop_id, $supply_code, $org_price, $supply_price, $shop_price, $is_free_deliv, $tax, $is_popup;
   
        if( !$shop_id ) $shop_id=0;
        $query = "update price_history set supply_code='$supply_code',
                  supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                  tax='$tax', shop_id='$shop_id', start_date='$start_date', end_date='$end_date', update_time=Now()
                  where seq='$seq'";
debug( "modify_price:".$query );
        mysql_query ( $query, $connect );

        // 기초가격일 경우, products 수정
        if( !$shop_id )
        {
            $query = "update products set shop_price='$shop_price', supply_price='$supply_price'
                      where product_id='$product_id' or org_id='$product_id'";
debug( "modify_price:".$query );
            mysql_query($query, $connect);
        }
        //$link_url = $link_url ? base64_decode ( $link_url ) : "template.htm?template=C201&id=$product_id"; 

        global $top_url;
        if( $is_popup )
            $this->opener_redirect( "template15.htm?template=C208&is_popup=1&id=$product_id&link_url_list=$link_url_list" );
        else
            $this->opener_redirect( "template15.htm?template=C208&id=$product_id&link_url_list=$link_url_list" );
        $this->closewin();
    }

    //////////////////////////////////////////////
    // 삭제 
    // date : 2005.10.10 - jk
    function del_option()
    {
        global $org_id, $product_id, $connect, $link_url;
        
        $val = array();
        
        // Lock Check
        $obj_lock = new class_lock(406);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }
        
        $transaction = $this->begin("상품삭제");
        
        $query = "update products set is_delete = 1, delete_date=Now() where product_id='$product_id'";
        mysql_query ( $query, $connect );
        
        // 매칭정보 삭제
        class_product::delete_match_info("'$product_id'");
        
        class_C::update_soldout( $org_id );

        $this->end( $transaction );
        $val['error'] = 0;

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

   function check_order()
   {
        global $connect, $product_id;

        $val = class_C::check_order($product_id);
        
        echo json_encode( $val );
   }

   function check_order2()
   {
        global $connect, $product_id;

        $val = class_C::check_order($product_id, 1);
        

        echo json_encode( $val );
   }

   // 상품정보 변경시 상품명, 바코드 중복확인
   function check_all()
   {
        global $connect, $product_id, $name, $barcode;

        $val = array();
        if( !$_SESSION[DUP_PRODUCT_NAME] && class_product::dup_check( $name, $product_id ) )
            $val[is_dup] = 1;
        else if( class_product::dup_check_barcode( $barcode, $product_id ) )
            $val[is_dup] = 2;
        else if( trim($name) == "" )
            $val[is_dup] = 3;
        else
            $val[is_dup] = 0;
        
        echo json_encode( $val );
   }

   // 옵션상품정보 변경시 옵션, 바코드 중복확인
   function check_options()
   {
        global $connect, $product_id, $options, $barcode, $org_id;

        $val = array();
        if( strtoupper(substr($product_id,0,1)) == 'S' && class_product::dup_check_options( $options, $product_id, $org_id ) )
            $val[is_dup] = 1;
        else if( class_product::dup_check_barcode( $barcode, $product_id ) )
            $val[is_dup] = 2;
        else
            $val[is_dup] = 0;
        
        echo json_encode( $val );
   }

   //////////////////////////////////////////////
   // 대표상품, 옵션상품 삭제
   function del()
   {
        global $org_id, $connect, $top_url, $link_url_list;
        
        // Lock Check
        $obj_lock = new class_lock(405);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->redirect ( "?template=C208&id=$org_id&link_url_list=$link_url_list&top_url=$top_url" );
            exit;
        }


        $transaction = $this->begin("상품삭제");

        // 대표상품, 옵션상품 목록을 만든다.
        $id_arr = array();
        $query = "select product_id from products where product_id='$org_id' or org_id='$org_id'";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $id_arr[] = "'" . $data[product_id] . "'";
            


        $id_str = implode(',', $id_arr);
        
        // 삭제처리
        $query = "update products set is_delete=1, delete_date=now() where product_id in ($id_str)";
        mysql_query ( $query, $connect );

        // match data 삭제
        class_product::delete_match_info($id_str);

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }

        $this->end( $transaction );
        $this->jsAlert ( "삭제 되었습니다");
?>
        <form name=myform_list id='myform_list' method='post' enctype='multipart/form-data' action='template15.htm?template=C200'>
<?
        $arr_pars = explode( "&", $this->base64_decode_url($link_url_list) );
        foreach( $arr_pars as $val )
        {
          $pars = explode("=", $val);
          if( $pars[0] && $pars[1] )
            echo "<input type=hidden name='" . $pars[0] . "' value='" . $this->base64_decode_url($pars[1]) . "'>";
        }
?>
        </form>
        <script>myform_list.submit()</script>
<?
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
      $this->redirect ( "?template=C201&id=$product_id" . "&top_url=$top_url" );
      exit;
   }
   
    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기
    function save_file_a()
    {
        global $connect, $supply_code, $string_type, $string, $e_sale, $date_type, $start_date, $end_date, $include_option, $sort;

        // 엑셀 헤더
        $product_data = array();
        
        $product_data["is_represent"    ] = "대표상품";
        $product_data["org_id"          ] = "대표상품코드";
        $product_data["product_id"      ] = "상품코드";
        $product_data["link_id"         ] = "연동코드";
        $product_data["name"            ] = "상품명";
        $product_data["supply_code"     ] = "공급처코드";
        $product_data["supply_name"     ] = "공급처";
        $product_data["brand"           ] = "공급처 상품명";
        $product_data["supply_options"  ] = "공급처 옵션";
        $product_data["origin"          ] = "원산지";
        $product_data["trans_code"      ] = "택배비";
        $product_data["weight"          ] = "중량";
        $product_data["org_price"       ] = "원가";
        if($_SESSION[LOGIN_ID] != 'pinkage100')
        	$product_data["supply_price"    ] = "공급가";
        $product_data["shop_price"      ] = "판매가";
        $product_data["market_price"    ] = "시중가";
        $product_data["options"         ] = "옵션";
        $product_data["stock_manage"    ] = "옵션관리";
        $product_data["barcode"         ] = "바코드";
        $product_data["img_500"         ] = "대표 이미지";
        $product_data["img_desc1"       ] = "설명 이미지1";
        $product_data["img_desc2"       ] = "설명 이미지2";
        $product_data["img_desc3"       ] = "설명 이미지3";
        $product_data["img_desc4"       ] = "설명 이미지4";
        $product_data["img_desc5"       ] = "설명 이미지5";
        $product_data["img_desc6"       ] = "비고 이미지";
        $product_data["product_desc"    ] = "상품설명";
        $product_data["product_desc2"   ] = "상품설명2";
        $product_data["enable_sale"     ] = "판매상태";
        $product_data["enable_stock"    ] = "재고관리";
        $product_data["reg_date"        ] = "등록일";
        $product_data["last_update_date"] = "갱신일";
        $product_data["stock_alarm1"    ] = "재고경고수량";
        $product_data["stock_alarm2"    ] = "재고위험수량";
        $product_data["del"             ] = "삭제";
        $product_data["pack_disable"    ] = "합포불가";
        $product_data["pack_cnt"        ] = "동일상품 합포가능 수량";
        $product_data["sale_stop_date"  ] = "품절일";
        $product_data["memo"            ] = "상품메모";
        $product_data["category"        ] = "카테고리";
        $product_data["location"        ] = "로케이션";
        $product_data["maker"           ] = "제조사";
        $product_data["product_gift"    ] = "사은품";
        $product_data["md"              ] = "담당MD";
        $product_data["manager1"        ] = "관리자(정)";
        $product_data["manager2"        ] = "관리자(부)";
        $product_data["is_free_deliv"   ] = "무료배송";
        $product_data["no_sync"         ] = "상품동기화 미사용";
        $product_data["link_id"         ] = "동기화코드";
        $product_data["trans_type"      ] = "배송타입";
        $product_data["rt_disable"      ] = "매장간이동(RT) 불가";
        
        
        
        
        // 원가조회불가
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) !== FALSE )
            unset($product_data[0]["org_price"]);

debug("다운로드 start");
        $result = class_C::get_list( &$cnt_all, $page, 1 );
        while( $data = mysql_fetch_assoc($result) )
        {
debug("다운로드 product_id : " . $data[product_id]);
            // 상품동기화
            if( $data[no_sync] )
                $sync_set = 1;
            else if( $data[no_stock_sync] )
                $sync_set = 2;
            else
                $sync_set = 0;
                
            $product_desc = stripslashes(htmlspecialchars(htmlspecialchars_decode($data[product_desc])));
            $product_desc2 = $data[product_desc2];

            $info = array();
			$info["is_represent"    ] = $data[is_represent    ] ? $data[is_represent    ] : "";
            $info["org_id"          ] = $data[org_id          ] ? $data[org_id          ] : "";
            $info["product_id"      ] = $data[product_id      ] ? $data[product_id      ] : "";
            $info["link_id"         ] = $data[link_id         ] ? $data[link_id         ] : "";
            $info["name"            ] = $data[name            ] ? $data[name            ] : "";
            $info["supply_code"     ] = $data[supply_code     ] ? $data[supply_code     ] : "";
            $info["supply_name"     ] = class_supply::get_name( $data[supply_code] );
            $info["brand"           ] = $data[brand           ] ? $data[brand           ] : "";
            $info["supply_options"  ] = $data[supply_options  ] ? $data[supply_options  ] : "";
            $info["origin"          ] = $data[origin          ] ? $data[origin          ] : "";
            $info["trans_fee"       ] = $data[trans_fee       ] ? $data[trans_fee       ] : "";
            $info["weight"          ] = $data[weight          ] ? $data[weight          ] : "";
            $info["org_price"       ] = $data[org_price       ] ? $data[org_price       ] : "";
            if( $_SESSION[LOGIN_ID] != 'pinkage100')
            	$info["supply_price"    ] = $data[supply_price    ] ? $data[supply_price    ] : "";
            $info["shop_price"      ] = $data[shop_price      ] ? $data[shop_price      ] : "";
            $info["market_price"    ] = $data[market_price    ] ? $data[market_price    ] : "";
            $info["options"         ] = $data[options         ] ? $data[options         ] : "";
            $info["stock_manage"    ] = $data[stock_manage    ] ? $data[stock_manage    ] : "0";
            $info["barcode"         ] = $data[barcode         ] ? $data[barcode         ] : "";
            $info["img_500"         ] = $data[img_500         ] ? $data[img_500         ] : "";
            $info["img_desc1"       ] = $data[img_desc1       ] ? $data[img_desc1       ] : "";
            $info["img_desc2"       ] = $data[img_desc2       ] ? $data[img_desc2       ] : "";
            $info["img_desc3"       ] = $data[img_desc3       ] ? $data[img_desc3       ] : "";
            $info["img_desc4"       ] = $data[img_desc4       ] ? $data[img_desc4       ] : "";
            $info["img_desc5"       ] = $data[img_desc5       ] ? $data[img_desc5       ] : "";
            $info["img_desc6"       ] = $data[img_desc6       ] ? $data[img_desc6       ] : "";
            $info["product_desc"    ] = $product_desc ? $product_desc : "";
            $info["product_desc2"   ] = $product_desc2 ? $product_desc2 : "";
            $info["enable_sale"     ] = $data[enable_sale     ] ? $data[enable_sale     ] : "0";
            $info["enable_stock"    ] = $data[enable_stock    ] ? $data[enable_stock    ] : "0";
            $info["reg_date"        ] = $data[reg_date] . " " . $data[reg_time];
            $info["last_update_date"] = $data[last_update_date] ? $data[last_update_date] : "";
            $info["stock_alarm1"    ] = $data[stock_alarm1    ] ? $data[stock_alarm1    ] : "";
            $info["stock_alarm2"    ] = $data[stock_alarm2    ] ? $data[stock_alarm2    ] : "";
            $info["del"             ] = "";
            $info["pack_disable"    ] = $data[pack_disable];
            $info["pack_cnt"        ] = $data[pack_cnt];
            $info["sale_stop_date"  ] = ($data[is_represent] ? "" : ($data[enable_sale]=="1" ? "" : $data[sale_stop_date]));
            $info["memo"            ] = $data[memo];
            $info["category"        ] = $_SESSION[MULTI_CATEGORY] ? class_multicategory::get_category_str($data[str_category]) : class_category::get_category_name( $data[category] );
            $info["location"        ] = $data[location];
            $info["maker"           ] = $data[maker];
            $info["product_gift"    ] = $data[product_gift];
            $info["md"              ] = $data[md];
            $info["manager1"        ] = $data[manager1];
            $info["manager2"        ] = $data[manager2];
            $info["is_free_deliv"   ] = $data[is_free_deliv];
            $info["no_sync"         ] = $sync_set;
            $info["link_id"         ] = $data[link_id];
            $info["trans_type"      ] = $data[trans_type];
            $info["rt_disable"      ] = $data[rt_disable];
            
            
            // 원가조회불가
            if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) !== FALSE )
                unset($info["org_price"]);

            $product_data[] = $info;
            if( $include_option == "true" )
            {
                // 옵션 상품 있는지 확인
                $query = "select * from products where org_id='$data[product_id]' and is_delete=0";
                $result_opt = mysql_query( $query, $connect );
                if( mysql_num_rows($result_opt) > 0 )
                {
                    while( $data_opt = mysql_fetch_assoc($result_opt) )
                    {
                        $info[is_represent]   = "";
                        $info[org_id]         = $data_opt[org_id];
                        $info[product_id]     = $data_opt[product_id];
                        $info[link_id   ]     = $data_opt[link_id];
                        $info[supply_options] = $data_opt[supply_options];
                        $info[options]        = $data_opt[options];
                        $info[barcode]        = $data_opt[barcode];
                        $info[enable_sale]    = $data_opt[enable_sale];

                        $info[img_500]        = $data[img_500         ] ? $data[img_500         ] : "";
                        $info[img_desc1]      = $data[img_desc1       ] ? $data[img_desc1       ] : "";
                        $info[img_desc2]      = $data[img_desc2       ] ? $data[img_desc2       ] : "";
                        $info[img_desc3]      = $data[img_desc3       ] ? $data[img_desc3       ] : "";
                        $info[img_desc4]      = $data[img_desc4       ] ? $data[img_desc4       ] : "";
                        $info[img_desc5]      = $data[img_desc5       ] ? $data[img_desc5       ] : "";
                        $info[img_desc6]      = $data[img_desc6       ] ? $data[img_desc6       ] : "";

                        $info[product_desc]   = "";
                        $info[product_desc2]   = "";

                        $info[sale_stop_date] = ($data_opt[enable_sale]=="1" ? "" : $data_opt[sale_stop_date]);
                        $info[memo]           = $data_opt[memo];
                        $info[location]       = $data_opt[location];
                        $info[link_id]        = $data_opt[link_id];
                        $info[stock_alarm1]   = $data_opt[stock_alarm1] ? $data_opt[stock_alarm1] : "";
                        $info[stock_alarm2]   = $data_opt[stock_alarm2] ? $data_opt[stock_alarm2] : "";

                        $product_data[] = $info;
                    }
                }
            }
            
            $i++;
            if( $i % 73 == 0 )
            {
                $msg = " $i / $cnt_all ";
                echo "<script language='javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
        }
debug("다운로드 end");
debug("파일 만들기 start");
        $this->make_file( $product_data, "download.xls" );
debug("파일 만들기 end");
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

   function make_file( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
   {
	    $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<style>
.num_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:" . '"0_ \;\[Red\]\\-0\\ "' . ";
}
.str_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
}
.mul_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
    white-space:normal;
}
.mul_item2{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    white-space:normal;
}
br
	{mso-data-placement:same-cell;}
</style>
<body>
<html><table border=1>
";
        fwrite($handle, $buffer);

        // for row
        for( $i=0; $i < count( $arr_datas ); $i++ )
        {
            $row = $arr_datas[$i];
            $buffer = "<tr>\n";

            if( $i == 0 )
            {
                // for column
                foreach ( $row as $key=>$value) 
                    $buffer .= "<td style='font:bold 10pt \"굴림\"; white-space:nowrap; background:#CCFFCC;'>" . $value . "</td>";
            }
            else
            {
                // for column
                foreach ( $row as $key=>$value) 
                {
                    if( $key == 'org_price' || $key == 'supply_price' || $key == 'shop_price' || $key == 'weight' || $key == 'market_price' || $key == 'trans_fee' || 
                        $key == 'is_represent' || $key == 'stock_manage' || $key == 'enable_sale' || $key == 'enable_stock' || $key == 'pack_disable' || $key == 'rt_disable' || $key == 'pack_cnt' || $key == 'no_sync'  || $key == 'no_stock_sync' )
                        $buffer .= "<td class=num_item>" . $value . "</td>";
                    else if( $key == 'options' )
                        $buffer .= "<td class=mul_item>" . str_replace("\n", "<br>", $value) . "</td>";
                    else if( $key == 'product_desc' || $key == 'product_desc2' )
                    {
                        if( strlen($value) > 100 )
                            $buffer .= "<td class=mul_item2>" . str_replace("\n", "<br>", $value) . "</td>";
                        else
                            $buffer .= "<td class=mul_item>" . str_replace("\n", "<br>", $value) . "</td>";
                    }
                    else
                        $buffer .= "<td class=str_item>" . $value . "</td>";
                }
            }
            $buffer .= "</tr>\n";

            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");

        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        fclose($fp);

        return $filename; 
   }





    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기
    function save_file()
    {
        global $connect, $supply_code, $string_type, $string, $e_sale, $date_type, $start_date, $end_date, $include_option, $sort;

        $now_str = date("YmdHis");

        $filename = "products_download_$now_str.xls";
        $is_html = 1;
        
	    $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<style>
.num_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:" . '"0_ \;\[Red\]\\-0\\ "' . ";
}
.str_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
}
.mul_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
    white-space:normal;
}
.mul_item2{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    white-space:normal;
}
br
	{mso-data-placement:same-cell;}
</style>
<body>
<html><table border=1>
";
        fwrite($handle, $buffer);




        // 엑셀 헤더
		$header_arr = array();
        $header_arr["is_represent"    ] = "대표상품";
        $header_arr["org_id"          ] = "대표상품코드";
        $header_arr["product_id"      ] = "상품코드";
        $header_arr["name"            ] = "상품명";
        $header_arr["supply_code"     ] = "공급처코드";
        $header_arr["supply_name"     ] = "공급처";
        $header_arr["brand"           ] = "공급처 상품명";
        $header_arr["supply_options"  ] = "공급처 옵션";
        $header_arr["origin"          ] = "원산지";
        $header_arr["trans_fee"      ] = "택배비";
        $header_arr["weight"          ] = "중량";
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) == FALSE )
        	$header_arr["org_price"       ] = "원가";
        if( $_SESSION[LOGIN_ID] != 'pinkage100')
     	   $header_arr["supply_price"    ] = "공급가";
        $header_arr["shop_price"      ] = "판매가";
        $header_arr["market_price"    ] = "시중가";
        $header_arr["options"         ] = "옵션";
        $header_arr["stock_manage"    ] = "옵션관리";
        $header_arr["barcode"         ] = "바코드";
        $header_arr["img_500"         ] = "대표 이미지";
        $header_arr["img_desc1"       ] = "설명 이미지1";
        $header_arr["img_desc2"       ] = "설명 이미지2";
        $header_arr["img_desc3"       ] = "설명 이미지3";
        $header_arr["img_desc4"       ] = "설명 이미지4";
        $header_arr["img_desc5"       ] = "설명 이미지5";
        $header_arr["img_desc6"       ] = "비고 이미지";
        $header_arr["product_desc"    ] = "상품설명";
        $header_arr["product_desc2"   ] = "상품설명2";
        $header_arr["enable_sale"     ] = "판매상태";
        $header_arr["enable_stock"    ] = "재고관리";
        $header_arr["reg_date"        ] = "등록일";
        $header_arr["option_reg_date" ] = "옵션등록일";
        $header_arr["last_update_date"] = "갱신일";
        $header_arr["stock_alarm1"    ] = "재고경고수량";
        $header_arr["stock_alarm2"    ] = "재고위험수량";
        $header_arr["del"             ] = "삭제";
        $header_arr["pack_disable"    ] = "합포불가";
        $header_arr["pack_cnt"        ] = "동일상품 합포가능 수량";
        $header_arr["sale_stop_date"  ] = "품절일";
        $header_arr["memo"            ] = "상품메모";
        $header_arr["category"        ] = "카테고리";
        $header_arr["location"        ] = "로케이션";
        $header_arr["maker"           ] = "제조사";
        $header_arr["product_gift"    ] = "사은품";
        $header_arr["md"              ] = "담당MD";
        $header_arr["manager1"        ] = "관리자(정)";
        $header_arr["manager2"        ] = "관리자(부)";
        $header_arr["is_free_deliv"   ] = "무료배송";
        $header_arr["no_sync"         ] = "상품동기화 미사용";
        $header_arr["link_id"         ] = "동기화코드";
        $header_arr["trans_type"      ] = "배송타입";        
        $header_arr["rt_disable"      ] = "매장간이동(RT) 불가";
        $header_arr["extra_price"     ] = "옵션추가금액";
        $header_arr["sale_date"		  ] = "판매시작일";
        $header_arr["reserve_qty"	  ] = "입고대기";        
        $header_arr["ecn_is_hide"      ] = "상품검색 불가";

        if( _DOMAIN_ == 'jucifaci' || _DOMAIN_ == 'sbs' || _DOMAIN_ == 'soramam'  )
        {
            $header_arr["stock"] = "정상재고";
        }

        if( $_SESSION[CAFE24_SOLUP] )
            $header_arr["shop_product_code"] = "(신)연동코드";

        $this->make_file_direct($header_arr, $handle);
    
        // 원가조회불가
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) !== FALSE )
            unset($product_data[0]["org_price"]);

        $result = class_C::get_list( &$cnt_all, $page, 1 );
        while( $data = mysql_fetch_assoc($result) )
        {
            // 상품동기화
            if( $data[no_sync] )
                $sync_set = 1;
            else if( $data[no_stock_sync] )
                $sync_set = 2;
            else
                $sync_set = 0;
                
            $product_desc = stripslashes(htmlspecialchars(htmlspecialchars_decode($data[product_desc])));
            $product_desc2 = $data[product_desc2];

            $info = array();
            $info["is_represent"    ] = $data[is_represent    ] ? $data[is_represent    ] : "";
            $info["org_id"          ] = $data[org_id          ] ? $data[org_id          ] : "";
            $info["product_id"      ] = $data[product_id      ] ? $data[product_id      ] : "";
            $info["name"            ] = $data[name            ] ? $data[name            ] : "";
            $info["supply_code"     ] = $data[supply_code     ] ? $data[supply_code     ] : "";
            $info["supply_name"     ] = class_supply::get_name( $data[supply_code] );
            $info["brand"           ] = $data[brand           ] ? $data[brand           ] : "";
            $info["supply_options"  ] = $data[supply_options  ] ? $data[supply_options  ] : "";
            $info["origin"          ] = $data[origin          ] ? $data[origin          ] : "";
            $info["trans_fee"       ] = $data[trans_fee       ] ? $data[trans_fee       ] : "";
            $info["weight"          ] = $data[weight          ] ? $data[weight          ] : "";
            $info["org_price"       ] = $data[org_price       ] ? $data[org_price       ] : "";
            if(  $_SESSION[LOGIN_ID] != 'pinkage100')
            	$info["supply_price"    ] = $data[supply_price    ] ? $data[supply_price    ] : "";
            $info["shop_price"      ] = $data[shop_price      ] ? $data[shop_price      ] : "";
            $info["market_price"    ] = $data[market_price    ] ? $data[market_price    ] : "";
            $info["options"         ] = $data[options         ] ? $data[options         ] : "";
            $info["stock_manage"    ] = $data[stock_manage    ] ? $data[stock_manage    ] : "0";
            $info["barcode"         ] = $data[barcode         ] ? $data[barcode         ] : "";
            $info["img_500"         ] = $data[img_500         ] ? $data[img_500         ] : "";
            $info["img_desc1"       ] = $data[img_desc1       ] ? $data[img_desc1       ] : "";
            $info["img_desc2"       ] = $data[img_desc2       ] ? $data[img_desc2       ] : "";
            $info["img_desc3"       ] = $data[img_desc3       ] ? $data[img_desc3       ] : "";
            $info["img_desc4"       ] = $data[img_desc4       ] ? $data[img_desc4       ] : "";
            $info["img_desc5"       ] = $data[img_desc5       ] ? $data[img_desc5       ] : "";
            $info["img_desc6"       ] = $data[img_desc6       ] ? $data[img_desc6       ] : "";
            $info["product_desc"    ] = $product_desc ? $product_desc : "";
            $info["product_desc2"   ] = $product_desc2 ? $product_desc2 : "";
            $info["enable_sale"     ] = $data[enable_sale     ] ? $data[enable_sale     ] : "0";
            $info["enable_stock"    ] = $data[enable_stock    ] ? $data[enable_stock    ] : "0";
            $info["reg_date"        ] = $data[reg_date] . " " . $data[reg_time];
            $info["option_reg_date" ] = $data[reg_date] . " " . $data[reg_time];
            $info["last_update_date"] = $data[last_update_date] ? $data[last_update_date] : "";
            $info["stock_alarm1"    ] = $data[stock_alarm1    ] ? $data[stock_alarm1    ] : "";
            $info["stock_alarm2"    ] = $data[stock_alarm2    ] ? $data[stock_alarm2    ] : "";
            $info["del"             ] = "";
            $info["pack_disable"    ] = $data[pack_disable];
            $info["pack_cnt"        ] = $data[pack_cnt];
            $info["sale_stop_date"  ] = ($data[is_represent] ? "" : ($data[enable_sale]=="1" ? "" : $data[sale_stop_date]));
            $info["memo"            ] = $data[memo];
            $info["category"        ] = $_SESSION[MULTI_CATEGORY] ? class_multicategory::get_category_str($data[str_category]) : class_category::get_category_name( $data[category] );
            $info["location"        ] = $data[location];
            $info["maker"           ] = $data[maker];
            $info["product_gift"    ] = $data[product_gift];
            $info["md"              ] = $data[md];
            $info["manager1"        ] = $data[manager1];
            $info["manager2"        ] = $data[manager2];
            $info["is_free_deliv"   ] = $data[is_free_deliv];
            $info["no_sync"         ] = $sync_set;
            $info["link_id"         ] = $data[link_id];
            $info["trans_type"      ] = $data[trans_type];            
            $info["rt_disable"      ] = $data[rt_disable];
        	$info["extra_price"     ] = $data[extra_price];
        	$info["sale_date"       ] = $data[sale_date];
        	$info["reserve_qty"     ] = $data[reserve_qty];        	
        	$info["ecn_is_hide"     ] = $data[ecn_is_hide];

            if( _DOMAIN_ == 'jucifaci' || _DOMAIN_ == 'sbs' || _DOMAIN_ == 'soramam')
            {
            	$query_stock = "select sum(a.stock) stock from current_stock a,products b where a.product_id = b.product_id and b.org_id='$data[product_id]' AND b.is_delete=0 AND a.bad = 0";
            	

                $result_stock = mysql_query($query_stock, $connect);
                $data_stock = mysql_fetch_assoc($result_stock);

                $info['stock']      = $data_stock[stock];
                
                $data_stock[stock]  = 0;
            }

            if( $_SESSION[CAFE24_SOLUP] )
                $info["shop_product_code"] = $data[shop_product_code];

            // 원가조회불가
            if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) !== FALSE )
                unset($info["org_price"]);

            $this->make_file_direct($info, $handle,1);
            
            
            if( $include_option == "true" )
            {
                // 옵션 상품 있는지 확인
                $query = "select * from products where org_id='$data[product_id]' and is_delete=0";
                $result_opt = mysql_query( $query, $connect );
                if( mysql_num_rows($result_opt) > 0 )
                {
                    while( $data_opt = mysql_fetch_assoc($result_opt) )
                    {
                        $info[is_represent]   = "";
                        $info[org_id]         = $data_opt[org_id];
                        $info[product_id]     = $data_opt[product_id];
                        $info[supply_options] = $data_opt[supply_options];
                        $info[options]        = $data_opt[options];
                        $info[barcode]        = $data_opt[barcode];
                        $info[enable_sale]    = $data_opt[enable_sale];

                        $info[option_reg_date]= $data_opt[reg_date] . " " . $data_opt[reg_time];

                        $info[img_500]        = $data[img_500         ] ? $data[img_500         ] : "";
                        $info[img_desc1]      = $data[img_desc1       ] ? $data[img_desc1       ] : "";
                        $info[img_desc2]      = $data[img_desc2       ] ? $data[img_desc2       ] : "";
                        $info[img_desc3]      = $data[img_desc3       ] ? $data[img_desc3       ] : "";
                        $info[img_desc4]      = $data[img_desc4       ] ? $data[img_desc4       ] : "";
                        $info[img_desc5]      = $data[img_desc5       ] ? $data[img_desc5       ] : "";
                        $info[img_desc6]      = $data[img_desc6       ] ? $data[img_desc6       ] : "";

                        $info[product_desc]   = "";
                        $info[product_desc2]   = "";

                        $info[sale_stop_date] = ($data_opt[enable_sale]=="1" ? "" : $data_opt[sale_stop_date]);
                        $info[memo]           = $data_opt[memo];
                        $info[location]       = $data_opt[location];
                        $info[link_id]        = $data_opt[link_id];
                        $info[stock_alarm1]   = $data_opt[stock_alarm1] ? $data_opt[stock_alarm1] : "";
                        $info[stock_alarm2]   = $data_opt[stock_alarm2] ? $data_opt[stock_alarm2] : "";
                        
                        $info[extra_price]        = $data_opt[extra_price];
                        $info[reserve_qty]        = $data_opt[reserve_qty	];

                        if( _DOMAIN_ == 'jucifaci' || _DOMAIN_ == 'sbs' || _DOMAIN_ == 'soramam')
                        {
                            $query_stock = "select * from current_stock where product_id='$data_opt[product_id]' AND bad = 0";
                            $result_stock = mysql_query($query_stock, $connect);
                            $data_stock = mysql_fetch_assoc($result_stock);
                            
                            $info['stock']      = $data_stock[stock];
                        }
                        $this->make_file_direct($info, $handle,1);
                    }
                }
            }
            
            $i++;
            if( $i % 73 == 0 )
            {
                $msg = " $i / $cnt_all ";
                echo "<script language='javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
        }

        fwrite($handle, "</table>");

        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        fclose($fp);

        echo "<script language='javascript'>parent.set_file('$filename')</script>";
    }

   function make_file_direct( $row, $handle, $i=0)
   {
        // 대표상품 쓰기
        $buffer = "<tr>\n";

        if( $i == 0 )
        {
            // for column
            foreach ( $row as $key=>$value) 
                $buffer .= "<td style='font:bold 10pt \"굴림\"; white-space:nowrap; background:#CCFFCC;'>" . $value . "</td>";
        }
        else
        {
            // for column
            foreach ( $row as $key=>$value) 
            {
                if( $key == 'org_price' || $key == 'supply_price' || $key == 'shop_price' || $key == 'market_price' || $key == 'trans_fee' || 
                    $key == 'is_represent' || $key == 'stock_manage' || $key == 'enable_sale' || $key == 'enable_stock' || $key == 'pack_disable' || $key == 'rt_disable'|| $key == 'ecn_is_hide' || $key == 'pack_cnt' || $key == 'no_sync'  || $key == 'no_stock_sync' || $key == 'stock' || $key == 'stock_bad'  || $key == 'extra_price')
                    $buffer .= "<td class=num_item>" . $value . "</td>";
                else if( $key == 'options' )
                    $buffer .= "<td class=mul_item>" . str_replace("\n", "<br>", $value) . "</td>";
                else if( $key == 'product_desc' || $key == 'product_desc2' )
                {
                    if( strlen($value) > 100 )
                        $buffer .= "<td class=mul_item2>" . str_replace("\n", "<br>", $value) . "</td>";
                    else
                        $buffer .= "<td class=mul_item>" . str_replace("\n", "<br>", $value) . "</td>";
                }
                else
                    $buffer .= "<td class=str_item>" . $value . "</td>";
            }
        }
        $buffer .= "</tr>\n";

        fwrite($handle, $buffer);
   }





    // 품절 정보
    function get_enable_sale($val)
    {
        switch( $val )
        {
            case 0: return "판매불가";
            case 1: return "판매가능";
            case 2: return "부분품절";
        }
    }

    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 다운받기
    function download2()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "products_list.xls");
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
          case "trans_code":
                  return  $this->get_trans_code( $data[trans_code] );
              break;
          default:
              $val = $data[$key] ? $data[$key] : "";
              return  str_replace( array("\r", "\n", "\r\n","\t" ), " ", $val );
                // return $val;
           break; 
      }
   }
 
   function get_trans_code( $trans_code )
   {
        global $connect;
        $query = "select * From trans_price where trans_code='$trans_code'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        return " $trans_code ( $data[price1] / $data[price2] / $data[price3] / )";

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
   function C217()
   {
      global $template;
      global $connect;
    
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   // 전시물품등록
   function C218()
   {
      global $template;
      global $connect;
    
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // 상품메모
   function C219()
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
                session_write_close();  //Close the session before proc_open()
                exec($cmd);
                session_start(); //restore session

            }
            $img_250 = _save_dir . str_replace("_500", "_250", $list[img_500]);
            if (!file_exists($img_250))
            {
                $cmd = "/usr/local/bin/convert -resize 250x250 "._save_dir.$list[img_500]." ".$img_250;
                session_write_close();  //Close the session before proc_open()
                exec($cmd);
                session_start(); //restore session
            }
        }

        if ($list0[product_id])                // update
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
        else                                // insert
        {
            ////////////////////////////////////////
            $ins_sql = "insert into ez_store set
                        domain                 = '" . _DOMAIN_ . "',
                        product_id         = '$list[product_id]',
                        name                = '$list[name]',
                        origin                = '$list[origin]',
                        brand                = '$list[brand]',
                        options                = '$list[options]',
                        shop_price        = '$list[shop_price]',
                        trans_fee        = '$list[trans_fee]',
                        product_desc        = '$list[product_desc]',
                        img_500                = '$list[img_500]',
                        img_desc1        = '$list[img_desc1]',
                        img_desc2        = '$list[img_desc2]',
                        img_desc3        = '$list[img_desc3]',
                        img_desc4        = '$list[img_desc4]',
                        img_desc5        = '$list[img_desc5]',
                        img_desc6        = '$list[img_desc6]',
                        reg_date         = now(),
                        reg_time         = now(),
                        is_sale         = 1
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
        
        $this->redirect("popup.htm?template=C218&product_id=$product_id");
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

        $this->redirect("popup.htm?template=C218&product_id=$product_id");
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

        $this->redirect("popup.htm?template=C218&product_id=$product_id");
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

        $this->redirect("popup.htm?template=C217&product_id=$product_id");
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

        $this->redirect("popup.htm?template=C217&product_id=$product_id");
        exit;
   }

   function C230()
   {
      global $template, $id;

      $end_date= date('Y-m-d', strtotime('2 year'));

      // 상세 정보 가져온다
      $data = $this->get_supply_code( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   
   function C231()
   {
      global $template, $id;

      // 상세 정보 가져온다
      $data = class_product::get_info( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

    // 상품정보 동기화.   
   function C242()
   {
      global $template, $id,$connect;

      if( $_SESSION[SYNC_START_DATE] != "0000-00-00" )
        $start_date = $_SESSION[SYNC_START_DATE];
      else if(_DOMAIN_ == "flyday" || _DOMAIN_ == "dabagirl2")
      	$start_date = date('Y-m-d');
      else
        $start_date = date('Y-m-d', strtotime('-100 days'));
      
      // dodry는 9월 11일 이후 상품만 동기화.
      if ( _DOMAIN_ == "dodry" || _DOMAIN_ == "mixxmix" )
        $start_date = date('Y-m-d', strtotime('-1 days'));
      else if ( _DOMAIN_ == "zangternet"  || _DOMAIN_ == "chlije" || _DOMAIN_ == "anappletree" )      
        $start_date = date('Y-m-d', strtotime('-7 days'));
           
      // hckim1515는 2014-01-01 이후
      if( _DOMAIN_ == 'hckim1515' && $start_date < '2014-01-01' )
        $start_date = '2014-02-01';

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   
    function C243()
    {
        global $template, $connect;
        
        foreach($_REQUEST as $key => $val) $$key = $val;
        
        $par = array("template","action","start_date","end_date", "query_opt", "query_str");
        $link_url = $this->build_link_url3( $par );
        
        if ( $page )
            $result = $this->search_history( &$total_rows, $page);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
   
    // 동기화 이력
    function search_history(&$max_row, $page)
    {
        global $connect, $query_opt, $query_str, $start_date, $end_date;

        $query = "select a.* 
                    from product_sync_log a,
                         products       b
                   where a.product_id = b.product_id
                     and a.update_date     >= '$start_date 00:00:00'
                     and a.update_date     <= '$end_date 23:59:59'";

        if ( $query_str )
        {
            $query .= " and b.".$query_opt." like '%$query_str%'";   
        }
                     
        $order .= " order by a.update_date desc, a.product_id";

        $result = mysql_query( $query,$connect );
        
        $max_row = mysql_num_rows( $result );
        
        if ( !$page ) $page = 1;
	        $starter = ($page-1) * 500;
            
            
        $limit = " limit $starter, 500";
        
        $query_final = $query . $order . $limit;
        
        $result = mysql_query ( $query_final, $connect );

        return $result;
    }
    
    function get_pinfo( $product_id )
    {
        global $connect;
        
        $query = "select name,options,enable_sale from products where product_id = '$product_id'";
        
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        
        return $data;   
    }
   
    //************************************* 
    // cafe24, makeshop 상품 정보 동기화 이력.
    // date: 2011.9.15 - jk
    //
    function get_history_list()
    {
        global $connect;
        
        $query = "select * from product_sync_history order by seq desc limit 100";
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
			$data[start_date] = substr($data[start_date],0,10);
			$data[end_date]  = substr($data[end_date],0,10);

            $arr_result[] = $data;   
        }
        
        echo json_encode( $arr_result );
    }
    
    
    function C240()
    {
        global $template, $connect, $search_type, $string, $act, $s_group_id;
  
        if ( $act == "add" )
        {
            // 등일한 이름이 없는지 확인
            $query = "select * from userinfo 
                       where name = '" . $string . "'";
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );            
            $row    = mysql_num_rows( $result );
            
            if ( $row > 0 )
            {
                // 이미 등록..
                $query = "select * from userinfo 
                       where code like '%" . $string . "%' 
                          or name like '%" . $string . "%'";
                $result = mysql_query( $query, $connect );   
            }else{
                
                // get code
                $query = "select max(code) m_code from userinfo ";
                $result = mysql_query( $query, $connect );
                $data   = mysql_fetch_assoc( $result );
                
                $m_code = $data[m_code] + 1;
                
                // 추가
                $query = "insert into userinfo set name='$string', level=0, id='$string', code=$m_code";
                echo $query;
                mysql_query( $query, $connect );
                
                // 검색
                $query = "select * from userinfo 
                       where name = '" . $string . "'";
                $result = mysql_query( $query, $connect );
            }
            echo "<script language='javascript'>opener.supply_refresh()</script>";                
        }
        else
        {  
            if( $string )
            {
                $query = "select * from userinfo where level=0 ";

                if( $search_type == 1 )
                    $query .= " and name like '%$string%' ";
                else if( $search_type == 2 )
                    $query .= " and code = '$string' ";
                else if( $search_type == 3 )
                    $query .= " and id like '%$string%' ";

                if( $s_group_id )
                    $query .= " and group_id = '$s_group_id' ";
                $result = mysql_query($query, $connect);
            }
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function get_category_name()
    {
        global $connect, $category;
        
        $query = "select * from category where seq='$category'";
        $arr_data = array();
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        echo json_encode( $data );
    }
   
    // 공그업체 list
    function get_supply_list()
    {
        global $connect;
        
        $query = "select * from userinfo where level=0 order by name";
        $arr_data = array();
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_data[] = array( seq => $data[code], name => $data[name] );   
        }   
        
        echo json_encode( $arr_data );
    }
    
    function get_category_list()
    {
        global $connect;
        
        $query = "select * from category order by name";
        $arr_data = array();
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_data[] = array( seq => $data[seq], name => $data[name] );   
        }   
        
        echo json_encode( $arr_data );
    }
    
    function C241()
    {
        global $template, $connect, $string, $act, $seq;
        
        switch( $act )
        {
            case "":
            case "search":
                $query = "select * from category 
                           where seq like '%" . $string . "%' 
                              or name like '%" . $string . "%'";
                $result = mysql_query( $query, $connect );
                break;   
            case "add":
                // 등일한 이름이 없는지 확인
                $query = "select * from category 
                           where name = '" . $string . "'";
                $result = mysql_query( $query, $connect );
                $data   = mysql_fetch_assoc( $result );            
                $row    = mysql_num_rows( $result );
                
                if ( $row > 0 )
                {
                    // 이미 등록..
                    $query = "select * from category 
                           where seq like '%" . $string . "%' 
                              or name like '%" . $string . "%'";
                    $result = mysql_query( $query, $connect );   
                }else{
                    // 추가
                    $query = "insert into category set name='$string'";
                    mysql_query( $query, $connect );
                    
                    // 검색
                    $query = "select * from category 
                           where name = '" . $string . "'";
                    $result = mysql_query( $query, $connect );
                }
                echo "<script language='javascript'>opener.category_refresh()</script>";                
                break;
            case "del":                
                $query = "delete from category where seq=$seq";
                mysql_query( $query, $connect );
                
                // 검색
                $query = "select * from category 
                           where seq like '%" . $string . "%' 
                              or name like '%" . $string . "%'";
                $result = mysql_query( $query, $connect );
                echo "<script language='javascript'>opener.category_refresh()</script>";
                break;
        }
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    // 상품의 공급처 변경
    function change_supply()
    {
        global $connect, $product_id, $supply_code, $change_mode, $start_date, $link_url_list, $is_popup, $is_option;
        
        // 상품코드가 S로 시작하는 옵션상품일 경우 처리 안함
        if( substr($product_id, 0, 1) == 'S' && $is_option == 0)  return;
        // 상품코드가 공백일 경우 처리 안함
        if( $product_id == '' )  return;
        
        // 옵션상품코드 목록
        if($is_option)
        	$p_list = "'".$product_id."'";
        else
        {
	        $pid_list = class_product::get_child_product_id( $product_id );
	        $p_list = "'$product_id'" . ( $pid_list ? ", $pid_list" : "" ); 
        }
        
        
        
        // 공급처 코드 변경
        $query = "update products set supply_code=$supply_code where product_id in ($p_list)";
debug("공급처 코드 변경 : " . $query );
        mysql_query($query, $connect);
        
        // 과거주문 변경 - 전체
        if( $change_mode == 2 )
        {
            $query = "update order_products set supply_id=$supply_code where product_id in ($p_list)";
            mysql_query($query, $connect);
        }
        // 과거주문 변경 - 날짜 이후
        else if( $change_mode == 3 )
        {
            $query = "update order_products as a inner join orders b on (b.seq=a.order_seq) set a.supply_id=$supply_code 
                      where b.collect_date > '$start_date' and a.product_id in ($p_list)";
            mysql_query( $query, $connect );
        }

        // price history 수정
        $query = "update price_history set supply_code=$supply_code where product_id in ($p_list)";
        mysql_query($query, $connect);
        
        if( $is_popup )
        {
debug(" 공급처 수정 : " . class_table::get_change_supply_data($product_id) );            
            echo "<script type='text/javascript'>";
            echo "opener.opener.update_change_supply_info('".class_table::get_change_supply_data($product_id)."');";
            echo "</script>";
            
            $this->opener_redirect("popup_utf8.htm?template=C208&is_popup=1&id=$product_id&link_url_list=$link_url_list");
        }
        else
            $this->opener_redirect("template15.htm?template=C208&id=$product_id&link_url_list=$link_url_list");

        echo "<script>";
        echo "self.close();";
        echo "</script>";
    }

    // 옵션 상품 개별 변경
    function change_option_product()
    {
        global $connect, $product_id, $barcode, $location, $options, $supply_options, $enable_sale, $org_id, $stock_alarm1, $stock_alarm2, $stock_sync, $reserve_qty, $memo, $cs_open;
        
        // 로그
        $query = "select enable_sale from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        if( $data[enable_sale] != $enable_sale )
            class_C::insert_products_history($product_id, ($enable_sale ? "판매가능" : "품절"), "옵션상품 정보수정에서 판매상태수정");

        $query = "update products 
                     set barcode = '$barcode',
                         location = '$location',
                         options = '$options',
                         supply_options = '$supply_options',";
        // 품절
        if( !$enable_sale )
            $query .=  " sale_stop_date = if(enable_sale=1, now(), sale_stop_date), ";

        $query .=      " enable_sale = '$enable_sale',
                         stock_alarm1 = '$stock_alarm1',
                         stock_alarm2 = '$stock_alarm2', 
                         reserve_qty  = '$reserve_qty',
                         memo         = '$memo',
                         last_update_date = now() " . 
                  "where product_id='$product_id'";
        mysql_query( $query );      
debug("옵션 상품 개별 변경:".$query);        
        
        class_C::update_soldout( $org_id );

        if( $cs_open )
            echo "<script>opener.cs_reload();self.close()</script>";
        else
            echo "<script>opener.dispOptionList(0);self.close()</script>";
    }
    
    // 옵션상품 전체 품절/판매가능 처리
    function soldout_product()
    {
    	//product_id 는 무조건 대표상품 코드로 들어옴.
        global $connect, $product_id, $soldout;
		$mode = ($soldout=="0" ? 1 : 0);		
                
        $org_id = $product_id;
        
        if( $org_id > '' ) 
            $where_str = " product_id = '$org_id' or org_id='$org_id'";
        else
            $where_str = " product_id = '$product_id'";
        
        $query = "update products set enable_sale=$mode " . ($mode ? "" : ", sale_stop_date=now()") . " where " . $where_str;
debug("상품정보 옵션전체 품절버튼 : " . $query);
        mysql_query($query, $connect);

		$query = "SELECT product_id FROM products WHERE " . $where_str;
		$result = mysql_query($query, $connect);
		while($data = mysql_fetch_assoc($result))
		{
			// products history			
			class_C::insert_products_history($data[product_id], ($mode ? "판매가능" : "품절"), ($mode ? "상품정보변경 전체판매버튼" : "상품정보변경 전체품절버튼"));
		}

        echo "<script>dispOptionList(0);</script>";
    }


    // 판매처 가격 정보를 테이블에서 직접 수정
    function change_shop_price()
    {
        global $connect, $shop_id, $product_id, $price_type, $price, $new_org;
        
        $val = array();
        
        $price = preg_replace("/[^0-9]/", "", $price);

        // 기초가격 & 원가
        if( $shop_id == 0 && $price_type == 'org_price' )
        {
            $new_org = $price;
            $this->add_new_org();

            $val['error'] = 0;
            $val['new_value'] = number_format( $price );
            $val['update_time'] = date('Y-m-d h:m:s');
        }
        else
        {
            $query = "update price_history 
                         set $price_type = $price,
                             update_time = now()
                       where product_id = '$product_id' and
                             shop_id = $shop_id";
            if( mysql_query($query, $connect) )
            {
                $val['error'] = 0;
                $val['new_value'] = number_format( $price );
                $val['update_time'] = date('Y-m-d h:m:s');
                
                // 기초가격일 경우 products 수정
                if( $shop_id == 0 )
                {
                    $query = "update products
                                 set $price_type = $price,
                                     last_update_date = now()
                               where product_id = '$product_id' or 
                                     org_id = '$product_id'";
                    mysql_query($query, $connect);
                }
            }
            else
                $val['error'] = 1;
        }

        // 팝업 실행된 경우 갱신 정보
        $val['is_popup_info'] = class_table::get_product_new_data($product_id);
        
        echo json_encode( $val );
    }
    
    // 상품 옵션 정보를 테이블에서 직접 수정
    function update_options_info()
    {
        global $connect, $product_id, $field_type, $new_val;

        $val = array();
        
        // 바코드 중복 확인
        if( $field_type == 'barcode' && $new_val > '' )
        {
            $query = "select product_id from products where is_delete=0 and barcode='$new_val'";
debug( "바코드 중복확인 : " .  $query );
            $result = mysql_query($query, $connect);
            if( mysql_num_rows($result) )
            {
                $val['error'] = 1;
                echo json_encode( $val );
                exit;
            }
        }
        
        $query = "update products 
                     set $field_type = '$new_val'
                   where product_id = '$product_id'";
        mysql_query($query, $connect);
        $val['error'] = 0;
        $val['new_value'] = stripslashes($new_val);
        
        echo json_encode( $val );
    }


    
    // 원가 추가
    function add_new_org()
    {
        global $connect, $product_id, $new_org;
        
        $query = "insert org_price_history 
                     set product_id = '$product_id',
                         org_price = $new_org,
                         start_date = now(),
                         worker = '$_SESSION[LOGIN_ID]',
                         work_date = now()";
        mysql_query($query, $connect);
        
        $query = "update products set org_price=$new_org where product_id='$product_id' or org_id='$product_id'";
        mysql_query($query, $connect);

/*
        $val = array();
        $val['error'] = 0;
        
        // 팝업 실행된 경우 갱신 정보
        $val['is_popup_info'] = class_table::get_product_new_data($product_id);

        echo json_encode($val);
*/
    }
    
    // 원가 변경
    function save_org_price()
    {
        global $connect, $product_id, $seq, $new_org, $new_date;
        global $bck_connect;
        
        $val = array();
        
        // 원래정보
        $query = "select * from org_price_history where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $old_org_price = $data[org_price];
        $old_org_date = $data[start_date];
        $is_base = $data[is_base];
        
        if( $is_base )
        {
            $new_date = "0000-00-00 00:00:00";
        }
        else
        {
            // 이전 정보
            $query = "select * from org_price_history where product_id='$product_id' and seq<>$seq order by seq desc limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $pre_org_price = $data[org_price];
            $pre_org_date = $data[start_date];
        
            // 변경 날짜는 이전 원가정보 날짜의 이후만 가능
            if( $new_date <= $pre_org_date )
            {
                $val['error'] = 1;
                echo json_encode( $val );
                exit;
            }
    
            // 변경 날짜는 현재시간 이전만 가능
            if( $new_date > date('Y-m-d H:i:s') )
            {
                $val['error'] = 2;
                echo json_encode( $val );
                exit;
            }
        }

        // 상품코드 목록
        $prd_list = '';
        $query = "select product_id from products where product_id='$product_id' or org_id='$product_id'";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $prd_list .= ($prd_list ? "," : "") . "'$data[product_id]'";

        // 원가정보 변경
        $query = "update products set org_price = $new_org where product_id in ($prd_list)";
debug( "원가 변경 : " . $query );
        mysql_query($query, $connect);
        mysql_query($query, $bck_connect);
        
        $query = "update org_price_history set org_price=$new_org, start_date='$new_date', worker='$_SESSION[LOGIN_ID]', work_date=now() where seq=$seq";
        mysql_query($query, $connect);
        mysql_query($query, $bck_connect);

        // order_products 원가변경
        $query = "update order_products set org_price=$new_org * qty where product_id in ($prd_list) and match_date>='$new_date'";
        mysql_query($query, $connect);
        mysql_query($query, $bck_connect);

        // stock_tx_history 원가변경
        $query = "update stock_tx_history set org_price=$new_org where product_id in ($prd_list) and job in ('in', 'retin') and crdate>='$new_date'";
        mysql_query($query, $connect);
        mysql_query($query, $bck_connect);

        // 날짜가 뒤로 밀린 경우, 원래 시간과 밀린 시간 사이는 이전 원가를 적용한다.
        if( !$is_base && $old_org_date < $new_date )
        {
            // order_products 원가변경
            $query = "update order_products set org_price=$pre_org_price * qty where product_id in ($prd_list) and match_date>'$pre_org_date' and match_date<'$new_date'";
            mysql_query($query, $connect);
            mysql_query($query, $bck_connect);

            // stock_tx_history 원가변경
            $query = "update stock_tx_history set org_price=$pre_org_price where product_id in ($prd_list) and job in ('in', 'retin') and crdate>'$pre_org_date' and crdate<'$new_date'";
            mysql_query($query, $connect);
            mysql_query($query, $bck_connect);
        }
        
        // 팝업 실행된 경우 갱신 정보
        $val['is_popup_info'] = class_table::get_product_new_data($product_id);

        $val['error'] = 0;
        echo json_encode( $val );
    }
    
    // 원가 삭제
    function delete_org_price()
    {
        global $connect, $product_id, $seq, $new_org, $new_date;
        
        $val = array();
        
        // 원래정보
        $query = "select * from org_price_history where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        // 기초원가는 삭제 불가
        if( $data[is_base] )
        {
            $val['error'] = 1;
            echo json_encode( $val );
            exit;
        }
        
        $old_org_price = $data[org_price];
        $old_org_date = $data[start_date];

        // 이전 정보
        $query = "select * from org_price_history where product_id='$product_id' and seq<>$seq order by seq desc limit 1";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $pre_org_price = $data[org_price];
        $pre_org_date = $data[start_date];

        // 상품코드 목록
        $prd_list = '';
        $query = "select product_id from products where product_id='$product_id' or org_id='$product_id'";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $prd_list .= ($prd_list ? "," : "") . "'$data[product_id]'";

        // 원가정보 변경
        $query = "update products set org_price = $pre_org_price where product_id in ($prd_list)";
debug( "원가 삭제 : " . $query );
        mysql_query($query, $connect);
        
        $query = "delete from org_price_history where seq=$seq";
        mysql_query($query, $connect);
        
        // order_products 원가변경
        $query = "update order_products set org_price=$pre_org_price * qty where product_id in ($prd_list) and match_date>='$old_org_date'";
        mysql_query($query, $connect);

        // stock_tx_history 원가변경
        $query = "update stock_tx_history set org_price=$pre_org_price where product_id in ($prd_list) and job in ('in', 'retin') and crdate>='$old_org_date'";
        mysql_query($query, $connect);

        // 팝업 실행된 경우 갱신 정보
        $val['is_popup_info'] = class_table::get_product_new_data($product_id);

        $val['error'] = 0;
        echo json_encode( $val );
    }


    // ecn 작업 - 재고조정 오류 검사
    function jkh_test_ecn1()
    {
        global $connect;

debug("ecn 작업시작");
        $i=0;
        $j=0;
        
        $query = "select * from ecn_stock_tx_history where work='ARRANGE' order by crdate";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $i++;
            $query_chk = "select *  
                            from ecn_stock_tx_history 
                           where warehouse_seq = '$data[warehouse_seq]'
                             and product_id = '$data[product_id]'
                             and bad = '$data[bad]'
                             and work <> 'MOVE'
                             and crdate < '$data[crdate]'
                           order by crdate desc, seq desc limit 1 ";
            $result_chk = mysql_query($query_chk, $connect);
            if( mysql_num_rows($result_chk) )
            {
                $data_chk = mysql_fetch_assoc($result_chk);
                
                if( $data_chk[stock] + $data[qty] != $data[stock] )
                {
                    debug("재고조정 오류 : " . $data[seq] );

                    if( $j++ > 10 )
                        return;
                }
            }
            
            if( $i % 1000 == 0 )
                debug($i);
        }
debug("ecn 작업종료");
    }

    // ecn 작업 - stock_tx_history move 재설정
    function jkh_test_ecn2()
    {
        global $connect;

        $query = "update ecn_stock_tx_history set move = 0 ";
        mysql_query($query, $connect);
        
        $i = 0;
        $query = "select * from ecn_stock_tx_history where work='MOVE' and qty<>0 order by crdate, seq";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 자신
            $query_up = "update ecn_stock_tx_history 
                            set move = move + $data[qty]
                          where seq = $data[seq] ";
            mysql_query($query_up, $connect);

            // 날짜가 크거나 seq가 크거나
            $query_up = "update ecn_stock_tx_history 
                            set move = move + $data[qty]
                          where warehouse_seq = '$data[warehouse_seq]'
                            and product_id = '$data[product_id]' 
                            and bad = 0
                            and (crdate > '$data[crdate]' or seq > '$data[seq]') ";
            mysql_query($query_up, $connect);
            
            // move 차감 - 이동
            $query_sub = "select * 
                            from ecn_stock_tx_history 
                           where warehouse_seq = '$data[warehouse_seq]'
                             and product_id = '$data[product_id]'
                             and bad = 0
                             and work = 'MOVE_IN'
                             and move_sheet = '$data[move_sheet]' ";
            $result_sub = mysql_query($query_sub, $connect);
            if( mysql_num_rows($result_sub) )
            {
                $data_sub = mysql_fetch_assoc($result_sub);
                
                // 자신
                $query_up = "update ecn_stock_tx_history 
                                set move = move - $data[qty]
                              where seq = $data_sub[seq] ";
                mysql_query($query_up, $connect);
    
                // 날짜가 크거나 seq가 크거나
                $query_up = "update ecn_stock_tx_history 
                                set move = move - $data[qty]
                              where warehouse_seq = '$data[warehouse_seq]'
                                and product_id = '$data[product_id]' 
                                and bad = 0
                                and (crdate > '$data_sub[crdate]' or seq > '$data_sub[seq]') ";
                mysql_query($query_up, $connect);
            }
            
            // move 차감 - 매장요청
            $query_sub = "select * 
                            from ecn_stock_tx_history 
                           where warehouse_seq = '$data[warehouse_seq]'
                             and product_id = '$data[product_id]'
                             and bad = 0
                             and work = 'SHOP_REQ'
                             and req_sheet = '$data[req_sheet]' ";
            $result_sub = mysql_query($query_sub, $connect);
            if( mysql_num_rows($result_sub) )
            {
                $data_sub = mysql_fetch_assoc($result_sub);
                
                // 자신
                $query_up = "update ecn_stock_tx_history 
                                set move = move - $data[qty]
                              where seq = $data_sub[seq] ";
                mysql_query($query_up, $connect);
    
                // 날짜가 크거나 seq가 크거나
                $query_up = "update ecn_stock_tx_history 
                                set move = move - $data[qty]
                              where warehouse_seq = '$data[warehouse_seq]'
                                and product_id = '$data[product_id]' 
                                and bad = 0
                                and (crdate > '$data_sub[crdate]' or seq > '$data_sub[seq]') ";
                mysql_query($query_up, $connect);
            }
            
            if( $i++ % 100 == 0 )
                debug( $i );
        }
    }
    
    // move update
    function jkh_test_ecn3()
    {
        global $connect;
        
        $query = "select * from ecn_current_stock where bad=0";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_move = "select * 
                             from ecn_stock_tx_history 
                            where warehouse_seq = $data[warehouse_seq]
                              and product_id = '$data[product_id]' 
                              and bad = 0
                            order by crdate desc, seq desc limit 1";
            $result_move = mysql_query($query_move, $connect);
            if( mysql_num_rows($result_move) )
            {
                $data_move = mysql_fetch_assoc($result_move);
                $new_move = $data_move[move];
            }
            else
                $new_move = 0;
                
            $query_up = "update ecn_current_stock
                            set move = $new_move
                          where seq = $data[seq]";
            mysql_query($query_up, $connect);
        }
debug("완료");
    }
    
    // ecn_stock_tx_history  MOVE 작업에 stock이 0인 경우 확인
    function jkh_test_ecn4()
    {
        global $connect;

debug("시작");
        $i = 0;
        $err = 0;
        $query = "select * from ecn_stock_tx_history where warehouse_seq=5 order by crdate, seq";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_tx = "select * 
                           from ecn_stock_tx_history
                          where warehouse_seq = $data[warehouse_seq]
                            and product_id = '$data[product_id]'
                            and bad = $data[bad]
                            and (crdate< '$data[crdate]' or (crdate='$data[crdate]' and seq<$data[seq]) )
                          order by crdate desc, seq desc
                          limit 1";
            $result_tx = mysql_query($query_tx, $connect);
            if( mysql_num_rows($result_tx) )
            {
                $data_tx = mysql_fetch_assoc($result_tx);
                $old_stock = $data_tx[stock];
                
                $pos = "중간";
            }
            else
            {
                $old_stock = 0;

                $pos = "처음";
            }

            switch( $data[work] )
            {
                case "SHOP_SELL" : 
                    $new_stock = $old_stock - $data[qty];
                    break;
                case "MOVE"      : 
                    $new_stock = $old_stock;
                    break;
                case "ARRANGE"   : 
                    $new_stock = $old_stock + $data[qty];
                    break;
                case "MOVE_OUT"  : 
                    $new_stock = $old_stock - $data[qty];
                    break;
                case "SHOP_REQ"  : 
                    $new_stock = $old_stock + $data[qty];
                    break;
                case "MOVE_IN"   : 
                    $new_stock = $old_stock + $data[qty];
                    break;
                case "STOCK_IN"  : 
                    $new_stock = $old_stock + $data[qty];
                    break;
                case "HQ_RETURN" : 
                    $new_stock = $old_stock - $data[qty];
                    break;
            }                


            if( $data[stock] != $new_stock )
            {
                debug("move stock : $data[product_id]/$data[qty]/$data[work]/$data[stock]/$data[bad]");
                
                if( $err++ > 10 )  return;
            }
            
            if( $i++ % 1000 == 0 )  debug($i);
        }

debug("종료");
    }

    // ecn_stock_tx_history  MOVE 작업에 stock이 0인 경우 수정
    function jkh_test_ecn5()
    {
        global $connect;

debug("시작");
        $i = 0;
        $err = 0;
        $query = "select * from ecn_stock_tx_history where work='MOVE' order by crdate, seq";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_tx = "select * 
                           from ecn_stock_tx_history
                          where warehouse_seq = $data[warehouse_seq]
                            and product_id = '$data[product_id]'
                            and bad = $data[bad]
                            and work<>'MOVE'
                            and (crdate< '$data[crdate]' or (crdate='$data[crdate]' and seq<$data[seq]) )
                          order by crdate desc, seq desc
                          limit 1";
            $result_tx = mysql_query($query_tx, $connect);
            if( mysql_num_rows($result_tx) )
            {
                $data_tx = mysql_fetch_assoc($result_tx);
                
                $new_stock = $data_tx[stock];
            }
            else
                $new_stock = 0;
                
            $query_up = "update ecn_stock_tx_history set stock = $new_stock where seq=$data[seq]";
            mysql_query($query_up, $connect);
            
            if( $i++ % 1000 == 0 )  debug($i);
        }
debug("종료");
    }
    
    // ecn_stock_tx 새로 생성
    function jkh_test_ecn6()
    {
        global $connect;
        
        $i=0;
        
        $query = "delete from ecn_stock_tx";
        mysql_query($query, $connect);
        
debug("시작");

        // stock, move
        $query = "select * 
                    from ecn_stock_tx_history 
                   where tx_date>0
                   group by tx_date, warehouse_seq, product_id, bad
                   order by tx_date, warehouse_seq, product_id, bad ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $tx_date = $data[tx_date];
            $warehouse_seq = $data[warehouse_seq];
            $product_id = $data[product_id];
            $bad = $data[bad];

            $query_tx = "select stock, move 
                           from ecn_stock_tx_history 
                          where tx_date = '$tx_date'
                            and warehouse_seq = $warehouse_seq
                            and product_id = '$product_id'
                            and bad = $bad
                          order by crdate desc, seq desc
                          limit 1 ";
            $result_tx = mysql_query($query_tx, $connect);
            $data_tx = mysql_fetch_assoc($result_tx);
            
            $new_stock = $data_tx[stock];
            $new_move = $data_tx[move];
            
            $work_qty = array(
                "SHOP_SELL"     => 0
               ,"ARRANGE"       => 0
               ,"MOVE_IN"       => 0
               ,"SHOP_REQ"      => 0
               ,"MOVE_OUT"      => 0
               ,"STOCK_IN"      => 0
               ,"HQ_RETURN"     => 0
            );
            
            $query_tx = "select work
                               ,sum(qty) sum_qty
                           from ecn_stock_tx_history 
                          where tx_date = '$tx_date'
                            and warehouse_seq = $warehouse_seq
                            and product_id = '$product_id'
                            and bad = $bad
                          group by work ";
            $result_tx = mysql_query($query_tx, $connect);
            while( $data_tx = mysql_fetch_assoc($result_tx) )
            {
                $work_qty[$data_tx['work']] = $data_tx['sum_qty'];
            }
            
            $query_ins = "insert ecn_stock_tx
                             set crdate        = '$tx_date'
                                ,warehouse_seq = '$warehouse_seq'
                                ,product_id    = '$product_id'
                                ,bad           = '$bad'
                                ,stock         = '$new_stock'
                                ,move          = '$new_move'
                                ,SHOP_SELL     = '".$work_qty['SHOP_SELL']."'
                                ,STOCK_IN      = '".$work_qty['STOCK_IN']."'
                                ,ARRANGE       = '".$work_qty['ARRANGE']."'
                                ,MOVE_OUT      = '".$work_qty['MOVE_OUT']."'
                                ,SHOP_REQ      = '".$work_qty['SHOP_REQ']."'
                                ,MOVE_IN       = '".$work_qty['MOVE_IN']."'
                                ,HQ_RETURN     = '".$work_qty['HQ_RETURN']."' ";
            mysql_query($query_ins, $connect);
            
            
            if( $i++ % 100 == 0 )  debug($i);
        }
debug("종료");
    }
    
    function jkh_test_ecn7()
    {
        global $connect;
        
        $i = 0;
debug("시작");
        $query = "select * from ecn_stock_tx group by warehouse_seq, product_id, bad";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $warehouse_seq = $data[warehouse_seq];
            $product_id = $data[product_id];
            $bad = $data[bad];
            $tx_date = $data[tx_date];

            $query_tx = "select * 
                           from ecn_stock_tx 
                          where warehouse_seq = '$warehouse_seq'
                            and product_id = '$product_id'
                            and bad = '$bad'
                          order by crdate limit 1";
            $result_tx = mysql_query($query_tx, $connect);
            $data_tx = mysql_fetch_assoc($result_tx);

            $the_date = $data_tx[crdate];
            
            // 첫번째 날짜로부터 날짜 배열 생성
            $date_arr = array();
            while($the_date < '2013-05-06')
            {
                $the_date = date('Y-m-d', strtotime('+1 days', strtotime($the_date)));
                $date_arr[] = $the_date;
            }

            $the_stock = $data_tx[stock];
            $the_move = $data_tx[move];
            foreach( $date_arr as $d_val )
            {
                $query_date = "select * 
                                 from ecn_stock_tx 
                                where warehouse_seq = '$warehouse_seq'
                                  and product_id = '$product_id'
                                  and bad = '$bad'
                                  and crdate = '$d_val' ";
                $result_date = mysql_query($query_date, $connect);
                if( mysql_num_rows($result_date) )
                {
                    $data_date = mysql_fetch_assoc($result_date);
                    
                    $the_stock = $data_date[stock];
                    $the_move = $data_date[move];
                }
                else
                {
                    if( $the_stock != 0 || $the_move != 0 )
                    {
                        $query_ins = "insert ecn_stock_tx
                                         set crdate = '$d_val'
                                            ,warehouse_seq = $warehouse_seq
                                            ,product_id = '$product_id'
                                            ,bad = '$bad'
                                            ,stock = $the_stock
                                            ,move = $the_move ";
                        mysql_query($query_ins, $connect);
                    }
                }
            }
            
            if( $i++ % 100 == 0 )  debug($i);
        }
debug("완료");            
    }
        
    function jkh_test_ecn8()
    {
        global $connect;
        
        $query = "select a.to_seq       w_seq
                        ,b.product_id   pid
                        ,sum(b.req_qty) sum_qty
                    from ecn_move_sheet_title a
                        ,ecn_move_sheet_item b 
                   where a.seq=b.title_seq 
                     and a.status<5
                     and a.stock_sub=1
                   group by a.to_seq, b.product_id";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_qty = "select count(*) cnt from ecn_jkh where warehouse_seq=$data[w_seq] and product_id='$data[pid]' ";
            $result_qty = mysql_query($query_qty, $connect);
            $data_qty = mysql_fetch_assoc($result_qty);
            
            if( $data_qty[cnt] > 0 )
                $query_up = "update ecn_jkh set return_qty = $data[sum_qty] where product_id='$data[pid]' ";
            else
                $query_up = "insert ecn_jkh set product_id = '$data[pid]', $return_qty=$data[sum_qty]";
            mysql_query($query_up, $connect);
        }

        $query = "select a.in_warehouse_id       w_seq
                        ,b.product_id   pid
                        ,sum(b.real_qty) sum_qty
                    from ecn_warehouse_out_req_sheet a
                        ,ecn_warehouse_out_req_item b 
                   where a.seq=b.deliv_sheet_seq 
                     and a.status=5
                     and b.real_qty>0
                   group by a.in_warehouse_id, b.product_id";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_qty = "select count(*) cnt from ecn_jkh where warehouse_seq=$data[w_seq] and product_id='$data[pid]' ";
            $result_qty = mysql_query($query_qty, $connect);
            $data_qty = mysql_fetch_assoc($result_qty);
            
            if( $data_qty[cnt] > 0 )
                $query_up = "update ecn_jkh set return_qty = $data[sum_qty] where product_id='$data[pid]' ";
            else
                $query_up = "insert ecn_jkh set product_id = '$data[pid]', $return_qty=$data[sum_qty]";
            mysql_query($query_up, $connect);
        }
debug("완료");            
    }
    
    //#######################################################
    // ecn_current_stock, current_stock move 초기화
    //#######################################################
    function jkh_test_ecn9()
    {
        global $connect;
debug("ecn_current_stock, current_stock move 초기화 0 ");
        
        // move 초기화
        $query = "update ecn_current_stock set move = 0";
        mysql_query( $query, $connect);
        
        // rt 전표
        $query = "select a.to_seq     a_to_seq
                        ,b.product_id b_product_id
                        ,b.req_qty    b_req_qty
                    from ecn_move_sheet_title a
                        ,ecn_move_sheet_item b
                   where a.seq = b.title_seq
                     and a.status <= 4
                     and a.stock_sub = 1
                     and b.req_qty>0 ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_move = "select count(*) cnt
                             from ecn_current_stock
                            where product_id = '$data[b_product_id]'
                              and warehouse_seq = $data[a_to_seq]
                              and bad = 0";
            $result_move = mysql_query($query_move, $connect);
            $data_move = mysql_fetch_assoc($result_move);
            
            if( $data_move[cnt] > 0 )
            {
                $query_move = "update ecn_current_stock 
                                  set move = move + $data[b_req_qty]
                                where product_id = '$data[b_product_id]'
                                  and warehouse_seq = $data[a_to_seq]
                                  and bad = 0 ";
            }
            else
            {
                $query_move = "insert ecn_current_stock 
                                  set product_id = '$data[b_product_id]'
                                     ,qty = 0
                                     ,warehouse_seq = $data[a_to_seq]
                                     ,move = $data[b_req_qty]
                                     ,bad = 0 ";
            }
            mysql_query($query_move, $connect);
        }
debug("ecn_current_stock, current_stock move 초기화 1 ");

        // 입고요청 전표
        $query = "select a.in_warehouse_id a_to_seq
                        ,b.product_id      b_product_id
                        ,b.real_qty        b_req_qty
                    from ecn_warehouse_out_req_sheet a
                        ,ecn_warehouse_out_req_item b
                   where a.seq = b.deliv_sheet_seq
                     and a.status = 5
                     and b.real_qty>0 ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_move = "select count(*) cnt
                             from ecn_current_stock
                            where product_id = '$data[b_product_id]'
                              and warehouse_seq = $data[a_to_seq]
                              and bad = 0";
            $result_move = mysql_query($query_move, $connect);
            $data_move = mysql_fetch_assoc($result_move);
            
            if( $data_move[cnt] > 0 )
            {
                $query_move = "update ecn_current_stock 
                                  set move = move + $data[b_req_qty]
                                where product_id = '$data[b_product_id]'
                                  and warehouse_seq = $data[a_to_seq]
                                  and bad = 0 ";
            }
            else
            {
                $query_move = "insert ecn_current_stock 
                                  set product_id = '$data[b_product_id]'
                                     ,qty = 0
                                     ,warehouse_seq = $data[a_to_seq]
                                     ,move = $data[b_req_qty]
                                     ,bad = 0 ";
            }
            mysql_query($query_move, $connect);
        }

debug("ecn_current_stock, current_stock move 초기화 1 ");
        // move 초기화
        $query = "update current_stock set move = 0";
        mysql_query( $query, $connect);

        // 반품 전표
        $query = "select a.to_seq     a_to_seq
                        ,b.product_id b_product_id
                        ,b.req_qty    b_req_qty
                    from ecn_return_sheet_title a
                        ,ecn_return_sheet_item b
                   where a.seq = b.title_seq
                     and a.status <= 1
                     and a.stock_sub = 1
                     and b.req_qty>0 ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data[a_to_seq] == 2 )
            {
                $query_move = "select count(*) cnt
                                 from current_stock
                                where product_id = '$data[b_product_id]'
                                  and bad = 0";
                $result_move = mysql_query($query_move, $connect);
                $data_move = mysql_fetch_assoc($result_move);
                
                if( $data_move[cnt] > 0 )
                {
                    $query_move = "update current_stock 
                                      set move = move + $data[b_req_qty]
                                    where product_id = '$data[b_product_id]'
                                      and bad = 0 ";
                }
                else
                {
                    $query_move = "insert current_stock 
                                      set product_id = '$data[b_product_id]'
                                         ,stock = 0
                                         ,move = $data[b_req_qty]
                                         ,bad = 0 ";
                }
                mysql_query($query_move, $connect);
            }
            else
            {
                $query_move = "select count(*) cnt
                                 from ecn_current_stock
                                where product_id = '$data[b_product_id]'
                                  and warehouse_seq = $data[a_to_seq]
                                  and bad = 0";
                $result_move = mysql_query($query_move, $connect);
                $data_move = mysql_fetch_assoc($result_move);
                
                if( $data_move[cnt] > 0 )
                {
                    $query_move = "update ecn_current_stock 
                                      set move = move + $data[b_req_qty]
                                    where product_id = '$data[b_product_id]'
                                      and warehouse_seq = $data[a_to_seq]
                                      and bad = 0 ";
                }
                else
                {
                    $query_move = "insert ecn_current_stock 
                                      set product_id = '$data[b_product_id]'
                                         ,qty = 0
                                         ,warehouse_seq = $data[a_to_seq]
                                         ,move = $data[b_req_qty]
                                         ,bad = 0 ";
                }
                mysql_query($query_move, $connect);
            }
        }

debug("완료");            
    }

    // 테스트
    function jkh_test()
    {
        global $connect;

        // $this->jkh_test_cti();
        
        $this->jkh_test_cafe24_solup();
    }
    
    // 다바걸 매칭정보 솔업
    //
    // 기존 매칭정보에서 해당 판매처 매칭정보 백업 : code_match_solup
    // solup 코드 변경 테이블 : solup_code
    function jkh_test_cafe24_solup()
    {
        global $connect;
        
        // 수정 정보 없는 매칭 정보 삭제
        $query = "delete a from code_match_solup a left outer join solup_code b on a.shop_code=b.old_code where b.old_code is null";
        mysql_query($query, $connect);
        
        // 수정 정보 없는 매칭 정보 삭제
        $query = "update code_match_solup a, solup_code b set a.shop_code=b.new_code where a.shop_code=b.old_code";
        mysql_query($query, $connect);

        // seq 수정
        $query = "update code_match_solup set seq=seq+200000";
        mysql_query($query, $connect);
    }

    // 멀티카테고리 수정
    function change_multi_category()
    {
        global $connect;
        
        $search_id = array(
             "11" => "0011",
             "37" => "0037",
             "53" => "0053",
             "56" => "0056",
             "57" => "0057",
             "58" => "0058",
             "59" => "0059",
             "62" => "0062",
             "63" => "0063",
             "64" => "0064",
             "65" => "0065",
             "66" => "0066",
             "67" => "0067",
             "68" => "0068",
             "69" => "0069",
             "70" => "0070",
             "71" => "0071",
             "97" => "0067",
            "103" => "0078",
            "104" => "0079",
            "105" => "0080",
            "106" => "0081",
            "107" => "0082",
            "108" => "0083",
            "109" => "0084",
            "110" => "0085",
            "121" => "0095",
            "122" => "0096",
            "126" => "0100",
            "127" => "0101",
            "128" => "0102",
            "129" => "0103",
            "130" => "0065",
            "131" => "0104",
            "132" => "0105",
            "133" => "0106",
            "134" => "0107",
            "135" => "0108",
            "136" => "0109",
            "137" => "0110",
            "138" => "0111",
            "139" => "0112",
            "140" => "0113",
            "141" => "0114",
            "142" => "0115",
            "143" => "0116",
            "144" => "0117",
            "145" => "0118",
            "146" => "0119"
        );        
        
debug("멀티카테고리 시작 : ");
        $query = "select product_id, str_category from products where m_category1='0000'";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $str_arr = explode(">", $data[str_category]);
            
            $new_cat1 = $search_id[trim($str_arr[0])]; 
            $new_cat2 = $search_id[trim($str_arr[1])]; 
            $new_cat3 = $search_id[trim($str_arr[2])]; 

            $query = "update products set m_category1 = '$new_cat1', m_category2='$new_cat2', m_category3='$new_cat3' where product_id = '$data[product_id]' ";
            mysql_query($query, $connect);
        }
debug("멀티카테고리 완료 : $query");
    }

    // 주소변경
    function change_bad_address($bad_address, $good_address)
    {
        global $connect;
        
        $query = "update orders set recv_address='$good_address' where recv_address='$bad_address'";
        mysql_query($query, $connect);
    }

    
    // 옵션 체크박스로 품절처리
    function each_soldout()
    {
        global $connect, $product_id, $mode;
        
        $query = "update products set enable_sale=$mode " . ($mode ? "" : ", sale_stop_date=now()") . " where product_id='$product_id'";
debug("부분품절 체크박스 : " . $query);
        mysql_query($query, $connect);
        
        // 대표상품 부분품절체크
        $query = "select org_id from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $org_id = $data[org_id];
        
        $_en = false;
        $_dis = false;
        $query = "select enable_sale from products where org_id='$org_id'";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data[enable_sale] )  
                $_en = true;
            else
                $_dis = true;
        }
        
        // 부분품절
        if( $_en && $_dis )
            $_e = 2;
        // 판매가능
        else if( $_en && !$_dis )
            $_e = 1;
        // 품절
        else
            $_e = 0;
            
        class_C::insert_products_history($product_id, ($mode ? "판매가능" : "품절"), "상품정보변경 옵션목록에서 품절체크박스");
        
        $query = "update products set enable_sale=$_e where product_id='$org_id'";
        mysql_query($query, $connect);
    }

    // 상품 체크박스로 품절처리
    function all_soldout()
    {
        global $connect, $product_id, $mode;

        if( $mode )
        {
            $query = "update products 
                         set enable_sale = 1
                       where product_id = '$product_id' or org_id = '$product_id'";
        }
        else
        {
            $query = "update products 
                         set enable_sale = 0, 
                             sale_stop_date = now(),
                             soldout_worker = '$_SESSION[LOGIN_NAME]-상품목록체크박스'
                       where (product_id = '$product_id' or org_id = '$product_id') and
                             enable_sale in (1,2)";
        }
        mysql_query($query, $connect);

        // products history
        class_C::insert_products_history($product_id, ($mode ? "판매가능" : "품절"), "전체상품목록에서 품절체크박스");

        $query = "select * from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

        $product_icon = "&nbsp;" . 
                        ( $data[stock_manage] ? " <img src=images/option.gif> " : " <img src=images/option_blank.gif> " ) .
                        ( $data[enable_stock] ? " <img src=images/stock.gif> " : " <img src=images/stock_blank.gif> " );

        if( $data[enable_sale] == 0 ) 
            $product_icon .= " <img src=images/soldout.gif> ";
        else if( $data[enable_sale] == 2 ) 
            $product_icon .= " <img src=images/part_soldout.gif> ";
            
        $val = array();        
        $val['product_icon'] = $product_icon;
        echo json_encode( $val );
    }
    
    // CTI 주문 생성 - make_cti_orders
    function jkh_test_cti()
    {
        global $connect;
        
        // 단품
        $query = "select seq, 
                         collect_date,
                         collect_time,
                         recv_name, 
                         recv_tel, 
                         recv_mobile, 
                         order_name, 
                         order_tel, 
                         order_mobile, 
                         qty,
                         amount,
                         shop_id
                    from orders 
                   where pack=0 and collect_date>='2014-09-24'
                   order by seq desc";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $shop_key = "SHOP_NAME_" . $data[shop_id];
            $shop_name = $_SESSION[$shop_key];

            // recv_tel
            $tel = preg_replace('/[^0-9]/', '', $data[recv_tel]);
            if( $tel == "" )  continue;
            $name = str_replace("'", "\\'", $data[recv_name]);
            $query_tel = "select * from cti_orders where tel='$tel'";
            $result_tel = mysql_query($query_tel, $connect);
            if( !mysql_num_rows($result_tel) )
            {
                $query_in = "insert cti_orders
                                set order_seq = $data[seq],
                                    tel       = '$tel',
                                    name      = '$name',
                                    shop_name = '$shop_name',
                                    qty       = '$data[qty]',
                                    amount    = '$data[amount]',
                                    crdate    = '$data[collect_date] $data[collect_time]'";
                mysql_query($query_in, $connect);
            }
            
            if( $data[recv_tel] == $data[recv_mobile] )  continue;
            
            // recv_mobile
            $tel = preg_replace('/[^0-9]/', '', $data[recv_mobile]);
            if( $tel == "" )  continue;
            $name = str_replace("'", "\\'", $data[recv_name]);
            $query_tel = "select * from cti_orders where tel='$tel'";
            $result_tel = mysql_query($query_tel, $connect);
            if( !mysql_num_rows($result_tel) )
            {
                $query_in = "insert cti_orders
                                set order_seq = $data[seq],
                                    tel       = '$tel',
                                    name      = '$name',
                                    shop_name = '$shop_name',
                                    qty       = '$data[qty]',
                                    amount    = '$data[amount]',
                                    crdate    = '$data[collect_date] $data[collect_time]'";
                mysql_query($query_in, $connect);
            }
            
            if( $data[recv_tel] == $data[order_tel] || $data[recv_mobile] == $data[order_tel] )  continue;

            // order_tel
            $tel = preg_replace('/[^0-9]/', '', $data[order_tel]);
            if( $tel == "" )  continue;
            $name = str_replace("'", "\\'", $data[order_name]);
            $query_tel = "select * from cti_orders where tel='$tel'";
            $result_tel = mysql_query($query_tel, $connect);
            if( !mysql_num_rows($result_tel) )
            {
                $query_in = "insert cti_orders
                                set order_seq = $data[seq],
                                    tel       = '$tel',
                                    name      = '$name',
                                    shop_name = '$shop_name',
                                    qty       = '$data[qty]',
                                    amount    = '$data[amount]',
                                    crdate    = '$data[collect_date] $data[collect_time]'";
                mysql_query($query_in, $connect);
            }
            
            if( $data[recv_tel] == $data[order_mobile] || $data[recv_mobile] == $data[order_mobile] || $data[order_tel] == $data[order_mobile] )  continue;

            // order_mobile
            $tel = preg_replace('/[^0-9]/', '', $data[order_mobile]);
            if( $tel == "" )  continue;
            $name = str_replace("'", "\\'", $data[order_name]);
            $query_tel = "select * from cti_orders where tel='$tel'";
            $result_tel = mysql_query($query_tel, $connect);
            if( !mysql_num_rows($result_tel) )
            {
                $query_in = "insert cti_orders
                                set order_seq = $data[seq],
                                    tel       = '$tel',
                                    name      = '$name',
                                    shop_name = '$shop_name',
                                    qty       = '$data[qty]',
                                    amount    = '$data[amount]',
                                    crdate    = '$data[collect_date] $data[collect_time]'";
                mysql_query($query_in, $connect);
            }
        }
        
        debug( "단품 완료");

        // 합포
        $query = "select seq, 
                         collect_date,
                         collect_time,
                         recv_name, 
                         recv_tel, 
                         recv_mobile, 
                         order_name, 
                         order_tel, 
                         order_mobile, 
                         sum(qty) sum_qty,
                         sum(amount) sum_amount,
                         shop_id
                    from orders 
                   where pack>0 and collect_date>='2014-09-24'
                   group by pack
                   order by seq desc";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $shop_key = "SHOP_NAME_" . $data[shop_id];
            $shop_name = $_SESSION[$shop_key];

            // recv_tel
            $tel = preg_replace('/[^0-9]/', '', $data[recv_tel]);
            if( $tel == "" )  continue;
            $name = str_replace("'", "\\'", $data[recv_name]);
            $query_tel = "select * from cti_orders where tel='$tel'";
            $result_tel = mysql_query($query_tel, $connect);
            if( !mysql_num_rows($result_tel) )
            {
                $query_in = "insert cti_orders
                                set order_seq = $data[seq],
                                    tel       = '$tel',
                                    name      = '$name',
                                    shop_name = '$shop_name',
                                    qty       = '$data[sum_qty]',
                                    amount    = '$data[sum_amount]',
                                    crdate    = '$data[collect_date] $data[collect_time]'";
                mysql_query($query_in, $connect);
            }
            
            if( $data[recv_tel] == $data[recv_mobile] )  continue;
            
            // recv_mobile
            $tel = preg_replace('/[^0-9]/', '', $data[recv_mobile]);
            if( $tel == "" )  continue;
            $name = str_replace("'", "\\'", $data[recv_name]);
            $query_tel = "select * from cti_orders where tel='$tel'";
            $result_tel = mysql_query($query_tel, $connect);
            if( !mysql_num_rows($result_tel) )
            {
                $query_in = "insert cti_orders
                                set order_seq = $data[seq],
                                    tel       = '$tel',
                                    name      = '$name',
                                    shop_name = '$shop_name',
                                    qty       = '$data[sum_qty]',
                                    amount    = '$data[sum_amount]',
                                    crdate    = '$data[collect_date] $data[collect_time]'";
                mysql_query($query_in, $connect);
            }
            
            if( $data[recv_tel] == $data[order_tel] || $data[recv_mobile] == $data[order_tel] )  continue;

            // order_tel
            $tel = preg_replace('/[^0-9]/', '', $data[order_tel]);
            if( $tel == "" )  continue;
            $name = str_replace("'", "\\'", $data[order_name]);
            $query_tel = "select * from cti_orders where tel='$tel'";
            $result_tel = mysql_query($query_tel, $connect);
            if( !mysql_num_rows($result_tel) )
            {
                $query_in = "insert cti_orders
                                set order_seq = $data[seq],
                                    tel       = '$tel',
                                    name      = '$name',
                                    shop_name = '$shop_name',
                                    qty       = '$data[sum_qty]',
                                    amount    = '$data[sum_amount]',
                                    crdate    = '$data[collect_date] $data[collect_time]'";
                mysql_query($query_in, $connect);
            }
            
            if( $data[recv_tel] == $data[order_mobile] || $data[recv_mobile] == $data[order_mobile] || $data[order_tel] == $data[order_mobile] )  continue;

            // order_mobile
            $tel = preg_replace('/[^0-9]/', '', $data[order_mobile]);
            if( $tel == "" )  continue;
            $name = str_replace("'", "\\'", $data[order_name]);
            $query_tel = "select * from cti_orders where tel='$tel'";
            $result_tel = mysql_query($query_tel, $connect);
            if( !mysql_num_rows($result_tel) )
            {
                $query_in = "insert cti_orders
                                set order_seq = $data[seq],
                                    tel       = '$tel',
                                    name      = '$name',
                                    shop_name = '$shop_name',
                                    qty       = '$data[sum_qty]',
                                    amount    = '$data[sum_amount]',
                                    crdate    = '$data[collect_date] $data[collect_time]'";
                mysql_query($query_in, $connect);
            }
        }

        debug( "합포 완료");
    }
    
    // popup 화면에서 상품정보 수정시, opener의 정보를 자동 수정
    function update_opener_info($product_id)
    {
        echo "<script type='text/javascript'>";
        echo "opener.update_product_info('".class_table::get_product_new_data($product_id)."');";
        echo "</script>";
    }
    
    // 부분품절 아이콘 마우스오버시 상품목록 표시
    function cs_tooltip()
    {
        global $connect, $product_id;
        
        $str = "<table width=300>";
        
        $str .= "<tr>";
        $str .= "  <td>상품코드</td>";
        $str .= "  <td>&nbsp;</td>";
        $str .= "  <td>옵션</td>";
        $str .= "</tr>";

        $query = "select * from products where org_id='$product_id' and is_delete=0 order by product_id";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $str .= "<tr>";
            $str .= "  <td>$data[product_id]</td>";
            $str .= "  <td>" . ($data[enable_sale] ? "&nbsp;" : "품절") . "</td>";
            $str .= "  <td>$data[options]</td>";
            $str .= "</tr>";
        }

        $str .= "<table>";
        
        echo $str;
    }

    function insert_stock_tx($crdate)
    {
        global $connect;
        $query = "select * from ecn_stock_tx_history where date(crdate)='$crdate' order by seq ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $SHOP_SELL   = 0;
            $STOCK_IN    = 0;
            $ARRANGE     = 0;
            $MOVE_OUT    = 0;
            $SHOP_REQ    = 0;
            $MOVE_IN     = 0;
            $HQ_RETURN   = 0;
            $CUST_RETURN = 0;
            $TO_NORMAL   = 0;
            $TO_BAD      = 0;

            switch( $data[work] )
            {
                case "SHOP_SELL":
                    $SHOP_SELL = $data[qty];
                    break;
                case "STOCK_IN":
                    $STOCK_IN = $data[qty];
                    break;
                case "ARRANGE":
                    $ARRANGE = $data[qty];
                    break;
                case "MOVE_OUT":
                    $MOVE_OUT = $data[qty];
                    break;
                case "SHOP_REQ":
                    $SHOP_REQ = $data[qty];
                    break;
                case "MOVE_IN":
                    $MOVE_IN = $data[qty];
                    break;
                case "HQ_RETURN":
                    $HQ_RETURN = $data[qty];
                    break;
                case "CUST_RETURN":
                    $CUST_RETURN = $data[qty];
                    break;
                case "TO_NORMAL":
                    $TO_NORMAL = $data[qty];
                    break;
                case "TO_BAD":
                    $TO_BAD = $data[qty];
                    break;
            }

            $query_tx = "insert ecn_stock_tx
                            set crdate        = '$crdate'
                               ,warehouse_seq = '$data[warehouse_seq]'
                               ,product_id    = '$data[product_id]'
                               ,bad           = '$data[bad]'
                               ,stock         = '$data[stock]'
                               ,move          = '$data[move]'
                               ,SHOP_SELL     = $SHOP_SELL  
                               ,STOCK_IN      = $STOCK_IN   
                               ,ARRANGE       = $ARRANGE    
                               ,MOVE_OUT      = $MOVE_OUT   
                               ,SHOP_REQ      = $SHOP_REQ   
                               ,MOVE_IN       = $MOVE_IN    
                               ,HQ_RETURN     = $HQ_RETURN  
                               ,CUST_RETURN   = $CUST_RETURN
                               ,TO_NORMAL     = $TO_NORMAL  
                               ,TO_BAD        = $TO_BAD     
                             ON DUPLICATE KEY UPDATE
                                stock         = '$data[stock]'
                               ,move          = '$data[move]'
                               ,SHOP_SELL     = SHOP_SELL   + $SHOP_SELL  
                               ,STOCK_IN      = STOCK_IN    + $STOCK_IN   
                               ,ARRANGE       = ARRANGE     + $ARRANGE    
                               ,MOVE_OUT      = MOVE_OUT    + $MOVE_OUT   
                               ,SHOP_REQ      = SHOP_REQ    + $SHOP_REQ   
                               ,MOVE_IN       = MOVE_IN     + $MOVE_IN    
                               ,HQ_RETURN     = HQ_RETURN   + $HQ_RETURN  
                               ,CUST_RETURN   = CUST_RETURN + $CUST_RETURN
                               ,TO_NORMAL     = TO_NORMAL   + $TO_NORMAL  
                               ,TO_BAD        = TO_BAD      + $TO_BAD ";
            mysql_query($query_tx, $connect);
        }
        
        // tx_date
        $query = "update ecn_stock_tx_history set tx_date='$crdate' where date(crdate)='$crdate' ";
        mysql_query($query, $connect);
        
        // 재고, 이동은 있으나 다른 작업이 없는 경우
        $query = "select a.product_id    a_product_id
                        ,a.warehouse_seq a_warehouse_seq
                        ,a.bad           a_bad
                        ,a.qty           a_qty
                        ,a.move          a_move
                    from ecn_current_stock a
                         left outer join ecn_stock_tx b 
                                      on b.crdate='$crdate' 
                                     and a.product_id = b.product_id
                                     and a.warehouse_seq = b.warehouse_seq
                                     and a.bad = b.bad
                   where ( a.qty <> 0 or a.move <> 0 )
                     and b.seq is null ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_tx = "insert ecn_stock_tx
                            set crdate        = '$crdate'
                               ,warehouse_seq = '$data[a_warehouse_seq]'
                               ,product_id    = '$data[a_product_id]'
                               ,bad           = '$data[a_bad]'
                               ,stock         = '$data[a_qty]'
                               ,move          = '$data[a_move]' ";
            mysql_query($query_tx, $connect);
        }
    }
    
    function sync_ilovejchina()
    {
        global $sys_connect, $connect;
        global $template, $pick_soldout_date, $start_date, $end_date, $packed, $date_type,$e_stock, $stock_manage, $link_url_list, $sort, $str_category,$current_category1,$current_category2,$current_category3, $price_shop_id, $category;
        global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $m_sub_category_4, $click_index, $page;
        global $str_supply_code, $s_group_id, $str_category,$supply_code;
        
        // ilovejchina connect
        $query = "select * from sys_domain where id='ilovejchina'";
        $result = mysql_query($query, $sys_connect);
        $data = mysql_fetch_assoc($result);
        
        $china_connect = class_db::connect( $data[host], $data[db_name], $data[db_pwd] );
        
        $page = 1;
        $result = class_C::get_list( &$cnt_all, $page, 1 );
        while( $data = mysql_fetch_assoc($result) )
        {
            $prd_arr = array();
            
            $query_all = "select * from products where product_id='$data[product_id]' or org_id='$data[product_id]' ";
            $result_all = mysql_query($query_all, $connect);
            while( $data_all = mysql_fetch_assoc($result_all) )
            {
                $query = "insert products set ";
                foreach( $data_all as $k => $v )
                    $query .= "$k = '" . addslashes($v) . "',";
debug("중국 상품 동기화 : " . $query);
                $query = substr($query, 0,-1);
                mysql_query($query, $china_connect);
                
                $prd_arr[] = $data_all[product_id];
            }
            
            $prd_str = implode(",", $prd_arr);
            
            $query_all = "select * from price_history where product_id in ($prd_str) ";
            $result_all = mysql_query($query_all, $connect);
            while( $data_all = mysql_fetch_assoc($result_all) )
            {
                $query = "insert price_history set ";
                foreach( $data_all as $k => $v )
                    $query .= "$k = '" . addslashes($v) . "',";

                $query = substr($query, 0,-1);
                mysql_query($query, $china_connect);
            }

            $query_all = "select * from org_price_history where product_id in ($prd_str) ";
            $result_all = mysql_query($query_all, $connect);
            while( $data_all = mysql_fetch_assoc($result_all) )
            {
                $query = "insert org_price_history set ";
                foreach( $data_all as $k => $v )
                    $query .= "$k = '" . addslashes($v) . "',";

                $query = substr($query, 0,-1);
                mysql_query($query, $china_connect);
            }
        }

        // 옵션만 동기화된 경우 상품명 수정
        $query_name = "update products a, products b set b.name=a.name where a.product_id=b.org_id and a.name<>b.name";
        mysql_query($query_name, $china_connect);
        
        $result = class_C::get_list( &$cnt_all, $page);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function sync_ilovejjapan()
    {
        global $sys_connect, $connect;
        global $template, $pick_soldout_date, $start_date, $end_date, $packed, $date_type,$e_stock, $stock_manage, $link_url_list, $sort, $str_category,$current_category1,$current_category2,$current_category3, $price_shop_id, $category;
        global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $m_sub_category_4, $click_index, $page;
        global $str_supply_code, $s_group_id, $str_category,$supply_code;
        
        // ilovejchina connect
        $query = "select * from sys_domain where id='ilovejjapan'";
        $result = mysql_query($query, $sys_connect);
        $data = mysql_fetch_assoc($result);
        
        $china_connect = class_db::connect( $data[host], $data[db_name], $data[db_pwd] );
        
        $page = 1;
        $result = class_C::get_list( &$cnt_all, $page, 1 );
        while( $data = mysql_fetch_assoc($result) )
        {
            $prd_arr = array();
            
            $query_all = "select * from products where product_id='$data[product_id]' or org_id='$data[product_id]' ";
            $result_all = mysql_query($query_all, $connect);
            while( $data_all = mysql_fetch_assoc($result_all) )
            {
                $query = "insert products set ";
                foreach( $data_all as $k => $v )
                    $query .= "$k = '" . addslashes($v) . "',";

                $query = substr($query, 0,-1);
                mysql_query($query, $china_connect);
                
                $prd_arr[] = $data_all[product_id];
            }
            
            $prd_str = implode(",", $prd_arr);
            
            $query_all = "select * from price_history where product_id in ($prd_str) ";
            $result_all = mysql_query($query_all, $connect);
            while( $data_all = mysql_fetch_assoc($result_all) )
            {
                $query = "insert price_history set ";
                foreach( $data_all as $k => $v )
                    $query .= "$k = '" . addslashes($v) . "',";

                $query = substr($query, 0,-1);
                mysql_query($query, $china_connect);
            }

            $query_all = "select * from org_price_history where product_id in ($prd_str) ";
            $result_all = mysql_query($query_all, $connect);
            while( $data_all = mysql_fetch_assoc($result_all) )
            {
                $query = "insert org_price_history set ";
                foreach( $data_all as $k => $v )
                    $query .= "$k = '" . addslashes($v) . "',";

                $query = substr($query, 0,-1);
                mysql_query($query, $china_connect);
            }
        }

        // 옵션만 동기화된 경우 상품명 수정
        $query_name = "update products a, products b set b.name=a.name where a.product_id=b.org_id and a.name<>b.name";
        mysql_query($query_name, $china_connect);
        
        $result = class_C::get_list( &$cnt_all, $page);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

	//-----------------------------------------------
	//-- add by syhwang
	//-- for grid_C260
    function C260()
    {
        global $connect, $template;
                
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

	function grid_C260()
	{
		global $connect;
		
		$product_id = $_REQUEST['product_id'];

		$sql = "select org_id, options, name from products where product_id = '$product_id'";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));
		$product_name = $list[name];

		if ($list[org_id]) {
			$product_id = $list[org_id];
			$sql = "select options from products where product_id = '$product_id'";
			$list = mysql_fetch_assoc(mysql_query($sql, $connect));
		}
		
		//-- 옵션 분리
		list($opt1, $opt2) = explode("\n", $list[options]);

		//-- 옵션이 없으면 하부코드를 읽어서 만든다.
		if ($list[options] == "") {
			$arr1 = array();
			$arr2 = array();
			$sql = "select options from products where org_id = '$product_id'";
			$result = mysql_query($sql, $connect)  or die(mysql_error());
			while ($list = mysql_fetch_assoc($result))
			{
				list($t1, $t2) = explode(",", $list[options]);
				if (!in_array($t1, $arr1)) $arr1[] = $t1;
				if (!in_array($t2, $arr2)) $arr2[] = $t2;

				$opt1 = ":" . implode(",", $arr1);
				$opt2 = ":" . implode(",", $arr2);
			}
		}

		//-- 신옵션 포맷 [opt1-opt2]
		//-- 구포맷 : :opt1,$opt2
		//-- 신포맷인지 구포맷인지를 판별해서 프로그램 수행
		if (strpos($opt1,":") === 0 && strpos($opt2,":") === 0) 
			$new_format = false;
		else
			$new_format = true;


		if ($new_format) {

			//-- 옵션1
			list($prefix1, $data1) = explode(":", $opt1);
			$opt1_arrs = explode(",", $data1);

			//-- 옵션2
			list($prefix2, $data2) = explode(":", $opt2);
			$opt2_arrs = explode(",", $data2);


		} else {
			//-- 옵션1
			$prefix1 = "옵션1";
			$opt1 = str_replace(":", "", $opt1);
			$opt1_arrs = explode(",", $opt1);

			//-- 옵션1
			$prefix2 = "옵션2";
			$opt2 = str_replace(":", "", $opt2);
			$opt2_arrs = explode(",", $opt2);
		}

		// $stocks = array();
		$sql = "select product_id, options from products where org_id = '$product_id'";
		$result = mysql_query($sql, $connect) or die(mysql_error());
		while ($list = mysql_fetch_assoc($result))
		{
			if ($new_format) {
				// 신 옵션포맷
				$options = str_replace(array('[',']'), '', $list[options]);
				list($opt1_name, $opt2_name) = explode("-", $options);
			} else {
				// 구 옵션포맷
				$options = str_replace(':', '', $list[options]);
				list($opt1_name, $opt2_name) = explode(",", $options);
			}


			$stock = class_stock::get_current_stock($list[product_id]);
			$stocks[trim($opt2_name)][trim($opt1_name)] = $stock;
		}

		//--------------------------------
		// make html
		$strHTML = "<div class='desc'><b>상품코드 :</b>$product_id &nbsp; <b>상품명</b> : $product_name</span></div>";

		$strHTML .= "<table cellspacing=1 cellpadding=0 class='tblStock' bgcolor=#CCCCCC>";

		//-----------------
		//-- header
		$strHTML .= "<tr>";
		$strHTML .= "<th class=left>&nbsp;&nbsp;&nbsp;&nbsp; $prefix2 <br/>$prefix1</th>";
		for ($i=0; $i < sizeof($opt2_arrs); $i++) {
			$strHTML .= "<th>$opt2_arrs[$i]</th>";
		}
		$strHTML .= "<th>합계</th>";
		$strHTML .= "</tr>";

		//-----------------
		//-- data
		$rowsum = array();
		$colsum = array();

		for ($j=0; $j < sizeof($opt1_arrs); $j++) {
			$strHTML .= "<tr bgcolor=#FFFFFF>";
			$strHTML .= "<td class=opt2><b>$opt1_arrs[$j]</b></td>";

			for ($i=0; $i < sizeof($opt2_arrs); $i++) {
				$stock = $stocks[trim($opt2_arrs[$i])][trim($opt1_arrs[$j])];
				$strHTML .= "<td>$stock</td>";

				$rowsum[$opt1_arrs[$j]] += $stock;
				$colsum[$opt2_arrs[$i]] += $stock;
			}
			$strHTML .= "<td class=sum>" . $rowsum[$opt1_arrs[$j]] ."</td>";
			$strHTML .= "</tr>";
		}


		//-----------------
		//-- footer
		$strHTML .= "<tr bgcolor=#FFFFFF>";
		$strHTML .= "<td class=sum>합계</td>";
		for ($i=0; $i < sizeof($opt2_arrs); $i++) {
			$strHTML .= "<td class=sum>" . $colsum[$opt2_arrs[$i]] . "</td>";
			$total_sum += $colsum[$opt2_arrs[$i]];
		}
		$strHTML .= "<td class=totalsum>$total_sum</td>";
		$strHTML .= "</tr>";

		//-----------------
		$strHTML .= "</table>";

		echo $strHTML;
	}

   function C270()
   {
      global $template, $connect, $product_id, $org_id;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

    function del_price()
    {
        global $connect, $seq;
        
        $query = "delete from price_history where seq=$seq";
debug("가격 삭제 : " . $query);
        mysql_query($query, $connect);
        
    }
}
?>
