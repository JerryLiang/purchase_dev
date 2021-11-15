<?php

/**
 * 该文件用于获取页面公共下拉列表
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @since 2020-01-16
 * @link
 */
class DropdownService
{
    private $_ci;

    /**
     * map
     * @var array
     * key:模块名称_下拉名称
     * value:对应的method
     *
     */
    private $_dropdown_list_name = [
        'purchase_user' => 'get_buyer_name',//采购员
        'return_processing_status' => 'getReturnProcessingStatus',//入库后退货-处理状态
        'return_warehouse' => 'getReturnWarehouse',//入库后退货-退货仓库
        'return_status' => 'getReturnStatus',//入库后退货-退货状态
        'return_is_confirm_receipt' => 'getReturnIsConfirmReceipt',//入库后退货-是否确认签收
        'get_track_status' => 'getTrackStatus',//物流轨迹状态
        'get_logistics_company' => 'getLogisticsCompany',//物流公司
        'freight_payment_type' => 'getFreightPaymentType',//运费支付类型
        'return_season' => 'getReturnSeason',//退货原因
        'province' => 'getProvince',//省
        'shipment_type' => 'getShipmentType',//发运类型
        'shipment_plan_cancel_audit_status' => 'getShipmentPlanCancelAuditStatus',//发运管理-计划部取消-审核状态
        'purchase_status' => 'getPurchaseStatus',//采购单(目前备货单也是取这个)状态

    ];

    private $_runtime_droplist_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
    }

    public function get_names()
    {
        return array_keys($this->_dropdown_list_name);
    }

    public function get()
    {
        $request_hash = md5(json_encode($this->_runtime_droplist_cb));

        static $last_hash;
        static $options;

        if ($last_hash == $request_hash) {
            return $options;
        }
        foreach ($this->_runtime_droplist_cb as $name => $callback) {
            if (isset($options[$name])) {
                continue;
            }
            if (is_string($callback)) {
                if (method_exists($this, $callback)) {
                    $options[$name] = $this->$callback();
                } else {
                    $options[$name] = $callback();
                }
            } else {
                $options[$name] = call_user_func_array($callback, []);
            }
        }
        $last_hash = $request_hash;

        return array_intersect_key($options, $this->_runtime_droplist_cb);
    }

    /**
     * 设置要获取的下拉列表
     */
    public function setDroplist($callbacks = [], $is_override = false, $helper = [])
    {
        if (!empty($helper)) {
            foreach ($helper as $name) {
                $this->_ci->load->helper($name);
            }
        }

        if ($is_override) {
            $this->_runtime_droplist_cb = [];
        }

        if (empty($callbacks)) {
            return;
        }

        foreach ($callbacks as $name => $cb) {
            //传递多个name
            if (is_numeric($name)) {
                $callback = $this->_dropdown_list_name[$cb] ?? '';
                $name     = $cb;
            } else {
                $callback = $cb;
            }
            if (is_string($callback)) {
                if (!(method_exists($this, $callback) || function_exists($callback))) {
                    throw new \BadMethodCallException(sprintf('获取下拉列表%s方法无法调用', $name), 500);
                }
            } else {
                if (!is_callable($callback)) {
                    throw new \BadMethodCallException(sprintf('获取下拉列表%s方法无法调用', $name), 500);
                }
            }

            $this->_runtime_droplist_cb[$name] = $callback;
        }

        return;
    }

    /**
     * delete
     * @param unknown $del_names
     * @return array
     */
    public function delDropList($del_names)
    {
        foreach ($this->_runtime_droplist_cb as $name => $cb) {
            if (in_array($name, $del_names, true)) {
                unset($this->_runtime_droplist_cb[$name]);
            }
        }

        return $this->_runtime_droplist_cb;
    }
}
