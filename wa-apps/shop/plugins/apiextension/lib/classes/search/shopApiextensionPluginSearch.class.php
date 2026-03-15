<?php

/**
 * Helper class shopApiextensionPluginSearch
 *
 * @author Steemy, created by 05.10.2024
 */

class shopApiextensionPluginSearch
{
    /**
     * Фильтр для поиска
     * @param string $featuresIds
     * @return array|mixed
     * @throws waException
     */
    public function getSearchFilters($featuresIds)
    {
        $tag = waRequest::param('tag');

        if ($tag) {
            $query = 'tag/'.$tag;
        } else {
            $query = waRequest::get('query');
            $query = 'search/query='.str_replace('&', '\&', $query);
        }

        try {
            $collection = new shopProductsCollection($query);
        } catch (waDbException $dbe) {
            return array();
        }

        $filters = array();

        // filters
        if ($featuresIds) {
            $filter_ids = [];
            $feature_model = new shopFeatureModel();
            $filter_codes = explode(',', $featuresIds);

            if (array_search('price', $filter_codes) !== false) {
                $filter_ids = ['price'];
                unset($filter_codes[array_search('price', $filter_codes)]);
            }

            $filter_by_codes = $feature_model->getByCode($filter_codes);
            foreach ($filter_by_codes as $f) {
                $filter_ids[] = $f['id'];
            }

            $features = $feature_model->getById(array_filter($filter_ids, 'is_numeric'));
            if ($features) {
                $features = $feature_model->getValues($features);
            }
            $category_value_ids = $collection->getFeatureValueIds(false);

            foreach ($filter_ids as $fid) {
                if (!isset($filters['price']) && ($fid == 'price' || $fid == 'base_price')) {
                    $range = $collection->getPriceRange();
                    if ($range['min'] != $range['max']) {
                        $filters['price'] = array(
                            'min' => shop_currency($range['min'], null, null, false),
                            'max' => shop_currency($range['max'], null, null, false),
                        );
                        if (($filters['price']['max'] - $filters['price']['min']) <= 1) {
                            $filters['price']['max'] +=2;
                        }
                    }
                }
                elseif (isset($features[$fid]) && isset($category_value_ids[$fid])) {
                    //set feature data
                    $filters[$fid] = $features[$fid];

                    $min = $max = $unit = null;

                    foreach ($filters[$fid]['values'] as $v_id => $v) {

                        //remove unused
                        if (!in_array($v_id, $category_value_ids[$fid])) {
                            unset($filters[$fid]['values'][$v_id]);
                        } else {
                            if ($v instanceof shopRangeValue) {
                                $begin = $this->getFeatureValue($v->begin);
                                if (is_numeric($begin) && ($min === null || (float)$begin < (float)$min)) {
                                    $min = $begin;
                                }
                                $end = $this->getFeatureValue($v->end);
                                if (is_numeric($end) && ($max === null || (float)$end > (float)$max)) {
                                    $max = $end;
                                    if ($v->end instanceof shopDimensionValue) {
                                        $unit = $v->end->unit;
                                    }
                                }
                            } else {
                                $tmp_v = $this->getFeatureValue($v);
                                if ($min === null || $tmp_v < $min) {
                                    $min = $tmp_v;
                                }
                                if ($max === null || $tmp_v > $max) {
                                    $max = $tmp_v;
                                    if ($v instanceof shopDimensionValue) {
                                        $unit = $v->unit;
                                    }
                                }
                            }
                        }
                    }
                    if (!$filters[$fid]['selectable'] && ($filters[$fid]['type'] == 'double' ||
                            substr($filters[$fid]['type'], 0, 6) == 'range.' ||
                            substr($filters[$fid]['type'], 0, 10) == 'dimension.')
                    ) {
                        if ($min == $max) {
                            unset($filters[$fid]);
                        } else {
                            $type = preg_replace('/^[^\.]*\./', '', $filters[$fid]['type']);
                            if ($type == 'date') {
                                $min = shopDateValue::timestampToDate($min);
                                $max = shopDateValue::timestampToDate($max);
                            } elseif ($type != 'double') {
                                $filters[$fid]['base_unit'] = shopDimension::getBaseUnit($type);
                                $filters[$fid]['unit'] = shopDimension::getUnit($type, $unit);
                                if ($filters[$fid]['base_unit']['value'] != $filters[$fid]['unit']['value']) {
                                    $dimension = shopDimension::getInstance();
                                    $min = $dimension->convert($min, $type, $filters[$fid]['unit']['value']);
                                    $max = $dimension->convert($max, $type, $filters[$fid]['unit']['value']);
                                }
                            }
                            $filters[$fid]['min'] = $min;
                            $filters[$fid]['max'] = $max;
                        }
                    }
                }
            }
        }

        return $filters;
    }

    /**
     * @param shopDimensionValue|double $v
     * @return double
     */
    protected function getFeatureValue($v)
    {
        if ($v instanceof shopDimensionValue) {
            return $v->value_base_unit;
        } elseif ($v instanceof shopDateValue) {
            return $v->timestamp;
        }
        if (is_object($v)) {
            return $v->value;
        }
        return $v;
    }
}