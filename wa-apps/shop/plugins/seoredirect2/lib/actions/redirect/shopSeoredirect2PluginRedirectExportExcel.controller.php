<?php

class shopSeoredirect2PluginRedirectExportExcelController extends waController
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
        $this->writeRedirects($excel, $position_row);
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

    protected function writeRedirects(shopSeoredirect2Excel $excel, &$position_row)
    {
        $redirect_model = new shopSeoredirect2RedirectModel();
        $result_redirects = $redirect_model->select('*')->query();
        $columns = $this->getColumns();

        foreach ($result_redirects as $redirect) {
            foreach ($columns as $position_column => $column) {
                $excel->setCellValueByColumnAndRow($position_column, $position_row, $redirect[$column['code']]);
            }

            $position_row++;
        }
    }

    protected function readFile(shopSeoredirect2Excel $excel)
    {
        $date = date('Ymd');
        $filename = "redirects-{$date}.xlsx";
        $excel->readFile($filename);
    }

    protected function getColumns()
    {
        $excel_config = new shopSeoredirect2ExcelConfig();

        return $excel_config->getRedirectColumns();
    }
}
