<?
require_once "class_top.php";
require_once "class_file.php";
require_once "Classes/PHPExcel.php";

class class_EP00 extends class_top
{
    //////////////////////////////////////
    // VIP 리스트
    function EP00()
    {
        global $connect, $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
 
    //////////////////////////////////////
    // VIP 리스트 - 파일데이터
    function save_file()
    {
        global $connect;

        $file_data = array ();

        $query = "select * from customer_list where cust_type='vip' order by tel";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            while( $data = mysql_fetch_assoc($result) )
            {
                $file_data[] = array ( 
                    "tel" => $data[tel],
                    "add" => $data[address],
                    "name" => $data[name]
                );
            }
        }
        else
            $file_data[] = array();

        $this->make_file( $file_data, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    //////////////////////////////////////
    // VIP 리스트 - 파일만들기
    function make_file( $arr_datas, $fn )
    {
        global $connect;

        $filename = _upload_dir . $fn;

        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();

        $sheet->getCellByColumnAndRow(0, 1)->setValueExplicit("전화번호", PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow(1, 1)->setValueExplicit("주소", PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow(2, 1)->setValueExplicit("이름", PHPExcel_Cell_DataType::TYPE_STRING);
         
        $sheet->getStyle('A1:C1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle('A1:C1')->getFill()->getStartColor()->setARGB('FFCCFFCC');
        $sheet->getStyle('A1:C1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        foreach ($arr_datas as $row => $row_data) {
            $row = $row + 2;
            $col = 0;
            foreach ($row_data as $key => $value) {
                $sheet->getCellByColumnAndRow($col, $row)->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
                $col++;
            }
        }
        
        // 폭
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(20);

        // border
        $styleArray = array(
        	'font' => array(
        		'name' => '굴림체',
        		'size' => 9,
        	),
        	'borders' => array(
        		'allborders' => array(
        			'style' => PHPExcel_Style_Border::BORDER_THIN ,
        			'color' => array('argb' => 'FF000000'),
        		),
        	),
        );
        $sheet->getStyle('A1:C'.$row)->applyFromArray($styleArray);

        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $writer->save($filename);
    }

    //////////////////////////////////////
    // VIP 리스트 - 파일다운로드
    function download()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "vip_list.xls");
    }    

    ///////////////////////////////////
    // VIP 리스트 등록
    function upload()
    {
        global $connect, $_file;
        
        $obj = new class_file();
        $arr = $obj->upload();

        $this->show_wait();

        // 기존 목록 삭제
        $query = "delete from customer_list where cust_type='vip'";
        mysql_query($query, $connect);
        
        $i = 0;
        $row_cnt = count( $arr );
        foreach ( $arr as $row )
        {
            $i++;
            if ( $i <= 1 ) continue;  // 헤더

            $tel = trim($row[0]);
            $address = trim($row[1]);
            $name = trim($row[2]);

            if( !$tel && !$address && !$name )  continue;
            
            // vip 전화번호 입력
            $query = "insert customer_list set cust_type='vip', tel='$tel', address='$address' , name='$name' ";
            mysql_query($query, $connect);
        }
       
        $this->hide_wait();
        $this->jsAlert("작업 완료");
    
        $this->redirect("?template=EP00");
        
    }


    //////////////////////////////////////
    // 블랙 리스트
    function EP10()
    {
        global $connect, $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
 
    //////////////////////////////////////
    // 블랙 리스트 - 파일데이터
    function save_file2()
    {
        global $connect;

        $file_data = array();

        $query = "select * from customer_list where cust_type='black' order by tel";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            while( $data = mysql_fetch_assoc($result) )
            {
                $file_data[] = array ( 
                    "tel" => $data[tel],
                    "add" => $data[address],
                    "name" => $data[name]
                );
            }
        }
        else
            $file_data[] = array();

        $this->make_file( $file_data, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    //////////////////////////////////////
    // 블랙 리스트 - 파일다운로드
    function download2()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "black_list.xls");
    }    

    ///////////////////////////////////
    // 블랙 리스트 등록
    function upload2()
    {
        global $connect, $_file;
        
        $obj = new class_file();
        $arr = $obj->upload();

        $this->show_wait();

        // 기존 목록 삭제
        $query = "delete from customer_list where cust_type='black'";
        mysql_query($query, $connect);
        
        $i = 0;
        $row_cnt = count( $arr );
        foreach ( $arr as $row )
        {
            $i++;
            if ( $i <= 1 ) continue;  // 헤더

            $tel = trim($row[0]);
            $address = trim($row[1]);
            $name= trim($row[2]);

            if( !$tel && !$address && !$name)  continue;

            // vip 전화번호 입력
            $query = "insert customer_list set cust_type='black', tel='$tel', address='$address' , name='$name' ";
            mysql_query($query, $connect);
        }

        $this->hide_wait();
        $this->jsAlert("작업 완료");

        $this->redirect("?template=EP10");
    }

}
?>
