<?
require_once "class_top.php";
require_once "class_file.php";
require_once "Classes/PHPExcel.php";

class class_IU00 extends class_top
{
    //////////////////////////////////////
    // 로케이션 관리 
    function IU00()
    {
        global $connect, $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
 
    //////////////////////////////////////
    // 로케이션 리스트 - 파일데이터
    function save_file()
    {
        global $connect;

        $file_data = array();

        $query = "select * from stock_location order by location";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $file_data[] = array ( 
                //"seq" => $data[seq],
                "location" => $data[location]
            );
        }

        $this->make_file( $file_data, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    //////////////////////////////////////
    // 로케이션 리스트 - 파일만들기
    function make_file( $arr_datas, $fn )
    {
        global $connect;

        $filename = _upload_dir . $fn;

        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();

        //$sheet->getCellByColumnAndRow(0, 1)->setValueExplicit("번호", PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow(0, 1)->setValueExplicit("로케이션", PHPExcel_Cell_DataType::TYPE_STRING);
         
        //$sheet->getStyle('A1:B1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle('A1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('FFCCFFCC');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $row = 1;
        foreach ($arr_datas as $row_data) {
            $row++;
            $col = 0;
            foreach ($row_data as $key => $value) {
                $sheet->getCellByColumnAndRow($col, $row)->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
                $col++;
            }
        }
        
        // 폭
        $sheet->getColumnDimension('A')->setWidth(20);

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
        //$sheet->getStyle('A1:B'.$row)->applyFromArray($styleArray);
        $sheet->getStyle('A1:A'.$row)->applyFromArray($styleArray);

        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $writer->save($filename);
    }

    //////////////////////////////////////
    // 로케이션 리스트 - 파일다운로드
    function download()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "location_list.xls");
    }    

    function upload_add()	//	추가 업로드
    {
        global $connect, $_file;
        
        $obj = new class_file();
        $arr = $obj->upload();
        
        $this->show_wait();
        
        // 기존 목록 검색
        $query1 = "select * from stock_location";
        $result1 = mysql_query($query1, $connect);
 		
		$first_row = true;

		foreach( $arr as $row_new )	//	업로드한 로케이션 하나씩 비교
		{
		    // 첫행 헤더
		    if( $first_row )
		    {
		        $first_row = false;
		        continue;
		    }
		    
           	$location = trim($row_new[0]);
           	if( !$location )  continue;     //  로케이션 내용이 없으면 다음칸으로 이동
            
            if( !mysql_num_rows($result1) ) //  기존에 로케이션 목록이 아예 없을때
		    {
		        $query2 = "insert into stock_location (location) values ('$location')";
                mysql_query($query2, $connect);
		    }
            else    //  기존에 로케이션 목록이 있을때
            {    
                $query_search = "select * from stock_location where location = '$location'";
                $result = mysql_query($query_search, $connect);
        	    if( !mysql_num_rows($result) )
        	    {
                	$query2 = "insert into stock_location (location) values ('$location')";
               	    mysql_query($query2, $connect);
           	    }				
		    }
		}

        $this->hide_wait();
        $this->jsAlert("작업 완료");

        $this->redirect("?template=IU00");
    }

 function upload_del()		//	삭제 업로드
    {
        global $connect, $_file;

        $obj = new class_file();
        $arr = $obj->upload();

        $this->show_wait();

        foreach( $arr as $row_new ) //  새로운 목록 반복
        {
           	$location = trim($row_new[0]);
			$query = "delete from stock_location where location = '$location'";
			mysql_query($query, $connect);
        }

        $this->hide_wait();
        $this->jsAlert("작업 완료");

        $this->redirect("?template=IU00");
    }

	function search()
	{
		global $connect;
        
		$arr_location = array();
		$query_str = $_REQUEST["query_str"];

		$query = "select location from stock_location";
		
		if ( $query_str > "")
		{
			$query .= " where location like '%".$query_str."%'";
		}	
		$query .= " order by location";
		$result = mysql_query($query, $connect);	

		$text = "";
	
	    $i = 0;
		while ( $data = mysql_fetch_assoc($result) )
		{
			$text .= "<tr class=".$data["location"]." bgcolor=ffffff>";
			$text .= "<td align=center>".$data["location"]."</td>";
			$text .= "<td align=center width=25>";
            $text .= "<input type=button class=del_loc value=X id=".$data["location"]." />"; 
			$text .= "</td>";
			$text .= "</tr>";
		}
		echo $text;
		return $text;
	}
	
	function add_location()
	{
		global $connect, $query_add;
		
		$query_select = "select * from stock_location";
		$result = mysql_query( $query_select, $connect );
		$total_records = mysql_num_rows( $result );
		$count = 0;
		
		while( $row = mysql_fetch_assoc( $result ) )
		{
			$count++;
			
			if( !$query_add )
			{
				$val['error'] = 2;
    	        echo json_encode( $val );
				break;
			}
			else if ( $row["location"] == $query_add )
			{
				$val['error'] = 1;
    	        echo json_encode( $val );
				break;
			}
			else if( $count >= $total_records )
			{
           	 	$query_insert = "insert into stock_location (location) values ('$query_add')";
				mysql_query($query_insert, $connect);
		        $val['error'] = 0;
    	        echo json_encode( $val );
			}
		}
	}

	function delete_location()
	{
		global $connect, $query_delete;
	    
		$query = "delete from stock_location where location = '$query_delete'";
		mysql_query($query, $connect);
	
        $val['error'] = 0;
    	echo json_encode( $val );
	}
}
?>
