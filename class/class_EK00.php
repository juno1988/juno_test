<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_file.php";
require_once "class_supply.php";
require_once "class_auto.php";
require_once "class_E900.php";
require_once "ExcelReader/reader.php";
require_once "class_stock.php";
require_once "ExcelParserPro/excelparser.php";

class class_EK00 extends class_top
{ 
    function EK00()
    {
        global $template;
        global $connect;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////
    function upload()
    {
        global $connect, $admin_file, $_file, $seq;

        $obj = new class_file();
        $arr = $obj->upload();

        $this->show_wait();

        $obj = new class_E900();
        
        $err_result = "";
        $err_cnt = 0;
        $err_max = 100;
        
        $i = 0;
        $n = 0;
        $row_cnt = count( $arr );
        foreach ( $arr as $row )
        {
            $i++;
            if ( $i <= 1 ) continue;  // 헤더
            if ( $i == $row_cnt ) continue;  // 마지막행

            // 필수 입력 항목이 없으면 넘어간다.
            if( !$row[0] )
            {
                if( $err_cnt++ < $err_max )
                    $err_result .= " $i 행 : 관리번호를 입력하세요 <br> ";
                continue;
            }else if( !$row[1] ){
                if( $err_cnt++ < $err_max )
                    $err_result .= " $i 행 : 배송비를 입력하세요 <br> ";
                continue;
            }
                
            // 관리번호의 주문 정보 가져온다.
            $query = "select * from orders where seq=$row[0]";    
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc( $result );
            if( !$data )
            {
                if( $err_cnt++ < $err_max )
                    $err_result .= " $i 행 : 잘못된 관리번호 입니다. <br> ";
                continue;
            }
            
            // 상태 조회
            $sts = $data[status];
            if( $sts == 0 )
            {
                if( $err_cnt++ < $err_max )
                    $err_result .= " $i 행 : 발주상태의 주문은 배송비를 변경할 수 없습니다. <br> ";
                continue;
            }
            else if( $sts == 8 )
            {
                if( $err_cnt++ < $err_max )
                    $err_result .= " $i 행 : 이미 배송된 주문입니다. <br> ";
                continue;
            }
            
            // 배송비 변경 query
            $query = "update orders set trans_fee=$row[1] ";

            // 합포면 pack, 단일주문이면 seq
            if( $data[pack] > 0 )
                $query .= " where pack = $data[pack]";
            else
                $query .= " where seq = $data[seq]";
            
            if( !mysql_query($query, $connect) )
            {   
                if( $err_cnt++ < $err_max )
                    $err_result .= " $i 행 : 배송비 변경에 실패했습니다. 고객센터로 문의바랍니다. <br> ";
                continue;
            }

            // cs 로그
            $seq = $data[seq];
            $content = "배송비 일괄변경 ($data[trans_fee] -> $row[1])";
            $obj->csinsert3( $data[pack], 3, $content);

            $n++;
        }
        
        $this->hide_wait();
        $this->jsAlert("$n 개 입력 완료 되었습니다.");
    
        $err_result = $this->base64_encode_url($err_result);
        $this->redirect("?template=EK00&err_cnt=$err_cnt&err_result=$err_result");
    }
}
?>
