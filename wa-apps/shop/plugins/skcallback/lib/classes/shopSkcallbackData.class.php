<?php

class shopSkcallbackData{

    public function getData($page = 1, $limit = 30, $filters = array()){

        $page = (int)$page;
        if(!$page){
            $page = 1;
        }

        $limit = (int)$limit;
        if(!$limit){
            $limit = 30;
        }

        $offset = ($page - 1) * $limit;

        $whereSql = $this->getFiltersSql($filters);

        $data["count"] = $this->getCounters($whereSql);

        $data["requests"] = $this->getRequests($offset, $limit, $whereSql);

        $requests_ids = array_keys($data["requests"]);

        $values = $this->getValues($requests_ids);

        $carts = $this->getCarts($requests_ids);

        $data["requests"] = $this->mergeRequestsData($data["requests"], $values, $carts);

        $data["controls"] = $this->getControls();

        $data["statuses"] = $this->getStatuses();

        $data["is_add"] = false;
        if((count($data["requests"]) + $offset) < $data["count"]){
            $data["is_add"] = true;
        }

        return $data;

    }

    public function getFiltersSql($filters){

        $where = "WHERE 1 ";

        $model = new waModel();

        if($filters["date_from"] && $filters["date_to"]){
            unset($filters["period"]);
            $dtFrom = explode(".", $filters["date_from"]);
            $dtTo = explode(".", $filters["date_to"]);
            $filters["date_from"] = "{$dtFrom[2]}.{$dtFrom[1]}.{$dtFrom[0]} 00:00:00";
            $filters["date_to"] = "{$dtTo[2]}.{$dtTo[1]}.{$dtTo[0]} 23:59:59";
        }else{
            $time = time();
            switch($filters["period"]){
                case "today":
                    $filters["date_from"] = date("Y-m-d 00:00:00", $time);
                    $filters["date_to"] = date("Y-m-d 23:59:59", $time);
                    break;
                case "yesterday":
                    $t = $time - 86400;
                    $filters["date_from"] = date("Y-m-d 00:00:00", $t);
                    $filters["date_to"] = date("Y-m-d 23:59:59", $t);
                    break;
                case "week":
                    $t = $time - 604800;
                    $filters["date_from"] = date("Y-m-d H:i:s", $t);
                    $filters["date_to"] = date("Y-m-d 23:59:59", $time);
                    break;
                default:
                    unset($filters["date_from"]);
                    unset($filters["date_to"]);
                    break;
            }
        }

        if(!empty($filters) && is_array($filters)){
            foreach($filters as $name => $filter){
                if(empty($filter)){
                    continue;
                }
                $filter = $model->escape($filter);
                switch($name){
                    case "date_from":
                        $where .= " AND t1.date >= '{$filter}'";
                        break;
                    case "date_to":
                        $where .= " AND t1.date <= '{$filter}'";
                        break;
                    case "status":
                        $filter = (int)$filter;
                        $where .= " AND t1.status_id = {$filter}";
                        break;
                }
            }
        }

        return $where;

    }

    public function getCounters($where){

        $requestsModel = new shopSkcallbackRequestsModel();

        $result = $requestsModel->query("SELECT COUNT(*) as cnt FROM shop_skcallback_requests t1 {$where}")->fetchAssoc();

        return $result["cnt"];

    }


    public function getRequests($offset, $limit, $where){

        $requestsModel = new shopSkcallbackRequestsModel();

        $requests = $requestsModel->query("SELECT t1.* FROM shop_skcallback_requests t1 {$where} ORDER BY t1.id DESC LIMIT {$offset}, {$limit}")->fetchAll();

        $result = array();

        foreach($requests as $item){
            $result[$item["id"]] = $item;
        }

        return $result;

    }


    public function getValues($ids){

        if(empty($ids) || !is_array($ids)){
            return array();
        }

        $ids_sql = implode(",", $ids);

        $valuesModel = new shopSkcallbackValuesModel();

        $values = $valuesModel->query("
              SELECT t1.*, t2.title as control_title, t3.name as type_name
              FROM shop_skcallback_values t1
              JOIN shop_skcallback_controls t2 ON t1.control_id = t2.id
              JOIN shop_skcallback_controls_type t3 ON t2.type_id = t3.id
              WHERE t1.request_id IN({$ids_sql})
              ORDER BY t2.sort ASC")->fetchAll();

        $result = array();

        foreach($values as $value){
            $result[$value['request_id']][$value['control_id']] = $value;
        }

        return $result;

    }

    public function getCarts($ids){

        if(empty($ids) || !is_array($ids)){
            return array();
        }

        $ids_sql = implode(",", $ids);

        $cartModel = new shopSkcallbackCartModel();

        $carts = $cartModel->query("SELECT * FROM shop_skcallback_cart WHERE request_id IN ({$ids_sql})")->fetchAll();

        $result = array();

        foreach($carts as $cart){
            $result[$cart["request_id"]][] = $cart;
        }

        return $result;

    }

    public function mergeRequestsData($requests, $values, $carts){

        foreach($requests as $request_id => &$request){
            $request["values"] = array();
            if(isset($values[$request_id])){
                $request["values"] = $values[$request_id];
            }
            if(isset($carts[$request_id])){
                $request["cart"] = $carts[$request_id];
            }
        }

        return $requests;

    }

    public function getControls(){

        $controlsModel = new shopSkcallbackControlsModel();

        $controls = $controlsModel->getControlsWithTypes();

        return $controls;

    }

    public function getStatuses(){

        $modelStatus = new shopSkcallbackStatusModel();

        $statuses = $modelStatus->getAll();

        $result = array();

        foreach($statuses as $status){
            $result[$status['id']] = $status;
        }

        return $result;

    }

}