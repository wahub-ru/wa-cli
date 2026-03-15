<?php

/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version
 * @copyright Serge Rodovnichenko, 2017
 * @license
 */
class shopTipsLogModel extends waLogModel
{
    /**
     * @param int $product_id
     * @param null|int $limit
     * @return array
     */
    public function getProductLog($product_id, $limit=null)
    {
        try {
            $events = $this->getLogs(array('app_id' => 'shop', 'action' => array('product_add', 'product_edit'), 'params' => $product_id), $limit);
        } catch (waException $e) {
            return array();
        }
        $actions = wa('shop')->getConfig()->getLogActions(true);
        foreach ($events as &$e) {
            $e['datetime_human'] = waDateTime::format('humandatetime', strtotime($e['datetime']));
            $e += array(
                'type'        => $this->actionType($e['action']),
                'action_name' => ifset($actions, $e['action'], 'name', $e['action'])
            );
        }
        unset ($e);

        return $events;
    }

    public function countByProduct($product_id)
    {
        return $this->countByField(array(
            'app_id' => 'shop',
            'action' => array('product_add', 'product_edit'),
            'params' => $product_id
        ));
    }

    /**
     * Блин. Нам нужен limit
     *
     * @param array $where
     * @return array
     * @throws waException
     */
    public function getLogs($where = array(), $limit = null)
    {
        $where_string = "l.action != 'login' AND l.action != 'logout'";
        if (!empty($where['max_id'])) {
            $where_string .= ' AND l.id < ' . (int)$where['max_id'];
            unset($where['max_id']);
        }
        if (!empty($where['min_id'])) {
            $where_string .= ' AND l.id > ' . (int)$where['min_id'];
            unset($where['min_id']);
        }
        $where = array_intersect_key($where, $this->getMetadata());
        if ($where) {
            $where_string .= ' AND (' . $this->getWhereByField($where) . ')';
        }

        $sql_limit = 50;
        if ($limit) {
            if (is_string($limit)) {
                $sql_limit = $limit;
            } elseif (is_array($limit)) {
                $limit = array_slice($limit, 0, 2);
                if (count($limit) < 2) {
                    array_unshift($limit, 0);
                }
                $sql_limit = implode(',', $limit);
            }
        }

        $sql = "SELECT l.*, c.name contact_name, c.photo contact_photo, c.firstname, c.lastname, c.middlename,
c.company, c.is_company, c.is_user, c.login
                FROM " . $this->table . " l
                LEFT JOIN wa_contact c ON l.contact_id = c.id
                WHERE " . $where_string . "
                ORDER BY l.id DESC
                LIMIT $sql_limit";
        return $this->query($sql)->fetchAll();
    }

    private function actionType($action)
    {
        if (strpos($action, 'del')) {
            return 4;
        }
        if (strpos($action, 'add')) {
            return 3;
        }
        return 1;
    }
}
