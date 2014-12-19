<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_file.php";
require_once "class_supply.php";
require_once "class_auto.php";
require_once "class_lock.php";
require_once "ExcelReader/reader.php";
require_once "class_stock.php";
require_once "ExcelParserPro/excelparser.php";
require_once "class_multicategory.php";

class class_CS00 extends class_top
{ 
   var $items;
   var $val_items;

   function CS00()
   {
        global $template;
        global $connect;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
   }

    //
    // 작업 등록..
    function reg_work()
    {
        global $connect, $shop_id, $start_date, $end_date;
        
        $query = "insert into sync_history 
                          set crdate=Now()
                             ,start_date = '$start_date'
                             ,end_date   = '$end_date'
                             ,shop_id    = '$shop_id'
                             ,owner      = '" . $_SESSION['LOGIN_NAME'] . "'
                             ";      
        mysql_query( $query, $connect );
        echo $query;
    }

    function reg_list()
    {
        global $connect;
        
        $query = "";
        
    }

    var $m_qty = 0;
    function reg_product( $arr_product, $id )
    {
          global $connect, $id;

          $query = "insert into products set ";
          $i = 0;

          foreach ( $arr_product as $key => $val )
          {
              if( $key == "option_use" ||
                  $key == "enable_dup" )  continue;

              $query .= $i ? "," : "";
              if( $key == "name" )
                  $query .=  $key . "=\"" . strip_tags(addslashes($val)) . "\"";
              else
                  $query .=  $key . "=\"" . htmlspecialchars(addslashes($val)) . "\"";
              $i++;
          }
          $query .= ",reg_date=Now(), reg_time=Now(), last_update_date=now(), enable_sale=1";
          // 이미지 url 있으면
          if( $arr_product[img_500]   ||
              $arr_product[img_desc1] ||
              $arr_product[img_desc2] ||
              $arr_product[img_desc3] ||
              $arr_product[img_desc4] ||
              $arr_product[img_desc5] ||
              $arr_product[img_desc6] )  $query .= ",is_url_img=1";
          if( !mysql_query( $query, $connect ) )  return false;

          // 옵션관리
          if( $arr_product[option_use] )
            class_C::stock_build( $arr_product[name], $arr_product[options], $arr_product[product_id] );
          else
          {
	    	$barcode = $this->get_barcode($arr_product[product_id]);
    	    $query = "update products 
                             set barcode='$barcode'
                           where product_id='$arr_product[product_id]'";
    	    mysql_query ( $query, $connect );

            //옵션 상품에 대한 재고 0 초기화
            class_stock::new_current_stock($arr_product[product_id]);
          }

            // 멀티카테고리 
            $obj = new class_multicategory();
            $obj->save_str_category($arr_product[str_category]);

          // price history에 값 입력
          // tax: 0 과세설정 2009.9.15 jk
          $query = "insert into price_history 
                       set org_price     = '$arr_product[org_price]'
                          ,supply_price  = '$arr_product[supply_price]'
                          ,shop_price    = '$arr_product[shop_price]'
                          ,start_date    = Now()
                          ,end_date      = '2019-06-23' 
                          ,supply_code   = '$arr_product[supply_code]'
                          ,tax           = '0' 
                          ,is_free_deliv = '$is_free_deliv' 
                          ,product_id    = '$arr_product[product_id]'";
          if( !mysql_query( $query, $connect ) )  return false;

        // 판매처 가격 자동등록
        if( $_SESSION[USE_PRODUCT_PRICE] )
        {
            $id          = $arr_product[product_id];
            $org_price   = $arr_product[org_price];
            $supply_code = $arr_product[supply_code];
            
            $query_shop = "select * from shopinfo where disable=0 and auto_price=1";
            $result_shop = mysql_query($query_shop, $connect);
            while($data_shop = mysql_fetch_assoc($result_shop))
            {
                $charge = $data_shop[charge] / 100;
                $margin = $data_shop[margin] / 100;
                
                // margin 포함가 => 공급가
                $margin_p = $org_price / (1 - $margin);
                
                // charge 포함가 => 판매가
                $charge_p = $margin_p / (1 - $charge);
                
                $query_shop_price = "insert into price_history 
                                        set product_id = '$id',
                                            start_date = Now(),
                                            end_date = '2019-06-23',
                                            org_price = '$org_price',
                                            supply_price = '$margin_p',
                                            shop_price = '$charge_p',
                                            supply_code = '$supply_code',
                                            shop_id = '$data_shop[shop_id]',
                                            tax = 0,
                                            is_free_deliv = 0,
                                            update_time = Now()";
                mysql_query($query_shop_price, $connect);
            }
        }
          
          //current_stock table 재고 0 초기화
          //class_stock::new_current_stock( $id );  
          
          return true;
    }

    function dup_check3( $name )
    {
          global $connect;

          $query  = "select count(*) cnt from products where name='$name' and is_delete=0 and (stock_manage=0 or is_represent=1)";
          $result = mysql_query( $query, $connect );
          $data   = mysql_fetch_array( $result );
          
          return $data[cnt] ? $data[cnt] : 0; 
    }

    function dup_check2( $name )
    {
          global $connect;

          $query  = "select count(*) cnt from products where name='$name' and is_delete=0";
          $result = mysql_query( $query, $connect );
          $data   = mysql_fetch_array( $result );
          
          return $data[cnt] ? $data[cnt] : 0; 
    }

    function dup_check( $product_id )
    {
          global $connect;

          $query = "select count(*) cnt from products where product_id='$product_id'";
          $result = mysql_query( $query, $connect );
          $data   = mysql_fetch_array( $result );

          return $data[cnt] ? $data[cnt] : 0; 
    }

    ///////////////////////////////////
    // 상품 data를 upload한다
    // CSV로 상품 data를 upload해야 함 
    function upload()
    {
        global $connect, $admin_file, $_file;
        
        $transaction = $this->begin("대량등록");
  
        $obj = new class_file();
        $arr = $obj->upload();

        $this->show_wait();
        
        $err_result = "";
        $err_cnt = 0;
        
        $i = 0;
        $n = 0;
        $row_cnt = count( $arr );
        
        $max = $this->get_max();            
        $product_id = sprintf("%05d",$max);
        $id = "S" . $product_id;
        
        foreach ( $arr as $row )
        {
            $i++;
            if ( $i <= 1 ) continue;  // 헤더
            if ( $i == $row_cnt ) continue;  // 마지막행

            // 필수 입력 항목이 없으면 넘어간다.
            if( !$row[0] )
            {
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 상품명을 입력하세요 <br> ";
                continue;
            }else if( !$row[1] ){
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 공급처 코드를 입력하세요 <br> ";
                continue;
            }else if( !$row[7] ){
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 원가를 입력하세요 <br> ";
                continue;
            }else if( !$row[8] ){
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 공급가를 입력하세요 <br> ";
                continue;
            }else if( !$row[9] ){
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 판매가를 입력하세요 <br> ";
                continue;
            }
                
            // 중복 상품명 자료는 입력하지 않는다.
            if( !$_SESSION[DUP_PRODUCT_NAME] )
            {
                if ( $this->dup_check2( $row[0] ) )
                {
                    if( $err_cnt++ < 20 )
                        $err_result .= " $i 행 : 상품명이 중복되었습니다. <br> ";
                    continue;
                }
            }
            
            // 공급처 코드 검사
            $query = "select * from userinfo where code='$row[1]' and level=0";
            $result = mysql_query($query, $connect);
            if( !mysql_num_rows($result) )
            {
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 존재하지 않는 공급처코드입니다.<br> ";
                continue;
            }
            
            // 바코드 검사
            if( $row[15] && class_product::dup_check_barcode( $row[15] ) )
            {
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 바코드가 중복되었습니다.<br> ";
                continue;
            }

            $max = $this->get_max();            
            $product_id = sprintf("%05d",$max);
            
            // 공급처가 없으면 자사
            $arr_product = array(
                product_id      => $product_id,
                max             => $max,
                name            => $row[0 ],
                supply_code     => $row[1 ],
                brand           => $row[2 ],
                supply_options  => $row[3 ],
                origin          => $row[4 ],
                trans_fee       => $row[5 ],
                weight          => $row[6 ],
                org_price       => $row[7 ],
                supply_price    => $row[8 ],
                shop_price      => $row[9 ],
                market_price    => $row[10],
                options         => $row[11] . "\n" . $row[12] . "\n" . $row[13],
                option_use      => $row[14],
                barcode         => $row[15],
                img_500         => $row[16],
                img_desc1       => $row[17],
                img_desc2       => $row[18],
                img_desc3       => $row[19],
                img_desc4       => $row[20],
                img_desc5       => $row[21],
                img_desc6       => $row[22],
                product_desc    => $row[23],
                stock_alarm1    => $row[24],
                stock_alarm2    => $row[25],
                pack_disable    => $row[26],
                pack_cnt        => $row[27],
                location        => $row[28],
                memo            => $row[29],
                maker           => $row[30],
                product_gift    => $row[31],
                md              => $row[32],
                manager1        => $row[33],
                manager2        => $row[34],
                is_free_deliv   => $row[35],
                str_category    => $row[36]
            );

            if ( $i % 87 == 0 )
            $this->show_txt( $i . "/" . count($arr));          
            
            // 실제 data 저장
            if( !$this->reg_product( $arr_product, $id ) )
            {   
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 상품등록에 실패했습니다. 고객센터로 문의바랍니다. <br> ";
                continue;
            }
            $n++;
        }
       
        $this->hide_wait();
        $this->jsAlert("$n 개 입력 완료 되었습니다.");
    
        $err_result = $this->base64_encode_url($err_result);
        $this->redirect("?template=CS00&err_cnt=$err_cnt&err_result=$err_result");
        
    }

   // get max
   function get_max()
   {
          global $connect;
          $query = "select max(max) m from products";
          $result = mysql_query ( $query, $connect );
          $data   = mysql_fetch_array( $result );
          //return sprintf("%05d", $data[m]);
          return $data[m]+1;
   }

   ////////////////////////////////////////////// 
   // 상품 저장 format
   // id 0, name 1, desc 2, shop_price 3, supply_price 4, org_price 5, brand 6, supply_code 7, 
   // options 8 | 로 구분, options 9, options 10, desc1 11, org_id [option]
   // 
   function save2( $data, $x )
   {
        global $connect;

          // max값을 구함
          $query = "select max(max) m from products";
          $result = mysql_query ( $query, $connect );
          $m = mysql_fetch_array ( $result );

          $max = $m[m] + 1;

          // 입력 이 안된경우
                  if ( !$data[1-$x] ) 
                     $product_id         = sprintf ( "%05d",$max );
          else
                     $product_id         = sprintf ( "%05d",$data[1-$x] );

        ////////////////////////////////////////
        // query 생생
        // 판매가능 상태
        $org_price          = $data[6-$x];
        $supply_price     = $data[5-$x];

        $tax                  = 1;                // 비과세
        $is_free_deliv    = 1;                // 선불
        $supply_code        = $data[8-$x];

        $options            = $data[9-$x] . "\n" . $data[10-$x] . "\n" . $data[11-$x];
        $options            = str_replace("|","\n", $options);
        $options            = addslashes(htmlspecialchars( $options));

        for( $i=0; $i < count( $data) ; $i++ )
        {
          // $data[$i] = str_replace( array("\"", "."),"",$data[$i]);
          $data[$i] = htmlspecialchars( addslashes( $data[$i] ));
        }

        $query = "insert into products set product_id='$product_id',
                                                     max='$max',
                                                     product_desc=\"" . $data[3-$x] ."\",
                                                     reg_date = Now(), 
                                                     reg_time=Now(),
                                                     last_update_date=Now(),
                                                     enable_sale ='1', 
                                                     name         =\"" . $data[2-$x] ."\", 
                                                     supply_code='$supply_code', 
                                                     brand='" . $data[7-$x] . "', 
                                                     org_price='" . $data[6-$x] . "', 
                                                     shop_price='" . $data[4-$x] . "',
                                                     supply_price='" . $data[5-$x] . "',
                                                     options = '" . $options . "',
                                                     img_500 = '" . $data[12-$x] . "',
                                                     img_desc1 = '" . $data[13-$x] ."',
                                                     img_desc2 = '" . $data[14-$x] . "',
                                                     img_desc3 = '" . $data[15-$x] . "',
                                                     img_desc4 = '" . $data[16-$x] . "'";

        /////////////////////////////////////////
        // 저장
        mysql_query( $query, $connect ) or die ("잘못된 질의를 실행했습니다!! / $query ");

        //////////////////////////////////////////
        // 가격 table에 값 추가
        // 기본
        $query = "insert into price_history set supply_code='$supply_code', org_price='$org_price',
                    supply_price='$supply_price', shop_price='" . $data[4-$x] . "', is_free_deliv='$is_free_deliv',
                    tax='$tax', product_id='$product_id', start_date=Now(), end_date='2012-6-9'";

        mysql_query( $query, $connect ) or die ("잘못된 질의를 실행했습니다!!");

   }

   function save( $data)
   {
        global $connect;

        $product_id          = sprintf ( "%05d",$data[0] );

        ////////////////////////////////////////
        // query 생생
        // 판매가능 상태
        $org_price           = $data[5];
        $supply_price           = $data[4];

print "가격:" . $supply_price . "<br>";
//exit;

        $tax                     = 1;                    // 비과세
        $is_free_deliv           = 1;                    // 선불
        $product_id          = sprintf ( "%05d",$data[0] );
        $supply_code          = $data[7];

        $options            = "$data[8]\n$data[9]\n$data[10]";
        $options            = str_replace("|","\n", $options);
        $options            = str_replace( array("\"", "."),"",$options);

        $query = "insert into products set product_id='$product_id',
                                                     max='$data[0]',
                                                     product_desc='$data[2]',
                                                     reg_date = Now(), 
                                                     reg_time=Now(),
                                                     last_update_date=Now(),
                                                     enable_sale='1', 
                                                     name='" . addslashes( str_replace("\"\"", "\"", $data[1] ) ) ."', 
                                                     supply_code='$supply_code', 
                                                     brand='$data[6]', 
                                                     org_price='$data[5]', 
                                                     shop_price='$data[3]',
                                                     supply_price='$data[4]',
                                                     options = '$options',
                                                     img_500 = '$data[11]',
                                                     img_desc1 = '$data[12]',
                                                     img_desc2 = '$data[13]',
                                                     img_desc3 = '$data[14]',
                                                     img_desc4 = '$data[15]'
";
   exit;

        /////////////////////////////////////////
        // 저장
        mysql_query( $query, $connect ) or die ("잘못된 질의를 실행했습니다!! / $query ");
        
        //////////////////////////////////////////
        // 가격 table에 값 추가
        // 기본
        $query = "insert into price_history set supply_code='$supply_code', org_price='$org_price',
                    supply_price='$supply_price', shop_price='$data[3]', is_free_deliv='$is_free_deliv',
                    tax='$tax', product_id='$product_id', start_date=Now(), end_date='2012-6-9'";

        mysql_query( $query, $connect ) or die ("잘못된 질의를 실행했습니다!!");
    }

    ///////////////////////////////////
    // 상품 data를 update한다
    // 
    function update()
    {
        global $connect, $admin_file, $_file;
        
        $obj_lock = new class_lock(407);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->redirect("?template=C610");
            exit;
        }

        $transaction = $this->begin("대량변경");

        $arr = array();
        $obj = new class_file();
        if( $obj->upload2('', &$arr) )
        {
            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $this->jsAlert($msg);
            }
            $this->redirect("?template=C610");
            exit;
        }

        $this->show_wait();
        
        $err_result = "";
        $err_cnt = 0;
        
        $i = 0;
        $n = 0;
        $row_cnt = count( $arr );
        foreach ( $arr as $row )
        {
            $i++;
            if ( $i <= 1 ) continue;  // 헤더
            if ( $i == $row_cnt ) continue;  // 마지막행

            $arr_product = array(
                "product_id"     => $row[2] ,
                "name"           => $row[3] ,
                "supply_code"    => $row[4] ,
                "brand"          => $row[6] ,
                "supply_options" => $row[7] ,
                "origin"         => $row[8] ,
                "trans_fee"      => $row[9] ,
                "weight"         => $row[10],
                "org_price"      => $row[11],
                "supply_price"   => $row[12],
                "shop_price"     => $row[13],
                "market_price"   => $row[14],
                "options"        => $row[15],
                "stock_manage"   => $row[16],
                "barcode"        => $row[17],
                "img_500"        => $row[18],
                "img_desc1"      => $row[19],
                "img_desc2"      => $row[20],
                "img_desc3"      => $row[21],
                "img_desc4"      => $row[22],
                "img_desc5"      => $row[23],
                "img_desc6"      => $row[24],
                "product_desc"   => $row[25],
                "enable_sale"    => $row[26],
                "enable_stock"   => $row[27],
                "stock_alarm1"   => $row[30],
                "stock_alarm2"   => $row[31],
                "is_delete"      => $row[32],
                "pack_disable"   => $row[33],
                "pack_cnt"       => $row[34],
                "memo"           => $row[36],
                "category"       => $row[37],
                "location"       => $row[38],
                "maker"          => $row[39],
                "product_gift"   => $row[40],
                "md"             => $row[41],
                "manager1"       => $row[42],
                "manager2"       => $row[43],
                "is_free_deliv"  => $row[44]
            );

            if ( $i % 10 == 0 )
            $this->show_txt( $i . "/" . count($arr));          
            // 실제 data 업데이트
            $r = $this->update_product( $arr_product, &$err_cnt, &$err_result );
            if( $r )
            {
                if( $err_cnt++ < 20 )
                {
                    switch( $r )
                    {
                        case 1:
                            $err_result .= " $i 행 : 등록되지 않은 상품코드입니다. <br> ";
                            break;
                        case 2:
                            $err_result .= " $i 행 : 상품삭제에 실패했습니다. 주문 또는 재고가 있습니다.<br> ";
                            break;
                        case 3:
                            $err_result .= " $i 행 : 옵션상품 변경에 실패했습니다. <br> ";
                            break;
                        case 4:
                            $err_result .= " $i 행 : 중복된 상품명입니다. <br> ";
                            break;
                        case 5:
                            $err_result .= " $i 행 : 등록되지 않은 공급처코드입니다. <br> ";
                            break;
                        case 6:
                            $err_result .= " $i 행 : 상품 변경에 실패했습니다. <br> ";
                            break;
                        case 7:
                            $err_result .= " $i 행 : 옵션관리취소에 실패했습니다. 주문 또는 재고 정보가 있습니다.<br> ";
                            break;
                        case 8:
                            $err_result .= " $i 행 : 옵션관리설정에 실패했습니다. 주문 또는 재고 정보가 있습니다.<br> ";
                            break;
                        case 9:
                            $err_result .= " $i 행 : 가격정보 변경에 실패했습니다. <br> ";
                            break;
                        case 10:
                            $err_result .= " $i 행 : 옵션이 중복되었습니다. <br> ";
                            break;
                        case 11:
                            $err_result .= " $i 행 : 바코드가 중복되었습니다. <br> ";
                            break;
                        case 12:
                            $err_result .= " $i 행 : 옵션이 없습니다. <br> ";
                            break;
                        case 13:
                            $err_result .= " $i 행 : 하위 옵션상품 변경에 실패했습니다. <br> ";
                            break;
                        default:
                            $err_result .= " $i 행 : 고객센터로 문의바랍니다. <br> ";
                            break;
                    }
                }
            }
            else
                $n++;
        }
        
        $this->hide_wait();
        $this->jsAlert("$n 개 변경 완료 되었습니다.");
        
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }

        $err_result = $this->base64_encode_url($err_result);
        $this->redirect("?template=C610&err_cnt=$err_cnt&err_result=$err_result");
    }

    function update_product( $arr_product )
    {
        global $connect;

        $obj = new class_product();

        $this->show_txt( ++$this->m_qty );

        // 현재의 정보를 얻어온다.
        $query = "select * from products where product_id='$arr_product[product_id]' and is_delete=0";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
            $data = mysql_fetch_assoc($result);
        else
            return 1;
        
        // 상품 삭제일 경우
        if( $arr_product[is_delete] )
        {
            // 주문 또는 재고가 있는지 체크
            if( !$this->check_order($arr_product[product_id]) )  return 2;

            $query = "update products set is_delete=1, delete_date=now() where product_id='$arr_product[product_id]' or org_id='$arr_product[product_id]'";
            mysql_query($query, $connect);

            // 매칭정보 삭제
            $query = "delete a from code_match a, products b where a.id=b.product_id and ( b.product_id='$arr_product[product_id]' or b.org_id='$arr_product[product_id]' )";
            mysql_query ( $query, $connect );

            // 삭제된 상품이 옵션 상품이면, 대표상품의 판매상태 갱신
            if( $data[stock_manage] && !$data[is_represent] )
                class_C::update_soldout( $data[org_id] );
            return 0;
        }
        
        //++++++++++++++++++++++++++++
        // 옵션상품일 경우
        //++++++++++++++++++++++++++++
        if( $data[stock_manage] && !$data[is_represent] )
        {
            // 옵션입력체크
            if( !$arr_product[options] )
                return 12;
            
            // 옵션중복체크
            if( $arr_product[options] != $data[options] )
            {
                if( class_product::dup_check_options( $arr_product[options], $data[product_id], $data[org_id] ) )
                    return 10;
            }
            
            // 바코드중복체크
            if( $arr_product[barcode] != $data[barcode] )
            {
                if( class_product::dup_check_barcode( $arr_product[barcode], $data[product_id] ) )
                    return 11;
            }
            
            $query = "update products set ";
            
            // 품절일 경우 품절일
            if( !$arr_product[enable_sale] )
                $query .= " sale_stop_date = if(enable_sale>0, now(), sale_stop_date), ";
                
            foreach ( $arr_product as $key => $val )
            {
                // 옵션, 공급처 옵션, 바코드 정보 
                if( $key == "options" || $key == "supply_options" || $key == "barcode" || $key == "enable_sale" || 
                    $key == "stock_alarm1" || $key == "stock_alarm2"  || $key == "memo" || $key == "location" )
                    $query .=  $key . "=\"" . htmlspecialchars(addslashes($val)) . "\",";
            }
            // 맨 마지막 쉼표 빼고, where 붙이기
            $query = substr($query, 0, -1) . " where product_id='$arr_product[product_id]'";

            if( !mysql_query($query, $connect) )  return 3;
            
            // 판매상태가 변경됐을 경우, 대표상품의 판매상태 갱신
            if( $data[enable_sale] != $arr_product[enable_sale] )
                class_C::update_soldout( $data[org_id] );
        }

        //++++++++++++++++++++++++++++
        // 대표상품, 또는 일반상품일 경우
        //++++++++++++++++++++++++++++
        else
        {
            // 상품명이 변경됐을 경우, 중복확인
            if( trim($data[name], " ") != trim($arr_product[name], " ") && !$_SESSION[DUP_PRODUCT_NAME] )
                if( $this->dup_check3( $arr_product[name] ) )  return 4;

            // 바코드중복체크
            if( $arr_product[barcode] != $data[barcode] )
            {
                if( class_product::dup_check_barcode( $arr_product[barcode], $data[product_id] ) )
                    return 11;
            }
            
            // 공급처가 변경됐을 경우, 공급처 코드 검사
            if( $data[supply_code] != $arr_product[supply_code] )
            {
                $query_supply = "select * from userinfo where code='$arr_product[supply_code]' and level=0";
                $result_supply = mysql_query($query_supply, $connect);
                if( !mysql_num_rows($result_supply) )  return 5;
            }
            
            $query = "update products set ";
            foreach ( $arr_product as $key => $val )
            {
                // 건너뛸 정보
                if( $key == "product_id" || 
                    $key == "is_delete"  || 
                    $key == "enable_dup" || 
                    $key == "stock_manage" )  continue;
                $query .=  $key . "=\"" . htmlspecialchars(addslashes($val)) . "\",";
            }

            // 이미지가 변경됐을 경우, 무조건 url_img
            if( $data[img_500]   != $arr_product[img_500]   ||
                $data[img_desc1] != $arr_product[img_desc1] ||
                $data[img_desc2] != $arr_product[img_desc2] ||
                $data[img_desc3] != $arr_product[img_desc3] ||
                $data[img_desc4] != $arr_product[img_desc4] ||
                $data[img_desc5] != $arr_product[img_desc5] ||
                $data[img_desc6] != $arr_product[img_desc6] )
                $query .=  "is_url_img=1,";
            
            $query = substr($query, 0, -1) . " where product_id='$arr_product[product_id]'";
            if( !mysql_query($query, $connect) )  return 6;
            
            // 대표상품의 경우, 옵션상품에도 적용
            if( $data[is_represent] )
            {
                $query = "update products set ";
                foreach ( $arr_product as $key => $val )
                {
                    // 옵션 정보
                    if( $key=="supply_code" || $key=="name" || $key=="origin" || $key=="brand" || $key=="trans_fee" ||
                        $key=="market_price" || $key=="trans_code" || $key=="weight" || $key=="enable_stock" ||
                        $key=="org_price" || $key=="supply_price" || $key=="shop_price" ||
                        $key == "stock_alarm1" || $key == "stock_alarm2" || $key == "pack_disable" || $key == "pack_cnt" || $key == "memo" || $key == "category" || $key == "location" )
                        $query .=  $key . "=\"" . htmlspecialchars(addslashes($val)) . "\",";
                }
                $query = substr($query, 0, -1) . " where org_id='$arr_product[product_id]' and is_delete=0";
                if( !mysql_query($query, $connect) )  return 13;
            }
            
            // 옵션관리가 변경됐을 경우
            if( $data[stock_manage] != $arr_product[stock_manage] )
            {
                // 옵션관리취소
                if( $data[is_represent] && $data[options] && ($arr_product[stock_manage] == "0") )
                {
                    // 주문, 재고 검증
                    if( !$this->check_order($arr_product[product_id]) )  return 7;

                    // 옵션상품 리스트
                    $obj->get_option_id($arr_product[product_id], &$id_arr, &$id_str, false);

                    // 매칭정보 삭제
                    $query_match = "delete from code_match where id in ($id_str)";
                    mysql_query($query_match, $connect);
                    
                    $this->stock_delete($arr_product[product_id]);
                }
                // 옵션관리 
                if( !$data[is_represent] && ($arr_product[stock_manage] == "1") )
                {
                    // 주문, 재고 검증
                    if( !$this->check_order($arr_product[product_id]) )  return 8;
                    
                    // 매칭정보 삭제
                    $query_match = "delete from code_match where id='$arr_product[product_id]'";
                    mysql_query($query_match, $connect);
                    
                    $this->stock_build($arr_product[product_id]);
                }
            }
            
            // 판매상태가 변경됐을 경우, 옵션상품 판매상태 변경
            if( $data[is_represent] && ($data[enable_sale] != $arr_product[enable_sale]) )
            {
                if( $arr_product[enable_sale] == "0" )
                {
                    $query_sale = "update products set sale_stop_date=if(enable_sale>0,now(),sale_stop_date), enable_sale=0 where org_id='$arr_product[product_id]' and is_delete=0";
                    if( !mysql_query($query_sale, $connect) )  return 13;
                }
                else if( $arr_product[enable_sale] == "1" )
                {
                    $query_sale = "update products set enable_sale=1 where org_id='$arr_product[product_id]' and is_delete=0";
                    if( !mysql_query($query_sale, $connect) )  return 13;
                }
                else
                    class_C::update_soldout( $arr_product[product_id] );
            }
            
            // 기초가격 정보가 변경됐을 경우
            if( $data[org_price]    != $arr_product[org_price]    ||
                $data[supply_price] != $arr_product[supply_price] ||
                $data[shop_price]   != $arr_product[shop_price] )
            {
                $query = "update price_history 
                             set org_price    = '$arr_product[org_price]',
                                 supply_price = '$arr_product[supply_price]',
                                 shop_price   = '$arr_product[shop_price]',
                                 update_time  = now()
                           where product_id = '$arr_product[product_id]' and
                                 shop_id = 0";
                if( !mysql_query($query, $connect) )  return 9;
            }
        }
        
        return 0;
    }

    // 일괄등록에서 사용하는 체크함수
    function check_order($product_id)
    {
         global $connect;

         if( !$product_id )  return false;
         
         // product_id는 원 상품 하위 상품이 없는지 확인
         $query       = "select product_id from products where org_id='$product_id' and is_delete=0";
         $result      = mysql_query( $query, $connect );
         $product_ids = "'" . $product_id . "',";
         while ( $data = mysql_fetch_array( $result ) )
             $product_ids .= "'" . $data[product_id] . "',";
         $product_ids = substr( $product_ids, 0, strlen( $product_ids) -1 ); 
 
         // 주문에서 찾는다.
         $query   = "select count(*) cnt from order_products where product_id in ( $product_ids )";
         $result  = mysql_query( $query, $connect );
         $data    = mysql_fetch_array( $result );
         if( $data[cnt] ) return false;
         
         // current_stock에서 찾는다.        
         $query = "select sum(stock) cnt from current_stock where product_id in ( $product_ids )";
         $result = mysql_query( $query, $connect );
         $data   = mysql_fetch_assoc( $result );
         if( $data[cnt] ) return false;
         
         return true;
    }

    function stock_delete($id)
    {
        global $connect;
        
        $query = "update products set stock_manage=0, is_represent=0 where product_id='$id'";
        mysql_query ( $query, $connect );
        
        // 상품코드 목록
        $p_list = '';
        $query = "select product_id from products where org_id='$id'";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $p_list .= ( $p_list ? "," : "" ) . "'" . $data[product_id] . "'" ;
            
        // 매칭 삭제
        $query = "delete from code_match where id in ($p_list)";
        mysql_query($query, $connect);
        
        // 상품 삭제
        $query = "delete from products where org_id='$id'";
        mysql_query ( $query, $connect );
    }

    function stock_build($product_id)
    {
        global $connect;

        $query = "select options,name from products where product_id='$product_id'";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array ( $result );
        class_C::stock_build( $data[name], $data[options], $product_id );
    }   

    ///////////////////////////////////
    // 상품 data를 개별 update한다
    // 
    function update2()
    {
        global $connect, $admin_file, $_file;
        
        $this->show_wait();

        $obj_lock = new class_lock(408);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->redirect("?template=C620");
            exit;
        }

        $transaction = $this->begin("대량변경");

        $arr = array();
        $obj = new class_file();
        if( $obj->upload2('', &$arr) )
        {
            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $this->jsAlert($msg);
            }
            $this->redirect("?template=C620");
            exit;
        }

        // 필드선택
        switch( str_replace(" ","",$arr[0][1]) )
        {
            case "상품명"       :  $field_type = "name"          ; break;
            case "공급처코드"   :  $field_type = "supply_code"   ; break;
            case "공급처상품명" :  $field_type = "brand"         ; break;
            case "공급처옵션"   :  $field_type = "supply_options"; break;
            case "원산지"       :  $field_type = "origin"        ; break;
            case "택배비"       :  $field_type = "trans_fee"     ; break;
            case "중량"         :  $field_type = "weight"        ; break;
            case "원가"         :  $field_type = "org_price"     ; break;
            case "공급가"       :  $field_type = "supply_price"  ; break;
            case "판매가"       :  $field_type = "shop_price"    ; break;
            case "시중가"       :  $field_type = "market_price"  ; break;
            case "옵션"         :  $field_type = "options"       ; break;
            case "옵션관리"     :  $field_type = "stock_manage"  ; break;
            case "바코드"       :  $field_type = "barcode"       ; break;
            case "대표이미지"   :  $field_type = "img_500"       ; break;
            case "설명이미지1"  :  $field_type = "img_desc1"     ; break;
            case "설명이미지2"  :  $field_type = "img_desc2"     ; break;
            case "설명이미지3"  :  $field_type = "img_desc3"     ; break;
            case "설명이미지4"  :  $field_type = "img_desc4"     ; break;
            case "설명이미지5"  :  $field_type = "img_desc5"     ; break;
            case "비고이미지"   :  $field_type = "img_desc6"     ; break;
            case "상품설명"     :  $field_type = "product_desc"  ; break;
            case "판매상태"     :  $field_type = "enable_sale"   ; break;
            case "재고관리"     :  $field_type = "enable_stock"  ; break;
            case "재고경고수량" :  $field_type = "stock_alarm1"  ; break;
            case "재고위험수량" :  $field_type = "stock_alarm2"  ; break;
            case "삭제"         :  $field_type = "is_delete"     ; break;
            case "합포불가"     :  $field_type = "pack_disable"  ; break;
            case "동일상품합포가능수량" :  $field_type = "pack_cnt" ; break;
            case "메모"         :  $field_type = "memo" ; break;
            case "카테고리"     :  $field_type = "category" ; break;
            case "로케이션"     :  $field_type = "location" ; break;
            case "제조사"       :  $field_type = "maker" ; break;
            case "사은품"       :  $field_type = "product_gift" ; break;
            case "담당MD"       :  $field_type = "md" ; break;
            case "관리자(정)"   :  $field_type = "manager1" ; break;
            case "관리자(부)"   :  $field_type = "manager2" ; break;
            case "무료배송"     :  $field_type = "is_free_deliv" ; break;
            default:
                // Lock End
                if( !$obj_lock->set_end(&$msg) )
                {
                    $this->jsAlert($msg);
                }

                $this->hide_wait();
                $this->jsAlert("필드선택 헤더가 잘못되었습니다.");
                $this->redirect("?template=C620&err_cnt=$err_cnt&err_result=$err_result");
                return;
        }

        $err_result = "";
        $err_cnt = 0;
        
        $i = 0;
        $n = 0;
        $row_cnt = count( $arr );
        foreach ( $arr as $row )
        {
            $i++;
            if ( $i <= 1 ) continue;  // 헤더
            if ( !$row[0] ) continue;  // 마지막행

            $arr_product = array(
                "product_id"     => $row[0] ,
                "data"           => $row[1]
            );
            
            if ( $i % 79 == 0 )
            $this->show_txt( $i . "/" . count($arr));          
            
            // 실제 data 업데이트
            $r = $this->update_product2( $arr_product, $field_type );
            if( $r )
            {
                if( $err_cnt++ < 100 )
                {
                    switch( $r )
                    {
                        case 1:
                            $err_result .= " $i 행 : 등록되지 않은 상품코드입니다. <br> ";
                            break;
                        case 2:
                            $err_result .= " $i 행 : 상품삭제에 실패했습니다. 주문 또는 재고가 있습니다.<br> ";
                            break;
                        case 3:
                            $err_result .= " $i 행 : 옵션상품 변경에 실패했습니다. <br> ";
                            break;
                        case 4:
                            $err_result .= " $i 행 : 중복된 상품명입니다. <br> ";
                            break;
                        case 5:
                            $err_result .= " $i 행 : 등록되지 않은 공급처코드입니다. <br> ";
                            break;
                        case 6:
                            $err_result .= " $i 행 : 상품 변경에 실패했습니다. <br> ";
                            break;
                        case 7:
                            $err_result .= " $i 행 : 옵션관리취소에 실패했습니다. 주문 또는 재고 정보가 있습니다.<br> ";
                            break;
                        case 8:
                            $err_result .= " $i 행 : 옵션관리설정에 실패했습니다. 주문 또는 재고 정보가 있습니다.<br> ";
                            break;
                        case 9:
                            $err_result .= " $i 행 : 가격정보 변경에 실패했습니다. <br> ";
                            break;
                        case 10:
                            $err_result .= " $i 행 : 옵션이 중복되었습니다. <br> ";
                            break;
                        case 11:
                            $err_result .= " $i 행 : 바코드가 중복되었습니다. <br> ";
                            break;
                        case 12:
                            $err_result .= " $i 행 : 옵션이 없습니다. <br> ";
                            break;
                        case 13:
                            $err_result .= " $i 행 : 하위 옵션상품 변경에 실패했습니다. <br> ";
                            break;
                        case 14:
                            $err_result .= " $i 행 : 대표상품코드를 입력하세요. <br> ";
                            break;
                        default:
                            $err_result .= " $i 행 : 고객센터로 문의바랍니다. <br> ";
                            break;
                    }
                }
            }
            else
                $n++;
        }

        // 삭제작업시, 옵션상품이 모두 삭제된 대표상품 삭제
        if( $field_type == "is_delete" )
        {
            $query = "update products a left outer join products b 
                          on a.product_id=b.org_id and 
                             b.is_delete=0
                         set a.is_delete=1,
                             a.delete_date=now()
                       where a.is_delete=0 and 
                             a.is_represent=1 and
                             b.product_id is null";
            mysql_query($query, $connect);
        }
        
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }

        $this->hide_wait();
        $this->jsAlert("$n 개 변경 완료 되었습니다.");
        
        $err_result = $this->base64_encode_url($err_result);
        $this->redirect("?template=C620&err_cnt=$err_cnt&err_result=$err_result");
    }

    function update_product2( $arr_product, $field_type )
    {
        global $connect;

        $this->show_txt( ++$this->m_qty );

        $obj = new class_product();

        $pid = $arr_product[product_id];
        $val = $arr_product[data];
        
        // 상품정보
        $pinfo = $obj->get_info2($pid);
        if( !$pinfo )  return 1;
        
        // 상품삭제
        if( $field_type == "is_delete" )
        {
            if( $val == 1 )
            {
                $ret = $obj->delete_product($pid);  // 1:상품 없음, 2:주문,재고 있음
                
                // 옵션상품 삭제시 대표상품 판매상태 갱신
                if( !$ret && $pinfo[stock_manage] == 1 && $pinfo[is_represent] == 0 )
                    class_C::update_soldout( $pinfo[org_id] );
                    
                return $ret;
            }
            else
                return 0;
        }
        
        // 옵션관리
        if( $field_type == "stock_manage" )
        {
            // 옵션관리취소
            if( $pinfo[is_represent] == 1 && $val == 0 )
            {
                // 옵션상품 리스트
                $obj->get_option_id($pid, &$id_arr, &$id_str, false);
        
                // 주문, 재고 확인
                if( $obj->check_order_stock($id_str) )  
                    return 7;
                else
                {
                    $query_match = "delete from code_match where id in ($id_str)";
                    mysql_query($query_match, $connect);
                    
                    $this->stock_delete($pid);
                }
            }
            // 옵션관리 
            else if( $pinfo[stock_manage] == 0 && $pinfo[options] && $val == 1 )
            {
                // 주문, 재고 확인
                if( $obj->check_order_stock($pid) )  
                    return 8;
                else
                {
                    $query_match = "delete from code_match where id = '$pid'";
                    mysql_query($query_match, $connect);
                    
                    $this->stock_build($pid);
                }
            }
            return 0;
        }

        //*******************************************
        // 여기부터는 값이 같을 경우 작업 안함
        if( trim($val, " ") == trim($pinfo[$field_type], " ") )  return 0;

        // 상품명 중복체크
        if( $field_type == "name" && !$_SESSION[DUP_PRODUCT_NAME] )
        {
            if( $this->dup_check3($val) )  
                return 4;
        }

        // 공급처 코드 확인
        if( $field_type == "supply_code" )
        {
            if( !class_supply::get_info($val) )  
                return 5;
        }

        // 옵션 중복체크
        if( $field_type == "options" )
        {
            if( $pinfo[stock_enable] == 1 && 
                $pinfo[is_represent] == 0 &&
                $obj->dup_check_options( $val, $pid, $pinfo[org_id] ) )
                return 10;
        }

        // 바코드 중복체크
        if( $field_type == "barcode" )
        {
            if( $obj->dup_check_barcode($val, $pid) )
                return 11;
        }

        // 대표 설정에 옵션코드 입력이 아닌경우
        if( $field_type == "name"         ||
            $field_type == "supply_code"  ||
            $field_type == "brand"        ||
            $field_type == "origin"       ||
            $field_type == "trans_fee"    ||
            $field_type == "weight"       ||
            $field_type == "org_price"    ||
            $field_type == "supply_price" ||
            $field_type == "shop_price"   ||
            $field_type == "market_price" ||
            $field_type == "stock_manage" ||
            $field_type == "maker"        ||
            $field_type == "product_gift" ||
            $field_type == "md"           ||
            $field_type == "manager1"     ||
            $field_type == "manager2"     ||
            $field_type == "is_free_deliv" ||
            $field_type == "img_500"      ||
            $field_type == "img_desc1"    ||
            $field_type == "img_desc2"    ||
            $field_type == "img_desc3"    ||
            $field_type == "img_desc4"    ||
            $field_type == "img_desc5"    ||
            $field_type == "img_desc6"    ||
            $field_type == "product_desc" ||
            $field_type == "enable_stock" ||
            $field_type == "pack_disable" ||
            $field_type == "pack_cnt"     ||
            $field_type == "category"     )
        {
            if( $pinfo[stock_manage] == 1 && $pinfo[is_represent] == 0 )
                return 14;
            else
                $obj->get_option_id($pid, &$id_arr, &$id_str);
        }
        // 판매가능/품절, 재고경고수량, 재고위험수량
        else if( ($field_type == "enable_sale"  || 
                  $field_type == "stock_alarm1" || 
                  $field_type == "stock_alarm2") && $pinfo[is_represent] )
        {
            $obj->get_option_id($pid, &$id_arr, &$id_str);
        }
        else
        {
            $id_str = "'" . $pid . "'";
        }

        // 품절일 경우 품절일 
        if( $field_type == "enable_sale" && $val == 0 )
            $sale_stop_date = " sale_stop_date = if(enable_sale>0,now(),sale_stop_date), ";
        else
            $sale_stop_date = "";
            
        $val = htmlspecialchars(addslashes($val));
        $query = "update products set $sale_stop_date $field_type='$val' where product_id in ($id_str) and is_delete=0";

        mysql_query($query, $connect);

        // 판매가능/품절 변경시 대표상품 상태 변경
        if( $field_type == "enable_sale" )
        {
            if( $pinfo[stock_manage] == 1 && $pinfo[is_represent] == 0 )
                class_C::update_soldout( $pinfo[org_id] );
        }

        // 기초가격 정보가 변경됐을 경우
        if( $field_type == "org_price"    ||
            $field_type == "supply_price" ||
            $field_type == "shop_price"   )
        {
            $query = "update price_history set $field_type='$val', update_time=now() where product_id='$pid' and shop_id=0";
            mysql_query($query, $connect);
        }
        return 0;
    }

}
?>
