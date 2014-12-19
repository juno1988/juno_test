<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_B.php";
require_once "class_file.php";
require_once "class_combo.php";

//////////////////////////////////////////////
// get_list : ��ǰ ����Ʈ
// get_detail : ��ǰ �� ����

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

      // �� ���� �����´�
      $data = $this->get_detail( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
    
   // stock manage copy popup
   function C212()
   {
      global $template;
      global $id,$top_url;

      $val_item = array ( "supply_code"=>"����ó �ڵ�" );
      $this->validate ( $val_item );

      // �� ���� �����´�
      $data = $this->get_detail( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // copy popup
   function C202()
   {
      global $template;
      global $id;

      $val_item = array ( "supply_code"=>"����ó �ڵ�" );
      $this->validate ( $val_item );

      // �� ���� �����´�
      $data = $this->get_detail( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
 
   // price popup 
   function C210()
   {
      global $template;

      $end_date= date('Y-m-d', strtotime('2 year'));

      // $val_item = array ( "supply_code"=>"����ó �ڵ�" );
      // $this->validate ( $val_item );

      // �� ���� �����´�
      $data = $this->get_price_detail();

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
 
   // price popup 
   function C211()
   {
      global $template;


      $val_item = array ( "supply_code"=>"����ó �ڵ�" );
      $this->validate ( $val_item );

      // �� ���� �����´�
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
   // ������ ���� ��ǰ ����Ʈ 
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
   // ����Ʈ�� ���� ��ǰ ����Ʈ 
   // date: 2006.1.2
   function C216()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();     
    
      $result = $this->get_list( &$total_rows, $page );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // ��ǰ ����Ʈ 
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
   // ��ǰ �׷� ����
   function del()
   {
       global $template, $string, $link_url, $connect, $id;

       // �׷� ��ü�� ����
       $query = "delete from product_group_folder where group_id=$id";
       mysql_query( $query, $connect );

       // �׷쿡 ��ϵǾ� �ִ� ��ǰ ����
       $query = "delete from product_group where group_id=$id";
       mysql_query( $query, $connect );
       
       $this->redirect( $link_url );
   }

   //////////////////////////////////////////////
   // ��ǰ ���� ��
   // ������ ���� ��ǰ ���� ��
   function C213()
   {
      global $template;
      global $id, $connect;

      /////////////////////////////////////
      $option_list = $this->option_list($id);
      // $obj_combo = new category_combo($connect, "tbl_category","myform","","", $option_list);

      // �� ���� �����´�
      $data = $this->get_detail( $id );

      if ( $data[stock_manage] )
      {
         global $top_url;
         $this->redirect("?template=C214&id=$id" . "&top_url=$top_url");
         exit;
      }

      // copy ��ǰ�� ���� �����´�.
//    $result = $this->get_copy_list( $id );

      // price table�� ���� �����´�.
      $result_price = $this->get_price_history( 1 );

      // $obj_combo->disp_script_data();	

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";

      // $obj_combo->disp_script_engine();
   }


   // ��ǰ ���� ��
   function C201()
   {
      global $template;
      global $id, $connect, $top_url;

      /////////////////////////////////////
      $option_list = $this->option_list($id);
      // $obj_combo = new category_combo($connect, "tbl_category","myform","","", $option_list);

      // �� ���� �����´�
      $data = $this->get_detail( $id );

      if ( $data[stock_manage] )
      {
         global $top_url;
         $this->redirect("?template=C208&id=$id&top_url=$top_url");
         exit;
      }

      // copy ��ǰ�� ���� �����´�.
//    $result = $this->get_copy_list( $id );

      // price table�� ���� �����´�.
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
   // ������ ���� ��ǰ ���� �� ( ������)
   function C214()
   {
      global $template;
      global $id;

      $link_url = "template.htm?" . $this->build_link_url();

      // �� ���� �����´�
      $data = $this->get_detail( $id );

      if ( $data[org_id] )
      {
          $id = $data[org_id];
          $data = $this->get_detail( $data[org_id] );
      }

      // copy ��ǰ�� ���� �����´�.
      $result = $this->get_stock_list( $id );

      // price table�� ���� �����´�.
      $result_price = $this->get_price_history();

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // ��ǰ ���� �� ( ������)
   function C208()
   {
      global $template;
      global $id,$top_url;

      $link_url = "template.htm?" . $this->build_link_url();

      // �� ���� �����´�
      $data = $this->get_detail( $id );

      if ( $data[org_id] )
      {
          $id = $data[org_id];
          $data = $this->get_detail( $data[org_id] );
      }

      // copy ��ǰ�� ���� �����´�.
      $result = $this->get_stock_list( $id );

      // price table�� ���� �����´�.
      $result_price = $this->get_price_history();

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // ��ǰ ���� �� ( ��� ���� ��)
   function C206()
   {
      global $template;
      global $id;


      // �� ���� �����´�
      $data = $this->get_detail( $id );

      // copy ��ǰ�� ���� �����´�.
      $result = $this->get_copy_list( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function C203()
   {
      global $template;
      global $id, $org_id;

      // �� ���� �����´�
      $data = $this->get_detail( $id );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function match_input()
   {
      global $connect;
      global $product_id, $shop_id, $shop_pid;

      $transaction = $this->begin("��ġ���̺���");

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
   // ��ǰ �� ����
   function get_detail ( $id )
   {
      global $connect;
      return class_C::get_detail ( $id );
   }

   //////////////////////////////
   // ��ǰ ����Ʈ
   function get_list( &$max_row, $page, $download=0 )
   {
      global $connect;
      global $string, $disp_copy, $enable_sale;
      
      if ( !$page )
         $page = 1;
      $starter = ($page-1) * _line_per_page;

      // �� ������ ���Ѵ�
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

      $this->val_items = array("name"=>"��ǰ��", "org_price"=>"����", "supply_price"=>"���ް�","supply_code"=>"����ó" );
   }
 
   // copy
   function add_options()
   {
      global $connect, $org_id;

      $transaction = $this->begin("�ɼ� �߰�");

      // id ���� 
      $table_name = "products";
      $query = "select max(max) m from $table_name";
      $result = mysql_query($query);
      $data = mysql_fetch_array($result);
      $max = $data[m] + 1;
      $id = $id_index . sprintf("%05d", $max);
      $id = "S" . $id;

      // ���� data �������� �۾�
      $query = "select * from products where product_id='$org_id'";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_array($result);
 
      $org_items = array ("name", "origin", "brand", "img_500", "img_desc1", "img_desc2","img_desc3", "img_desc4", 
                   "product_desc","org_price", "supply_price", "shop_price", "is_free_deliv", "supply_code", "enable_sale" );

      $copy_items = array ("options", "org_id"); 

      // query ����� �κ�      
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
      // ���� table�� �� �߰�
      $query = "insert into price_history set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax', product_id='$id', start_date=Now(), update_time=Now()";
      mysql_query ( $query, $connect );

     $this->end( $transaction );

     // self.close �� �� opener�� location�� �����ؾ� 
     global $top_url;
     $this->opener_redirect ( "template.htm?template=C208&id=$org_id&top_url=$top_url" );
     $this->jsAlert("�ɼ� �߰� �Ǿ����ϴ�.");
     $this->closewin(); 

   }

  

   // copy
   function copy()
   {
      global $connect, $org_id;

      $transaction = $this->begin("����");

      // id ���� 
      $table_name = "products";
      $query = "select max(max) m from $table_name";
      $result = mysql_query($query);
      $data = mysql_fetch_array($result);
      $max = $data[m] + 1;
      $id = $id_index . sprintf("%05d", $max);
      $id = "C" . $id;

      // ���� data �������� �۾�
      $query = "select * from products where product_id='$org_id'";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_array($result);
 
      $org_items = array ("origin", "brand", "img_500", "img_desc1", "img_desc2",
                   "img_desc3", "img_desc4", "product_desc");

      $copy_items = array ("name","org_price", "supply_price", "shop_price", "is_free_deliv", "org_id", "supply_code"); 

      // query ����� �κ�      
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
      // ���� table�� �� �߰�
      $query = "insert into price_history set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax', product_id='$id', start_date=Now(), update_time=Now()";
      mysql_query ( $query, $connect );

     $this->end( $transaction );

     // self.close �� �� opener�� location�� �����ؾ� 
     global $top_url;
     $this->opener_redirect ( "template.htm?template=C201&id=$org_id&top_url=$top_url" );
     $this->jsAlert("���� �Ǿ����ϴ�.");
     $this->closewin(); 

   }

   //////////////////////////////
   // ���� ����
   function modify()
   {
      global $connect;
      global $id;

      $transaction = $this->begin( "��ǰ����" );

      $this->init_product_reg();

      /////////////////////////////////////////////////////
      // �ش� ��ǰ�� stock manage use���� ���θ� �˾ƾ� ��
      $query = "select stock_manage,org_id from products where product_id='$id'";
      $result = mysql_query ( $query,$connect );
      $data = mysql_fetch_array ( $result );
      $stock_manage = $data[stock_manage];

      if ( $data[org_id] )
         $id = $data[org_id];

      // image ����
      $images = array( "img_500", "img_desc1", "img_desc2","img_desc3","img_desc4" );

      ///////////////////////////////////////////////////////////////
      // query ����
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
           // ������ ��(�ɼǺ� ����)�ϰ�� options�� �������
           if ( $stock_manage && $item == "options" )
               continue;

           if ( $$item || $item == "enable_sale" || $item == "product_desc" )  // enable_sale�� 0�϶��� ó�� �ؾ� ��
              $query .= ", $item='" . addslashes( $$item ) . "'";
      }

      /////////////////////////////////////////
      // ����
      $query .= " where product_id = '$id'";

//echo $supply_code;
//exit;
      mysql_query( $query, $connect );

      if ( $supply_code )
      { 
          ///////////////////////////////////////////////////
          // ���纻�� supply_code�� ��� �����ؾ� �Ѵ�. 
          $query = "update products set supply_code = '$supply_code' where org_id='$id'"; 
          mysql_query( $query, $connect );

          ///////////////////////////////////////////////////
          // price_history�� supply_code�� ��� �����ؾ� �Ѵ�. 
          $query = "update price_history set supply_code = '$supply_code', update_time=Now() where org_id='$id'"; 
          mysql_query( $query, $connect );
      }

      ///////////////////////////////////////////////
      // ���� ���� ���� ����
      if ( $_REQUEST["change_price"] )
      {
         $query = "update code_match set modify_code = 3 where id = '$id'";
         $transaction = $this->end( $transaction, "���ݺ���" );
      }
      else
         $query = "update code_match set modify_code = 4 where id = '$id'";

      mysql_query ( $query );

      //////////////////////////////////////////////////////////
      // sale_stop_date or sale_start_date �Է�
      $org_enable_sale = $_REQUEST["org_enable_sale"];
      $enable_sale = $_REQUEST["enable_sale"];

      if ( $org_enable_sale == 1 && $enable_sale == 0 )
      {
         $query = "update products set sale_stop_date = Now() where product_id = '$id'";
         mysql_query( $query, $connect );
         $query = "update code_match set modify_code = 1 where id = '$id'";
         mysql_query( $query, $connect );

         // ǰ�� ó��
         $transaction = $this->end( $transaction, "ǰ��ó��" );
      }
      else if ( $org_enable_sale == 0 && $enable_sale == 1 )
      {
         $query = "update products set sale_start_date = Now() where product_id = '$id'";
         mysql_query( $query, $connect );
         $query = "update code_match set modify_code = 2 where id = '$id'";
         mysql_query( $query, $connect );

         // �Ǹ� ����
         $transaction = $this->end( $transaction, "�ǸŰ���" );
      }

      /////////////////////////////////////////
      // tbl_product_category table�� �� �߰�
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
      // �ڽ� ��ǰ�� �̸� ����
      $query = "update products set name='" . addslashes( $name ) . "' where org_id='$id'";
      mysql_query( $query, $connect );

      if ( $stock_manage )
         $redirect = "C208";
      else
         $redirect = "C201";

      global $top_url;
      $this->redirect("?template=$redirect&id=$id&top_url=$top_url");
      $this->jsAlert("���� �۾� �Ϸ�");
      exit;
   }

   /////////////////////////////////////
   // copy modify
   function copy_modify()
   {
      global $connect;
      global $org_id, $id;

      $copy_items = array ("name", "org_price", "supply_price", "shop_price", "is_free_deliv", "org_id", "supply_code", "enable_sale");

      // query ����� �κ�      
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
     
     // self.close �� �� opener�� location�� �����ؾ� 
     global $top_url;
     $this->opener_redirect ( "template.htm?template=C201&id=$org_id&top_url=$top_url" );
     $this->jsAlert("���� �Ǿ����ϴ�.");
     $this->closewin(); 
   }

   function copy_delete()
   {
      global $connect, $org_id, $id;

      $query = "update products set is_delete = 1 where product_id='$id'";
      mysql_query ($query, $connect );

     // self.close �� �� opener�� location�� �����ؾ� 
     global $top_url;
     $this->opener_redirect ( "template.htm?template=C201&id=$org_id&top_url=$top_url" );
     $this->jsAlert("���� �Ǿ����ϴ�.");
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
      $this->jsAlert("���� �Ǿ����ϴ�.");
      $this->closewin(); 
   }

   //////////////////////////////////////////
   // match data�� ������ ����
   // 2005.12.22
   // match data�� ����� ������ ����
   function product_delete()
   {
      global $connect;

      $transaction = $this->begin("��ǰ����");

      $data = $this->get_detail( $_POST[id] );
      
      // �̹��� ���� - ������ ������ ��쿡�� �̹����� ���� ��
      if ( !$data[org_id] ) 
      {
         $arr_img = array( "img_500", "img_desc1", "img_desc2", "img_desc3", "img_desc4" );

         foreach ( $arr_img as $img )
         {
            $img = $data[$img];

            if ( $img )
               class_file::del ( $img );
         } 

         // ���纻 ����
         $query = "delete from products where org_id = '$_POST[id]'";
         mysql_query ( $query, $connect );
      }

      ////////////////////////////////// 
      // code match ����
      $query = "delete from code_match where id= '$_POST[id]'";
      mysql_query ( $query, $connect );

      ////////////////////////////////// 
      // name match ����
      $query = "delete from name_match where id= '$_POST[id]'";
      mysql_query ( $query, $connect );

      // ���� ����
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

      $transaction = $this->begin( "��� ����" , $id);
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

      // ���� ����
      $query = "update products set enable_sale = 1 where org_id = '$id' and current_stock > 0";
      mysql_query ( $query, $connect );

      $this->end( $transaction);
      global $top_url;
      $this->redirect("?template=C208&id=$id&top_url=$top_url"); 
   }
   
   function download()
   {
      global $connect, $saveTarget;

      $transaction = $this->begin("��ǰ �ٿ�ε� (CG00)");

      $handle = fopen ($saveTarget, "w");

      $download = 1;
      $result = $this->get_list( &$total_rows , 0, $download );

      ////////////////////////////////////////
      // writing datas to file
      $i = 1;
 
      $download_items = array ( "���̵�"=>"product_id",
                                "����"=>"enable_sale",
                                "��ǰ��"=>"name",
                                "�ɼ�"=>"options",
                                "����ó"=>"supply_code",
                                "����"=>"org_price",
                                "��ǰ��"=>"supply_price",
                                "�ǸŰ�"=>"shop_price",
                                "��ǰ����"=>"product_desc",
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
                  $buffer .=  $data[enable_sale] ? "�ǸŰ���" : "�ǸźҰ�" . "\t";
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

      // file ����
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
   // option�� 1�� ���� �Ϻ� ��ü���� ������ �����;� �� ���
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
 
      // end data�� ���� row�� end_date�Է�
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

      // product_table����
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

      // end data�� ���� row�� end_date�Է�
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

      // product_table����
       $query = "update products set supply_code='$supply_code', org_price='$org_price',
                supply_price='$supply_price', shop_price='$shop_price', is_free_deliv='$is_free_deliv',
                tax='$tax' 
                where product_id='$product_id'";
       mysql_query ( $query , $connect );

      // product table�� org_id�� $product_id�� ��ǰ�� ���� ����
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
   // ���� 
   // date : 2005.10.10 - jk
   function del_option()
   {
      global $org_id, $connect, $link_url;

      $transaction = $this->begin("��ǰ����");

      $query = "update products set is_delete = 1, delete_date=Now() where product_id='$org_id'";
//echo $query;
//exit;
      mysql_query ( $query, $connect );

      $this->end( $transaction );

      $this->jsAlert ( "���� �Ǿ����ϴ�");

      global $top_url;
      $this->redirect ( $link_url . "&top_url=$top_url");
      exit;
   }

   //////////////////////////////////////////////
   // ���� 
   // date : 2005.10.10 - jk

   //////////////////////////////////////////////
   // �ǻ츮��
   // date : 2005.10.11 - jk
   function restore()
   {
      global $id, $connect, $link_url;

      $transaction = $this->begin("��ǰ �ǻ츮��");

      $query = "update products set is_delete = 0 where product_id='$id'";
      mysql_query ( $query, $connect );

      // delete copy products
      $query = "update products set is_delete = 0 where org_id='$id'";
      mysql_query ( $query, $connect );

      $this->end( $transaction );

      $this->jsAlert ( "��Ȱ �Ǿ����ϴ�");

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

      // download format�� ���� ������ �����´�
      $download_items = array(
          "image"		=> "�̹���",
          "product_id" 		=> "��ǰ���̵�",
          "product_name" 	=> "��ǰ��",
          "option"		=> "���û���",
          "org_price"		=> "����",
          "shop_price"		=> "�ǸŰ���",
          "qty"			=> "�ǸŰ���",
          "order_date"		=> "�ֹ���",
	  "collect_date"	=> "������",
          "order_name"		=> "�ֹ���",
          "recv_name"		=> "������",
	  "memo"		=> "�޸�",	
      );

      //////////////////////////////////////////////
      // step 1.��ü ��� 
      $opt = "";
      $download = 1;
      $result = $this->get_list( &$total_rows , 0, $download );

      $this->write_excel ( $worksheet, $result, $download_items, $rows );

      // Let's send the file
      $workbook->close();
   }    

   /////////////////////////////////////////////////////// 
   // excel�� write ��
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
