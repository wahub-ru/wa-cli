<?php

/**
 * ACTION class shopApiextensionPluginFrontendSmartFilterController
 *
 * @author Steemy, created by 21.09.2025
 */

class shopApiextensionPluginFrontendSmartFiltersController extends waJsonController
{
  private static $smartFilters = array();

  /**
   * Execute the controller action
   *
   * @throws waException
   */
  public function execute()
  {
    $categoryId = waRequest::post('category_id', 0, waRequest::TYPE_INT);
    $filters = waRequest::post('filters', array(), waRequest::TYPE_ARRAY);
    $requestData = waRequest::post('request_data', array(), waRequest::TYPE_ARRAY);

    if ($categoryId && !empty($filters)) {
      if (!empty($requestData) && !isset(self::$smartFilters[$categoryId])) {
        self::$smartFilters[$categoryId] = $this->getSmartFilters($categoryId, $filters, $requestData);
      }

      $this->response = [
        'status' => true,
        'filters' => ifempty(self::$smartFilters[$categoryId], array()),
      ];
    } else {
      $this->response = array(
        'status' => false,
        'error' => 'Не передан category_id или filters или request_data',
        'error_code' => 'invalid_parameters'
      );
    }
  }

  /**
   * Get filters for category using smart filter service
   *
   * @param int $categoryId
   * @param array $filters
   * @param array $requestData
   * @return array
   * @throws waException
   */
  private function getSmartFilters($categoryId, $filters, $requestData)
  {
    $smartFilter = new shopApiextensionPluginCategorySmartFilters();
    return $smartFilter->getSmartFilters($categoryId, $filters, $requestData);
  }
}
