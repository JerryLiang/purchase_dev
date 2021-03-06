<?php

/**
 * [function desc]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Purchase_shipment_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    public function getshippingment($params){

        $url = $this->_baseUrl . $this->_getshippingment;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }

    /**
     * 二验交期确认
     * POST
     * @author:luxu
     * @time:2020/4/20
     **/
    public function updateShipmentTime($params){

        try {
            $url = $this->_baseUrl . $this->_updateShipmentTime;
            $resp = $this->_curlWriteHandleApi($url, $params, 'POST');

            return $resp;
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 获取修改日志
     * GET
     * @author:luxu
     * @time:2020/4/20
     **/
    public function getUpdateLog($params){

        $url = $this->_baseUrl . $this->_getUpdateLog;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }

    /**
     *  获取变更日志信息
     *  @Mthod GET
     *  @author:luxu
     *  @time:2020/4/20
     **/
    public function getChangeLog($params){

        $url = $this->_baseUrl . $this->_getChangeLog;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }

    /**
     *  获取计划变更日志信息
     *  @Mthod GET
     *  @author:luxu
     *  @time:2020/4/20
     **/

    public function getPlangChangeLog($params){

        $url = $this->_baseUrl . $this->_getPlangChangeLog;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }

    /**
     *  审核
     *  @Mthod POST
     *  @author:luxu
     *  @time:2020/4/21
     **/
    public function toExamineShipping($params){

        try {
            $url = $this->_baseUrl . $this->_toExamineShipping;
            $resp = $this->_curlWriteHandleApi($url, $params, 'POST');

            return $resp;
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    public function updateDelivery($params){

        try {
            $url = $this->_baseUrl . $this->_updateDelivery;
            $resp = $this->_curlWriteHandleApi($url, $params, 'POST');

            return $resp;
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
      * 导出发运管理跟踪模块CSV格式
     **/
    public function getshippingcsv($params){

        $url = $this->_baseUrl . $this->_getshippingcsv;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }

    public function getshippingexcel($params){
        $this->load->helper('export_csv');
        $this->load->helper('export_excel');
        $url = $this->_baseUrl . $this->_getshippingexcel;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        if( isset($result['status']) && $result['status'] ==1){

            $numi =0;
            $result_array = [];
            foreach($result['data_list']['list'] as $key=>$v_value){

                $v_value_tmp = [];
                $v_value_tmp['id'] = ++$numi;
                $v_value_tmp['new_demand_number'] = $v_value['new_demand_number'];
                $v_value_tmp['shipment_type_ch'] =   $v_value['shipment_type_ch'];
                $v_value_tmp['station_name'] = $v_value['station_name'];
                $v_value_tmp['destination_warehouse_name'] =  $v_value['destination_warehouse_name'];
                $v_value_tmp['sku'] =  $v_value['sku'];
                $v_value_tmp['product_img_url'] = $v_value['product_img_url'];
                $v_value_tmp['purchase_number'] = $v_value['purchase_number'];
                $v_value_tmp['supplier_name'] =  $v_value['supplier_name'];
                $v_value_tmp['is_drawback_ch'] = $v_value['is_drawback_ch'];
                $v_value_tmp['confirm_amount'] = $v_value['confirm_amount'];
                $v_value_tmp['cancel_amount'] = $v_value['cancel_amount'];
                $v_value_tmp['warehouse_numbers'] = $v_value['warehouse_numbers'];
                $v_value_tmp['deliver_status_ch'] = $v_value['deliver_status_ch'];
                $v_value_tmp['checkstatus_ch'] =  $v_value['checkstatus_ch'];
                $v_value_tmp['plan_qty'] = $v_value['plan_qty'];
                $v_value_tmp['t_plan_qty'] =$v_value['t_plan_qty'];
                $v_value_tmp['singlevolume'] = $v_value['singlevolume'];
                $v_value_tmp['sumvolume'] = $v_value['sumvolume'];
                $v_value_tmp['compactnumbers'] = $v_value['compactnumbers'];
                $v_value_tmp['audit_time'] = $v_value['audit_time'];
                $v_value_tmp['es_arrival_time'] = $v_value['es_arrival_time'];
                $v_value_tmp['es_shipment_time'] =  $v_value['es_shipment_time'];
                $v_value_tmp['is_can_change_time_ch'] = $v_value['is_can_change_time_ch'];
                $v_value_tmp['order_status_ch'] = $v_value['order_status_ch'];
                $v_value_tmp['suggest_status_ch'] =  $v_value['suggest_status_ch'];
                $v_value_tmp['warehousename'] =$v_value['warehousename'];
                $v_value_tmp['logis_type'] =  $v_value['logis_type'];
                $v_value_tmp['buyer_name'] =$v_value['buyer_name'];
                //check_time
                $v_value_tmp['check_time'] = $v_value['check_time'];
                $v_value_tmp['create_time'] = $v_value['create_time'];
                $v_value_tmp['update_time'] = $v_value['update_time'];
                $result_array[] = $v_value_tmp;

            }
            $filename = '发运跟踪EXCEL.xls';
            export_excel($result['data_list']['header'], $result_array, $filename,array('图片'),array('product_img_url'));
        }

    }

    /**
     * 发运类型审核列表
     * @MTHODS GET
     * @AUTHOR:LUXU
     * @time:2020/7/3
     **/

    public function showToExamineShipping($params){

        $url = $this->_baseUrl . $this->_showToExamineShipping;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }
}