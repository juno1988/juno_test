<?

/** PHPExcel */
require_once 'Classes/PHPExcel.php';

class class_file2
{
    function write_excel_file( $arr, $fn )
    {
        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();
        
        foreach ($arr as $row => $row_data) {
            foreach ($row_data as $col => $cell_data) {
                if( $cell_data[type] == "string" )
                    $sheet->getCellByColumnAndRow($col, $row + 1)->setValueExplicit($cell_data[data], PHPExcel_Cell_DataType::TYPE_STRING);
                else if( $cell_data[type] == "number" )
                {
                    $cell = $sheet->getCellByColumnAndRow($col, $row + 1);
                    $cell->setValueExplicit($cell_data[data], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat()->setFormatCode('#,##0;[Red][<0]-#,##0');
                }
            }
        }
        
        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $writer->save(_upload_dir . $fn);
    }
}
?>
