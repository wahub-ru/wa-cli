<?php

class shopSeoredirect2ExcelFactory
{
    /**
     * @return shopSeoredirect2Excel
     */
    public function create()
    {
        if ($this->useSpreadsheet()) {
            return shopSeoredirect2ExcelPHPSpreadsheet::create();
        } else {
            return shopSeoredirect2ExcelPHPExcel::create();
        }
    }

    /**
     * @return shopSeoredirect2Excel
     */
    public function fromFile($path)
    {
        if ($this->useSpreadsheet()) {
            return shopSeoredirect2ExcelPHPSpreadsheet::fromFile($path);
        } else {
            return shopSeoredirect2ExcelPHPExcel::fromFile($path);
        }
    }

    private function useSpreadsheet()
    {
        return version_compare(phpversion(), '7.4', '>=');
    }
}