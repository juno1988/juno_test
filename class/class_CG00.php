<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_B.php";
require_once "class_file.php";
require_once "class_combo.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_CG00 extends class_top
{
   function C209()
   {
      global $template, $id, $d_date;

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

   // image detail view or del popup
   function C204()
   {
      global $template;
      global $id,$param;

      // 상세 정보 가져온다
      $data = $this->get_detail( $id );

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
      global $template;

      $end_date= date('Y-m-d', strtotime('2 year'));

      // $val_item = array ( "supply_code"=>"공급처 코드" );
      // $this->validate ( $val_item );

      // 상세 정보 가져온다
      $data = $this->get_price_detail();

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
 
   // price popup 
   function C211()
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

   // 상품 리스트 
   function CG00()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();     
    
      $result = $this->get_list( &$total_rows, $page );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function build()
   {
       global $template, $string, $link_url, $connect;

       $query = "insert into product_group_folder
                    set name='" . addslashes($string) . "',
                        crdate = Now(),
                        userid = '" . $_SESSION[LOGIN_ID] . "'";

       mysql_query( $query, $connect );
       $this->redirect( $link_url );
   }

   /////////////////////////////////////////////////////////////////
   // 상품 그룹 삭제
   function del()
   {
       global $template, $string, $link_url, $connect, $id;

       // 그룹 자체를 삭제
       $query = "delete from product_group_folder where group_id=$id";
       mysql_query( $query, $connect );

       // 그룹에 등록되어 있는 상품 삭제
       $query = "delete from product_group where group_id=$id";
       mysql_query( $query, $connect );
       
       $this->redirect( $link_url );
   }

   //////////////////////////////////////////////
   // 상품 변경 폼
   // 벤더를 위한 상품 변경 폼
   function C213()
   {
      global $template;
      global $id, $connect;

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
      global $id, $connect, $top_url;

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

      // copy 상품의 정보 가져온다.
      $result = $this->get_stock_list( $id );

      // price table의 정보 가져온다.
      $result_price = $this->get_price_history();

      $master_code = substr( $template, 0,1);
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
      global $string, $disp_copy, $enable_sale;
      
      if ( !$page )
         $page = 1;
      $starter = ($page-1) * _line_per_page;

      // 총 개수를 구한다
      $query_cnt = "select count(*) cnt from product_group_folder";
      $result = mysql_query ( $query_cnt,$connect );
      $data = mysql_fetch_array ( $result );
      $max_row = $data[cnt];

      // data list
      $query = "select * from product_group_folder";
      $limit = " limit $starter, 20";
      $result = mysql_query ( $query . $limit, $connect );
      return $result;
   }   

   /////////////////////////////////////
   // copy list
   function get_copy_list ( $id )
   {
      global $connect;
      return class_C::get_copy_list ( $id );
   }

   function get_stock_list ( $id )
   {
      global $connect;

      $query = "select * from products 
                 where is_delete = 0 
                   and org_id = '$id'
                   and stock_manage = 1";

      $result = mysql_query( $query, $connect );

      return $result;
   }

   function init_product_reg()
   {
      $this->items = array("name","origin","brand","org_price","supply_price","shop_price","is_free_deliv","supply_code",
                           "options","product_desc", "enable_sale", "tax", "market_price");

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
 
      $org_items = array ("name", "origin", "brand", "img_500", "img_desc1", "img_desc2","img_desc3", "img_desc4", 
                   "product_desc","org_price", "supply_price", "shop_price", "is_free_deliv", "supply_code", "enable_sale" );

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

         $query .= "$item = '" . $data[$item] . "'";
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

     $this->end( $transaction );

     // self.close 한 후 opener의 location을 변경해야 
     global $top_url;
     $this->opener_redirect ( "template.htm?template=C208&id=$org_id&top_url=$top_url" );
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
                   "img_desc3", "img_desc4", "product_desc");

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
      global $connect;
      global $id;

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
      $images = array( "img_500", "img_desc1", "img_desc2","img_desc3","img_desc4" );

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

           if ( $$item || $item == "enable_sale" || $item == "product_desc" )  // enable_sale이 0일때도 처리 해야 함
              $query .= ", $item='" . addslashes( $$item ) . "'";
      }

      /////////////////////////////////////////
      // 저장
      $query .= " where product_id = '$id'";

//echo $supply_code;
//exit;
      mysql_query( $query, $connect );

      if ( $supply_code )
      { 
          ///////////////////////////////////////////////////
          // 복사본의 supply_code도 모두 변경해야 한다. 
          $query = "update products set supply_code = '$supply_code' where org_id='$id'"; 
          mysql_query( $query, $connect );

          ///////////////////////////////////////////////////
          // price_history의 supply_code도 모두 변경해야 한다. 
          $query = "update price_history set supply_code = '$supply_code', update_time=Now() where org_id='$id'"; 
          mysql_query( $query, $connect );
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
         $query = "update products set enable_sale='$enable_sale', last_update_date = Now() where org_id = '$id'";
         mysql_query( $query, $connect );
      }

      ///////////////////////////////////////////// 
      // 자식 상품의 이름 변경
      $query = "update products set name='" . addslashes( $name ) . "' where org_id='$id'";
      mysql_query( $query, $connect );

      if ( $stock_manage )
         $redirect = "C208";
      else
         $redirect = "C201";

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

      // update db
      $query = "update products set $param = '' where product_id='$id'";
      mysql_query ( $query, $connect );

      // delete file 
      class_file::del ( $img );

      global $top_url;
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

      $data = $this->get_detail( $_POST[id] );
      
      // 이미지 삭제 - 원본을 삭제할 경우에만 이미지를 삭제 함
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

      $transaction = $this->end( $transaction );

      global $top_url;
      $this->redirect ( $_POST["link_url"] . "&top_url=$top_url" );
      exit;
   }
  
   /////////////////////////////////////////
   // stock_build
   function stock_build()
   {
      class_C::stock_build();
   }

   function stock_delete()
   {
      global $connect, $id;

      $query = "update products set stock_manage=0 where product_id='$id'";
      mysql_query ( $query, $connect );

      $query = "delete from products where org_id='$id' and stock_manage=1";
      mysql_query ( $query, $connect );

      global $top_url;
      $this->redirect ( "?template=C201&id=$id&top_url=$top_url" );
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
         $query = "update products set current_stock = $value, last_update_date=Now() where product_id='$product_id'";
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

      $transaction = $this->begin("상품 다운로드 (CG00)");

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
                                "납품가"=>"supply_price",
                                "판매가"=>"shop_price",
                                "상품설명"=>"product_desc",
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
                  $buffer .=  $data[enable_sale] ? "판매가능" : "판매불가" . "\t";
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
 
      $query = "insert into price_history set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax', product_id='$product_id', start_date='$start_date', end_date='$end_date', shop_id='$shop_id', update_time=Now()";
      mysql_query ( $query, $connect );

      // product_table변경
       $query = "update products set org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax' 
                where product_id='$product_id'";
      mysql_query ( $query , $connect );

      $link_url = $link_url ? base64_decode( $link_url ) : "template.htm?template=C201&id=$product_id"; 

      global $top_url;
      $this->opener_redirect( $link_url . "&top_url=$top_url" );
      $this->closewin();

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
      $query = "update price_history set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax', shop_id='$shop_id', start_date='$start_date', end_date='$end_date', update_time=Now()
                where seq='$seq'";
      mysql_query ( $query, $connect );

      // product_table변경
       $query = "update products set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax' 
                where product_id='$product_id'";
       mysql_query ( $query , $connect );

      // product table의 org_id가 $product_id인 상품의 가격 변경
       $query = "update products set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax' 
                where org_id='$product_id'";
       mysql_query ( $query , $connect );

      $link_url = $link_url ? base64_decode ( $link_url ) : "template.htm?template=C201&id=$product_id"; 

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

      $query = "update products set is_delete = 1, delete_date=Now() where product_id='$org_id'";
//echo $query;
//exit;
      mysql_query ( $query, $connect );

      $this->end( $transaction );

      $this->jsAlert ( "삭제 되었습니다");

      global $top_url;
      $this->redirect ( $link_url . "&top_url=$top_url");
      exit;
   }

   //////////////////////////////////////////////
   // 삭제 
   // date : 2005.10.10 - jk

   //////////////////////////////////////////////
   // 되살리기
   // date : 2005.10.11 - jk
   function restore()
   {
      global $id, $connect, $link_url;

      $transaction = $this->begin("상품 되살리기");

      $query = "update products set is_delete = 0 where product_id='$id'";
      mysql_query ( $query, $connect );

      // delete copy products
      $query = "update products set is_delete = 0 where org_id='$id'";
      mysql_query ( $query, $connect );

      $this->end( $transaction );

      $this->jsAlert ( "부활 되었습니다");

      global $top_url;
      $this->redirect ( "?template=C201&id=$id" . "&top_url=$top_url" );
      exit;
   }

   function download2()
   {
      require_once 'Spreadsheet/Excel/Writer.php';

      global $connect, $saveTarget, $filename, $search_date;

      // Creating a workbook
      $workbook = new Spreadsheet_Excel_Writer();

      // sending HTTP headers
      $workbook->send( $filename . ".xls" );

      // Creating a worksheet
      $worksheet =& $workbook->addWorksheet('Sheet1');

      // download format에 대한 정보를 가져온다
      $download_items = array(
          "image"		=> "이미지",
          "product_id" 		=> "상품아이디",
          "product_name" 	=> "상품명",
          "option"		=> "선택사항",
          "org_price"		=> "원가",
          "shop_price"		=> "판매가격",
          "qty"			=> "판매개수",
          "order_date"		=> "주문일",
	  "collect_date"	=> "발주일",
          "order_name"		=> "주문자",
          "recv_name"		=> "수령자",
	  "memo"		=> "메모",	
      );

      //////////////////////////////////////////////
      // step 1.전체 출력 
      $opt = "";
      $download = 1;
      $result = $this->get_list( &$total_rows , 0, $download );

      $this->write_excel ( $worksheet, $result, $download_items, $rows );

      // Let's send the file
      $workbook->close();
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
              $arr_chars = array("`","/","=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'","<br>" );
              require_once "class_D.php"; 
              return class_D::get_product_option ( $data[product_id] ) ? 
                     str_replace( $arr_chars, " " , class_D::get_product_option($data[product_id])) : 
                     str_replace( $arr_chars, " ", $data[options] );
              break;
          default:
              $val = $data[$key] ? $data[$key] : "";
              return  str_replace( array("=","\r", "\n", "\r\n","\t" ), " ", $val );
           break; 
      }
   }

}
?>
