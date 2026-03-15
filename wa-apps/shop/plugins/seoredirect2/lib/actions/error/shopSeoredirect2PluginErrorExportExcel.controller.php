<?php

class shopSeoredirect2PluginErrorExportExcelController extends waController
{
    private $excel_factory;

    public function __construct()
    {
        $this->excel_factory = new shopSeoredirect2ExcelFactory();
    }


    public function execute()
    {
        $excel = $this->excel_factory->create();
        $position_row = 1;
        $this->writeHeader($excel, $position_row);
        $this->writeErrors($excel, $position_row);
        $this->readFile($excel);
    }

    protected function writeHeader(shopSeoredirect2Excel $excel, &$position_row)
    {
        foreach ($this->getColumns() as $position_column => $column) {
            $excel->setAutoSizeToColumn($position_column);
            $excel->setCellValueByColumnAndRow($position_column, $position_row, $column['title']);
        }

        $position_row++;
    }

    protected function writeErrors(shopSeoredirect2Excel $excel, &$position_row)
    {
        $error_storage = new shopSeoredirect2ErrorStorage();
        $columns = $this->getColumns();

        foreach ($error_storage->getAllIterable() as $i => $error) {
            foreach ($columns as $position_column => $column) {
                $excel->setCellValueByColumnAndRow($position_column, $position_row, $error[$column['code']]);
            }

            $position_row++;
        }
    }

    protected function readFile(shopSeoredirect2Excel $excel)
    {
        $date = date('Ymd');
        $filename = "404-error-{$date}.xlsx";
        $excel->readFile($filename);
    }

    protected function getColumns()
    {
        $excel_config = new shopSeoredirect2ExcelConfig();

        return $excel_config->getErrorColumns();
    }
}
