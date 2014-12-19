<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '../PHPExcel/PHPExcel-1.6.7/');

require_once 'PHPExcel.php';
require_once 'PHPExcel/Cell/AdvancedValueBinder.php';
require_once 'PHPExcel/IOFactory.php';

class class_excel
{
    function read_excel($file_name)
    {
        $data = array();
        
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setReadDataOnly(true);
        
        $objPHPExcel = $objReader->load($file_name);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        
        foreach ($objWorksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $data_row = array();
            foreach ($cellIterator as $cell) {
                $data_row[] = $cell->getValue();
            }
            $data[] = $data_row;
        }
        print_r( $data );
        echo json_encode($data);
    }
}
?>
