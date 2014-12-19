<?
require_once "class_top.php";
require_once "class_B.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_file.php";
require_once "class_combo.php";
require_once "class_category.php";
require_once "class_stock.php";
require_once "class_product.php";
require_once "class_ui.php";
require_once "class_3pl.php";
require_once "class_CL00.php";
require_once "class_auto.php";
require_once "class_supply.php";

//////////////////////////////////////////////
// 상품 변경 로그
class class_CP00 extends class_top
{
    function CP00()
    {
        global $template, $pick_soldout_date, $start_date, $end_date, $packed, $date_type,$e_stock, $stock_manage, $link_url_list;
        
        $par_arr = array("template","action","supply_code","string_type","string","start_date","end_date","page");
        $link_url_list = $this->build_link_par($par_arr);     
        
        if ( $_REQUEST["page"] )
            $result = $this->get_list( &$total_rows, $page );
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function get_list( &$total_rows, $page )
    {
        global $connect;
        
        $query = "select * from ";
        
    }

    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기
    function save_file()
    {
        global $connect, $supply_code, $string_type, $string, $e_sale, $date_type, $start_date, $end_date, $include_option;

        // 엑셀 헤더
        $product_data = array();
        $product_data[] = array(
            "is_represent"     => "대표상품",
            "org_id"           => "대표상품코드",
            "product_id"       => "상품코드",
            "name"             => "상품명",
            "supply_code"      => "공급처코드",
            "supply_name"      => "공급처",
            "brand"            => "공급처 상품명",
            "supply_options"   => "공급처 옵션",
            "origin"           => "원산지",
            "trans_code"       => "택배비 코드",
            "weight"           => "중량",
            "org_price"        => "원가",
            "supply_price"     => "공급가",
            "shop_price"       => "판매가",
            "market_price"     => "시중가",
            "options"          => "옵션",
            "stock_manage"     => "옵션관리",
            "barcode"          => "바코드",
            "img_500"          => "대표 이미지",
            "img_desc1"        => "설명 이미지1",
            "img_desc2"        => "설명 이미지2",
            "img_desc3"        => "설명 이미지3",
            "img_desc4"        => "설명 이미지4",
            "img_desc5"        => "설명 이미지5",
            "img_desc6"        => "비고 이미지",
            "product_desc"     => "상품설명",
            "enable_sale"      => "판매상태",
            "enable_stock"     => "재고관리",
            "reg_date"         => "등록일",
            "last_update_date" => "갱신일",
            "stock_alarm1"     => "재고경고수량",
            "stock_alarm2"     => "재고위험수량",
            "del"              => "삭제",
        );

        $result = class_C::get_list( &$cnt_all, $page, 1 );
        while( $data = mysql_fetch_assoc($result) )
        {
            $product_desc = stripslashes(htmlspecialchars(htmlspecialchars_decode($data[product_desc])));
            $info = array(
                "is_represent"     => $data[is_represent    ] ? $data[is_represent    ] : "",
                "org_id"           => $data[org_id          ] ? $data[org_id          ] : "",
                "product_id"       => $data[product_id      ] ? $data[product_id      ] : "",
                "name"             => $data[name            ] ? $data[name            ] : "",
                "supply_code"      => $data[supply_code     ] ? $data[supply_code     ] : "",
                "supply_name"      => class_supply::get_name( $data[supply_code] ),
                "brand"            => $data[brand           ] ? $data[brand           ] : "",
                "supply_options"   => $data[supply_options  ] ? $data[supply_options  ] : "",
                "origin"           => $data[origin          ] ? $data[origin          ] : "",
                "trans_code"       => $data[trans_code      ] ? $data[trans_code      ] : "",
                "weight"           => $data[weight          ] ? $data[weight          ] : "",
                "org_price"        => $data[org_price       ] ? $data[org_price       ] : "",
                "supply_price"     => $data[supply_price    ] ? $data[supply_price    ] : "",
                "shop_price"       => $data[shop_price      ] ? $data[shop_price      ] : "",
                "market_price"     => $data[market_price    ] ? $data[market_price    ] : "",
                "options"          => $data[options         ] ? $data[options         ] : "",
                "stock_manage"     => $data[stock_manage    ] ? $data[stock_manage    ] : "0",
                "barcode"          => $data[barcode         ] ? $data[barcode         ] : "",
                "img_500"          => $data[img_500         ] ? $data[img_500         ] : "",
                "img_desc1"        => $data[img_desc1       ] ? $data[img_desc1       ] : "",
                "img_desc2"        => $data[img_desc2       ] ? $data[img_desc2       ] : "",
                "img_desc3"        => $data[img_desc3       ] ? $data[img_desc3       ] : "",
                "img_desc4"        => $data[img_desc4       ] ? $data[img_desc4       ] : "",
                "img_desc5"        => $data[img_desc5       ] ? $data[img_desc5       ] : "",
                "img_desc6"        => $data[img_desc6       ] ? $data[img_desc6       ] : "",
                "product_desc"     => $product_desc ? $product_desc : "",
                "enable_sale"      => $data[enable_sale     ] ? $data[enable_sale     ] : "0",
                "enable_stock"     => $data[enable_stock    ] ? $data[enable_stock    ] : "0",
                "reg_date"         => $data[reg_date] . " " . $data[reg_time],
                "last_update_date" => $data[last_update_date] ? $data[last_update_date] : "",
                "stock_alarm1"     => $data[stock_alarm1    ] ? $data[stock_alarm1    ] : "",
                "stock_alarm2"     => $data[stock_alarm2    ] ? $data[stock_alarm2    ] : "",
                "del"              => ""
            );
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
                        $info[supply_options] = $data_opt[supply_options];
                        $info[options]        = $data_opt[options];
                        $info[barcode]        = $data_opt[barcode];
                        $info[enable_sale]    = $data_opt[enable_sale];
                        $info[img_500]        = "";
                        $info[img_desc1]      = "";
                        $info[img_desc2]      = "";
                        $info[img_desc3]      = "";
                        $info[img_desc4]      = "";
                        $info[img_desc5]      = "";
                        $info[img_desc6]      = "";
                        $info[product_desc]   = "";
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
        $this->make_file( $product_data, "download.xls" );
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
    mso-number-format:\"\#\,\#\#0_\)\;\[Red\]\\\(\#\,\#\#0\\\)\";
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
                    if( $key == 'org_price' || $key == 'supply_price' || $key == 'shop_price' || $key == 'weight' || $key == 'market_price' || 
                        $key == 'is_represent' || $key == 'stock_manage' || $key == 'enable_sale' || $key == 'enable_stock' )
                        $buffer .= "<td class=num_item>" . $value . "</td>";
                    else if( $key == 'options' || $key == 'product_desc' )
                        $buffer .= "<td class=mul_item>" . str_replace("\n", "<br>", $value) . "</td>";
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

}
?>
