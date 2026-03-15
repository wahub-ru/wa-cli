<?php

/*
 *
 * Easyseo plugin for Webasyst framework, created for Shopscript app.
 *
 * @name Easyseo
 * @author EasyIT LLC
 * @link https://easy-it.ru/
 * @copyright Copyright (c) 2017, EasyIT LLC
 * @version 1.0.0, 2024-10-01
 *
 */

/**
 * Override of shopProductsCollection to add custom functionality
 */
class shopEasyseoProductsCollection extends shopProductsCollection
{
  /**
   * Summary of getPriceRange
   * @param int $unit_id
   * @return float[]
   */
  public function getPriceRange($unit_id = null)
  {
    $skus_table_alias = null;

    if ($this->joins) {
      foreach ($this->joins as $join) {
        if ($join['table'] == 'shop_product_skus') {
          $skus_table_alias = $join['alias'];

          break; // got needed alias
        }
      }
    }

    if ($skus_table_alias === null) {
      $skus_table_alias = $this->addJoin('shop_product_skus', ':table.product_id = p.id', ':table.price <> 0');
    } else {
      $this->addWhere("`{$skus_table_alias}`.price <> 0");
    }

    $alias_currency = $this->addJoin('shop_currency', ':table.code = p.currency');
    $prev_group_by = $this->group_by;
    $this->groupBy("p.id");
    $sql = $this->getSQL();
    $sql = "SELECT MIN(`{$skus_table_alias}`.price * `{$alias_currency}`.rate) min, MAX(`{$skus_table_alias}`.price * `{$alias_currency}`.rate) max " . $sql;
    $data = $this->getModel()->query($sql)->fetch();
    $this->group_by = $prev_group_by;
    array_pop($this->joins);

    return array(
      'min' => (double) (isset($data['min']) ? $data['min'] : 0),
      'max' => (double) (isset($data['max']) ? $data['max'] : 0),
    );
  }
}