<?php

require_once(__DIR__ . '/../vendors/autoload.php');
PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip);

class shopSeoredirect2ExcelPHPExcel extends shopSeoredirect2Excel
{
    private $excel;

    public static function fromFile($path)
    {
        $reader = PHPExcel_IOFactory::createReaderForFile($path);

        return new shopSeoredirect2ExcelPHPExcel($reader->load($path));
    }

    public static function create()
    {
        return new shopSeoredirect2ExcelPHPExcel(new PHPExcel());
    }

    private function __construct(PHPExcel $excel)
    {
        $this->excel = $excel;
    }

    public function setAutoSizeToColumn($position_column)
    {
        $this->excel->getActiveSheet()->getColumnDimensionByColumn($position_column)->setAutoSize(true);
    }

    public function setCellValueByColumnAndRow($position_column, $position_row, $value)
    {
        $this->excel->getActiveSheet()->setCellValueByColumnAndRow($position_column, $position_row, $value);
    }

    public function getCellValueByColumnAndRow($position_column, $position_row)
    {
        return $this->excel->getActiveSheet()->getCellByColumnAndRow($position_column, $position_row)->getValue();
    }

    public function getCountColumns()
    {
        return PHPExcel_Cell::columnIndexFromString($this->excel->getActiveSheet()->getHighestDataColumn());
    }

    public function getCountRows()
    {
        return $this->excel->getActiveSheet()->getHighestDataRow();
    }

    public function readFile($filename)
    {
        $writer = PHPExcel_IOFactory::createWriter($this->excel, "Excel2007");

        header('Content-type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        try {
            $writer->save('php://output');
        } catch (PHPExcel_Writer_Exception $e) {
            $temp_file_path = sys_get_temp_dir() . "/" . rand(0, getrandmax()) . rand(0, getrandmax()) . ".tmp";
            $writer->save($temp_file_path);
            readfile($temp_file_path);
            unlink($temp_file_path);
        }
    }
}