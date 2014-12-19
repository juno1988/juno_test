<?
/*
    name: shoplinker������ ���� class
    date: 2011.6.10 - jkryu
*/

require_once "class_file.php";

class class_shoplinker
{
    function class_shoplinker()
    {
        $this->arr_items = array("ez_product_id"
                        ,"sl_product_name"
                        ,"sl_sale_status"
                        ,"sl_category_1"
                        ,"sl_category_2"
                        ,"sl_category_3"
                        ,"sl_category_4"
                        ,"sl_maker"
                        ,"sl_origin"
                        ,"sl_start_price"
                        ,"sl_sale_price"
                        ,"sl_delivery_charge_type"
                        ,"sl_delivery_charge"
                        ,"sl_tax_yn"
                        ,"sl_detail_desc"
                        ,"sl_quantity"
                        //,"sl_option_name1"
                        //,"sl_option_value1"
                        //,"sl_option_name2"
                        //,"sl_option_value2"
                        ,"sl_opt_info"
                        );
                        
        $this->arr_imgs = array("sl_image_url"
                       ,"sl_image_url2"
                       ,"sl_image_url3"
                       ,"sl_image_url4"
                       ,"sl_image_url5"
                       ,"sl_image_url16"
                       ,"sl_image_url17"
                       ,"sl_image_url18"
                       ,"sl_image_url19"
                       ,"sl_image_url20"
                        );
    }
    
    //
    // data load
    function load( $product_id )
    {
        global $connect;
        
        $query  = "select * from sl_products where ez_product_id='$product_id'";
        $result = mysql_query ($query, $connect );        
        $data   = mysql_fetch_assoc( $result ); 
        
        // image�� url�������� ����� ����.
        foreach ( $this->arr_imgs as $img )
        {
            // http�� �������� ������ upload�� �̹�����.
            // txt�� link�� �ø��� ��쿣 ������ http�� �־ �����Ѵ�.            
            if ( substr($data[$img],4) != "http" )
            {
                if ( $data[$img] )
                    $data[$img] = "./uploads/" . _DOMAIN_ . "/" . $data[$img];
            }
        }
        
        return $data;
    }
    
    //
    // ���, ����
    function reg()
    {
        global $connect, $product_id, $options;
        
        // 
        if ( $this->is_reg( &$data ) )
        {
            $query = "update sl_products set last_update_date = Now() ";
            
            
            
            $query_end = " where ez_product_id='$product_id'";   
        }
        else
        {
            $query = "insert into sl_products set last_update_date = Now() ";
        }
        
        // option ó�� �κ�..
        // sl_option_kind: 000: �ɼǾ��� ��ǰ, 001: �ɼǰ��� ���, 002: �� �ɼǺ� ����, �����Է�
        // sl_option_kind�� 001�� ���
        //  option_name1, option_value1�� ���� �־� ������.
        // sl_option_kind�� 002�� ���
        //  opt_info�� ���� �ִµ�, �ɼǺ� ���ݺ� �������� Ʋ�� ��� ����Ѵ�.
        if ( $options )
        {
            $sl_option_kind = "001";
            $arr_options = split("\r\n", $options );
            
            if ( count($arr_options) >= 1 )
            {
                list( $sl_option_name1, $sl_option_value1 ) = split(":",$arr_options[0]);   
                $query .= ",sl_option_name1 = '$sl_option_name1', sl_option_value1='$sl_option_value1'";   
            }
            
            if ( count($arr_options) >= 2 )
            {
                list( $sl_option_name2, $sl_option_value2 ) = split(":",$arr_options[1]);   
                $query .= ",sl_option_name2 = '$sl_option_name2', sl_option_value2='$sl_option_value2'";   
            }
            
        }
        else
        {
            $sl_option_kind = "000";   
        }
        
        // �ɼ��� ���°�� ��ǰ 000
        // �ɼ��� �ִ°�� 001
        $query .= " ,sl_option_kind = '$sl_option_kind'";
        
        
        // product_id�� sl_products�� �ԷµǾ� �ִ��� ���� Ȯ����        
        foreach( $this->arr_items as $item )
        {
            global $$item;
            if ( $item == "sl_category_1" )
                $query .= ", sl_category_l='" . $this->get_category_id( $$item ) . "'";
            else if ( $item == "sl_category_2" )
                $query .= ", sl_category_m='" . $this->get_category_id( $$item ) . "'";
            else if ( $item == "sl_category_3" )
                $query .= ", sl_category_s='" . $this->get_category_id( $$item ) . "'";
            else if ( $item == "sl_category_4" )
                $query .= ", sl_category_d='" . $this->get_category_id( $$item ) . "'";
            else
                $query .= ", $item='" . addslashes( $$item ) . "'";
        } 
        
        //**********************************************
        //
        // image ó��..
        //
        //**********************************************
        foreach( $this->arr_imgs as $item )
        {
            echo "<br>---</br>";
            echo $item;
            echo "<br>---</br>";
            
            global $$item;
            $index = split("_", $item) ;
            $key = $item . "_name";
            $chk_del = $item . "_del";
            global $$key, $$chk_del;
            if( $$chk_del )
            {
                // ���� üũ
                $query .= ", $item = null";
            }
            else
            {
                if( $is_url_img )
                {
                    $key = "txt_" . $item;
                    global $$key;
            
                    $filename = $$key;
                    $query .= ", $item = '$filename'";
                }
                else
                {
                    if ( $$key )
                    {
                        print_r ( $item . "=>" . $$item );
                        print_r ( $key . "=>" . $$key );
                        echo "<br> index: ";
                        print_r ( $index );
                                                                       
                        $filename = class_file::save($$item, $$key, $product_id, $index[2]);
                        $query .= ", $item = '$filename'";
                   }
                    else if( $data[is_url_img] )
                    {
                        $query .= ", $item = ''";
                    }
                }
            }
        }
        
        echo "<br>";
        echo $query . $query_end;
        
        mysql_query( $query . $query_end, $connect );
    }
    
    //
    // category id�����´�.
    function get_category_id( $seq )
    {
        // sys db�� connect�ؾ� ��.
        $sys_connect = sys_db_connect();
        
        if ( $seq )
        {
            $query = "select * from sl_category where seq=$seq"; 
            $result = mysql_query( $query, $sys_connect );
            $data   = mysql_fetch_assoc( $result );
            
            return $data["sl_category_id"];
        }
        else
        {
            return "";   
        }
    }
    
    //
    // ��� ���� Ȯ�� ��
    function is_reg( &$data )
    {
        global $connect, $product_id;
        
        $query = "select * from sl_products where ez_product_id='$product_id'";  
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        // print_r ( $data );
        
        return $data ? 1 : 0;
    }
    
    // 
    // ����Ŀ�� ���� ����
    // 2011.6.13 - jk
    // http://ad2.shoplinker.co.kr/shoplinker_API/XML_INFO/xmlInsert.php?iteminfo_url=xml�ּ�
    function sl_upload_product()
    {
        global $connect;
        
        // xml ���� ����
        $url = $this->build_xml();
        $xml_url = "http://" . $_SERVER[HTTP_HOST] . "/xml/" . $url;
        
        $request = "http://ad2.shoplinker.co.kr/shoplinker_API/XML_INFO/xmlInsert.php?iteminfo_url=" . $xml_url;
        echo $request;
        
        $response = file_get_contents( $request );
        echo $response;   
    }
    
    // xml ���� ����
    function build_xml()
    {
        global $connect, $product_id;
        
        $query  = "select * from sl_products where ez_product_id='$product_id'";       
        $result = mysql_query($query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        $file_name = "sl_" . $_SESSION[LOGIN_DOMAIN] . "_" . rand(0,5). ".xml";
        //echo $file_name;
        
        $handle = fopen("./xml/$file_name", "w");
        
        //print_r ( $data );
        $_date      = date('Ymd');
        $str = '<?xml version="1.0" encoding="euc-kr"?> 
        <openmarket> 
        <messageHeader> 
        <sendID>1</sendID> 
        <senddate>'.$_date.'</senddate> 
        </messageHeader> 
        <productInfo>
        ';
        fwrite($handle, $str );
        
        $str = "
        <product>
<customer_id>a0006001</customer_id>
<partner_product_id>"            . iconv('utf-8','cp949', $data[ez_product_id]  )         . "</partner_product_id>
<product_name><![CDATA["         . iconv('utf-8','cp949', $data[sl_product_name])         . "]]></product_name>
<sale_status>"                   . iconv('utf-8','cp949', $data[sl_sale_status] )         . "</sale_status>
<category_l>![CDATA["            . iconv('utf-8','cp949', $data[sl_category_l]  )         . "]></category_l>
<category_m>![CDATA["            . iconv('utf-8','cp949', $data[sl_category_m]  )         . "]></category_m>
<category_s>![CDATA["            . iconv('utf-8','cp949', $data[sl_category_s]  )         . "]></category_s>
<category_d>![CDATA["            . iconv('utf-8','cp949', $data[sl_category_d]  )         . "]></category_d>
<maker><![CDATA["                . iconv('utf-8','cp949', $data[sl_maker]       )         . "]]></maker>
<origin><![CDATA["               . iconv('utf-8','cp949', $data[sl_origin]      )         . "]]></origin>
<start_price>"                   . iconv('utf-8','cp949', $data[sl_start_price] )         . "</start_price>
<market_price>"                  . iconv('utf-8','cp949', $data[sl_sale_price]  )         . "</market_price>
<sale_price>"                    . iconv('utf-8','cp949', $data[sl_sale_price]  )         . "</sale_price>
<supply_price>"                  . iconv('utf-8','cp949', $data[sl_sale_price]  )         . "</supply_price>
<market_price_p>"                . iconv('utf-8','cp949', $data[sl_sale_price]  )         . "</market_price_p>
<sale_price_p>"                  . iconv('utf-8','cp949', $data[sl_sale_price]  )         . "</sale_price_p>
<supply_price_p>"                . iconv('utf-8','cp949', $data[sl_sale_price]  )         . "</supply_price_p>
<delivery_charge_type><![CDATA[" . iconv('utf-8','cp949', $data[sl_delivery_charge_type]) . "]]></delivery_charge_type>
<delivery_charge>"               . iconv('utf-8','cp949', $data[sl_delivery_charge]     ) . "</delivery_charge>
<tax_yn>"                        . iconv('utf-8','cp949', $data[sl_tax_yn]              ) . "</tax_yn>
<detail_desc><![CDATA["          . iconv('utf-8','cp949', $data[sl_detail_desc]         ) . "]]></detail_desc>
<sex>001</sex>                   
<option_kind>"                   . iconv('utf-8','cp949', $data[sl_option_kind]   )       . "</option_kind>
<option_name  num='1'><![CDATA[" . iconv('utf-8','cp949', $data[sl_option_name1]  )       . "]]></option_name>
<option_value num='1'><![CDATA[" . iconv('utf-8','cp949', $data[sl_option_value1] )       . "]]></option_value>
<option_name  num='2'><![CDATA[" . iconv('utf-8','cp949', $data[sl_option_name2]  )       . "]]></option_name>
<option_value num='2'><![CDATA[" . iconv('utf-8','cp949', $data[sl_option_value2] )       . "]]></option_value>
<opt_info></opt_info>";

// <image_url num='1'><![CDATA[http://img.buynjoy.com/images/X_550_mo_500.jpg]]></image_url>
// �̹��� ó�� �κ�.
//

if ( $data[sl_image_url] )
{
    $str .= "<image_url num='1'><![CDATA[" . $this->get_img_url( $data[sl_image_url] ) . "]]></image_url>\n";
}

for( $i = 2; $i <= 20; $i++ )
{
    $key = "sl_image_url" . $i;
    
    if ( $data[$key] )
    {
        $str .= "<image_url num='$i'><![CDATA[" . $this->get_img_url( $data[$key] ) . "]]></image_url>\n";    
    }       
}

$str .= "</product>
</productInfo> 
</openmarket>";
        
        fwrite($handle, $str );
        fclose( $handle);
        
        //echo $str;
        return $file_name;
    }
    
    function get_img_url( $_img )
    { 
        if ( substr( $_img,0,4)=="http" )    
            $_img_url = $data[$key];       
        else
            $_img_url = "http://" . $_SERVER[HTTP_HOST] . "/uploads/" . _DOMAIN_ . "/" . $_img;
          
        return $_img_url;
        
    }
}


?>
