<?php

abstract class shopSeoredirect2Excel
{
    abstract public function setAutoSizeToColumn($position_column);

    abstract public function setCellValueByColumnAndRow($position_column, $position_row, $value);

    abstract public function getCellValueByColumnAndRow($position_column, $position_row);

    abstract public function getCountColumns();

    abstract public function getCountRows();

    abstract public function readFile($filename);
}