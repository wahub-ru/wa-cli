<?php

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;

require_once(__DIR__ . '/../vendors/autoload.php');

class shopSeoredirect2ExcelPHPSpreadsheet extends shopSeoredirect2Excel
{
    private $spreadsheet;

    public static function fromFile($path)
    {
        $reader = new ReaderXlsx();

        return new shopSeoredirect2ExcelPHPSpreadsheet($reader->load($path));
    }

    public static function create()
    {
        return new shopSeoredirect2ExcelPHPSpreadsheet(new Spreadsheet());
    }

    private function __construct(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
    }

    public function setAutoSizeToColumn($position_column)
    {
        $this->spreadsheet->getActiveSheet()->getColumnDimensionByColumn($position_column + 1)->setAutoSize(true);
    }

    public function setCellValueByColumnAndRow($position_column, $position_row, $value)
    {
        $this->spreadsheet->getActiveSheet()->setCellValue([$position_column + 1, $position_row], $value);
    }

    public function getCellValueByColumnAndRow($position_column, $position_row)
    {
        return $this->spreadsheet->getActiveSheet()->getCell([$position_column + 1, $position_row])->getValue();
    }

    public function getCountColumns()
    {
        return Coordinate::columnIndexFromString($this->spreadsheet->getActiveSheet()->getHighestDataColumn());
    }

    public function getCountRows()
    {
        return $this->spreadsheet->getActiveSheet()->getHighestDataRow();
    }

    public function readFile($filename)
    {
        $writer = new WriterXlsx($this->spreadsheet);

        header('Content-type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        try {
            $writer->save('php://output');
        } catch (WriterException $e) {
            $temp_file_path = sys_get_temp_dir() . "/" . rand(0, getrandmax()) . rand(0, getrandmax()) . ".tmp";
            $writer->save($temp_file_path);
            readfile($temp_file_path);
            unlink($temp_file_path);
        }
    }
}