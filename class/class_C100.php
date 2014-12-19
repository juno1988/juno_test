<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_file.php";
require_once "class_combo.php";
require_once "class_3pl.php"; // library추가 2007.11.12
require_once "class_ui.php"; // library추가 2007.11.12
require_once "class_category.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_multicategory.php";

class class_C100 extends class_top
{ 
   var $items;
   var $val_items;

   function dup_check()
   {
    global $connect, $name;
//    $name = iconv('utf-8','cp949', $name );
    $val = array();
    $obj = new class_product();
    $val[is_dup] = $obj->dup_check( $name );
    
    echo json_encode( $val );
   }

   function C100()
   {
      global $template, $connect;
//      $obj_combo = new category_combo($connect, "tbl_category","myform","","", $option_list);

     
      $link_url = "?" . $this->build_link_url();

      $this->init_product_reg();
      $this->validate( $this->val_items );

//      $obj_combo->disp_script_data();    

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";

//      $obj_combo->disp_script_engine();
   }

   //////////////////////////////////////////////////
   // 
   function C102()
   {
      global $template, $connect;
//      $obj_combo = new category_combo($connect, "tbl_category","myform","","", $option_list);

      $this->init_product_reg();
      $this->validate( $this->val_items );

//      $obj_combo->disp_script_data();    

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
//      $obj_combo->disp_script_engine();
   }


    function init_product_reg()
    {
        // 공통필드
        $this->items = array("name","brand","origin","weight","trans_fee","trans_code","market_price","product_desc","product_desc2","is_url_img",
                             "options","supply_options","barcode","supply_code", "org_price", "supply_price", "shop_price","category","location","memo",
                             "maker","is_free_deliv","no_sync","no_stock_sync","product_gift","md","manager1","manager2","str_category","is_url_img0","is_url_img1",
                             "is_url_img2","is_url_img3", "is_url_img4","is_url_img5","is_url_img6","m_sub_category_1","m_sub_category_2","m_sub_category_3","trans_type","start_date","reserve_qty");

        $this->val_items = array("name"=>"상품명", "supply_code"=>"공급처" );
    }

   ////////////////////////////////////////////// 
   // 상품 저장
   function save()
   {
      global $connect, $transaction, $link_url, $option_use, $match_cancel;
      global $is_url_img0, $is_url_img1, $is_url_img2, $is_url_img3, $is_url_img4, $is_url_img5, $is_url_img6;

      $this->init_product_reg();

      $query = "select max(max) m from products";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_array($result);

      $max = $data[m] + 1;
      $id = $id_index . sprintf("%05d", $max);
      
      $transaction = $this->begin( "상품등록" , $id);

      // image 저장
      $images = array( "img_500", "img_desc1", "img_desc2","img_desc3","img_desc4", "img_desc5", "img_desc6" );

      // query 생생
      $query = "insert products set product_id='$id', reg_date = Now(), reg_time=Now(),last_update_date=Now(),
                enable_sale='1', enable_stock=1, max='$max', stock_manage=0 ";

      foreach( $images as $item )
      {
           global $$item;
           $index = split("_", $item) ;

           $key = $item . "_name";
           global $$key;

            
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

      foreach( $this->items as $item )
      {
          global $$item;

       // utf-8로 데이터가 전송되는 경우가 있음
       // date: 2008.5.20 
          if ( $this->is_utf8( $$item ) )
               $$item = iconv('utf-8', 'cp949', $$item );
          
          if( $item == "is_url_img" || $item == "pack_disable" || $item == "is_url_img0" || $item == "is_url_img1" || 
              $item == "is_url_img2"|| $item == "is_url_img3" || $item == "is_url_img4" || $item == "is_url_img5" || $item == "is_url_img6")
                $query .= ", $item='" . ( $$item == "on" ? 1 : 0 ) . "'";
          else if( $item == "m_sub_category_1" )
            $query .= ", m_category1='$m_sub_category_1'";
          else if( $item == "m_sub_category_2" )
            $query .= ", m_category2='$m_sub_category_2'";
          else if( $item == "m_sub_category_3" )
            $query .= ", m_category3='$m_sub_category_3'";
          else if( $item == "start_date" )
				$query .= ", sale_date='" . $$item . "'";
          else if( $item == "str_category" )
          {
            global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
            $str_query ="select * from multi_category a where search_id IN ($m_sub_category_1, $m_sub_category_2, $m_sub_category_3) order by depth ASC";
            $str_result = mysql_query($str_query, $connect);
            
            $i = 0;
            $query .= ", str_category='";
            while($str_data = mysql_fetch_assoc($str_result))
            {
            	$query .= "$str_data[seq] > ";
            	$i++;
            }
            for(;$i < 3;$i++)
            	$query .= "0 > ";
            	
            $query .= "'";
          }
          else
                $query .= ", $item='" . $$item . "'";
          
      }
   
debug ("[reg product] $query" );

      /////////////////////////////////////////
      // 저장
      mysql_query( $query, $connect );
      
      //특수기능 - 매칭시 자동취소
       if( $match_cancel )
           $query = "update products set match_cancel='1' where product_id='$id'";            
       else
           $query = "update products set match_cancel='0' where product_id='$id'";            
       mysql_query( $query, $connect );
     
		
      // 옵션관리
      if( $option_use && preg_match('/:.+/', $options) )
        class_C::stock_build( $name, $options, $id );
      // 옵션관리 아닐 경우 barcode 생성
      else
      {
	    	$barcode = $this->get_barcode($id);
    	    $query = "update products 
                             set barcode='$barcode'
                           where product_id='$id' and barcode='' ";
    	    mysql_query ( $query, $connect );

            //옵션 상품에 대한 재고 0 초기화
            class_stock::new_current_stock($id);
      }
      
      //////////////////////////////////////////
      // 가격 table에 값 추가
      $end_date = date('Y-m-d', strtotime('+3 year'));

      global $supply_code, $org_price, $supply_price, $shop_price;
      $query = "insert into price_history set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax', product_id='$id', start_date=Now(), end_date='$end_date'";
debug ("[reg product_history] $query" );
      mysql_query ( $query, $connect );
      
      // org_price_history
      $query = "insert org_price_history 
                   set product_id = '$id',
                       org_price = '$org_price',
                       start_date = now(),
                       worker = '$_SESSION[LOGIN_ID]',
                       work_date = now(),
                       is_base = 1";
      mysql_query($query, $connect);
      
        // 판매처 가격 자동등록
        if( $_SESSION[USE_PRODUCT_PRICE] )
        {
            $query_shop = "select * from shopinfo where disable=0 and auto_price=1";
            $result_shop = mysql_query($query_shop, $connect);
            while($data_shop = mysql_fetch_assoc($result_shop))
            {
                $su_price = (int)($shop_price * ( 1 - $data_shop[charge] / 100 ) + 0.5);
                $query_shop_price = "insert into price_history 
                                        set product_id = '$id',
                                            start_date = Now(),
                                            end_date = '$end_date',
                                            org_price = '$org_price',
                                            supply_price = '$su_price',
                                            shop_price = '$shop_price',
                                            supply_code = '$supply_code',
                                            shop_id = '$data_shop[shop_id]',
                                            tax = 0,
                                            is_free_deliv = 0,
                                            update_time = Now()";
                mysql_query($query_shop_price, $connect);
            }
        }

      $transaction = $this->end( $transaction );
      
      global $top_url;
      if ( $link_url )
      {
         $this->jsAlert ( "신상품이 등록되었습니다" );   
         $this->redirect( base64_decode ( $link_url ) . "&top_url=$top_url" );
      }
      else
      {
         $this->redirect("?template=C208&id=$id&top_url=$top_url");
      }

      exit;
   }

    function load_supply()
    {
        global $connect;
        
        $val = array();
        $query = "select * from userinfo where level=0 order by name";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $val['list'][] = array( "code" => $data[code], "name" => $data[name] );
        }
        echo json_encode( $val );
    }

    // 신상품 등록시, 상품명 입력값으로 시작하는 상품명 보여주기
    function get_prd_list()
    {
        global $connect, $name;
        
        $val = array();
        $val["list"] = "";
        $query = "select name from products where name like '%$name%' and substring(product_id,1,1)<>'S' and is_delete=0 order by name";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $val["list"] .= $data[name] . "<br>";
        }
        
        echo json_encode($val);
    }
}
?>
