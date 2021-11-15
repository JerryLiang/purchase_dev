<?php
/**
 * 1688 退款退货
 * @author 叶凡立
 * @Date 2020/12/09
 */

class Ali_order_refund_model extends Api_base_model
{

    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * 获取1688 退款退货信息
     */
    public function get_order_refund_data($params)
    {
        $url = $this->_baseUrl.$this->_getOrderRefundData;
        return $this->httrequest($params, $url);
    }

    /**
     * 保存1688 退款退货信息
     */
    public function save_order_refund_data($params)
    {
        $url = $this->_baseUrl.$this->_saveOrderRefundData;
        return $this->httrequest($params, $url);
    }

    /**
     * 保存1688 退款退货原因
     */
    public function get_order_refund_reason($params)
    {
        $url = $this->_baseUrl.$this->_saveOrderRefundReason;
        return $this->httrequest($params, $url);
    }

    /**
     * 获取退款退货列表
     */
    public function get_order_refund_list($params)
    {
        $url = $this->_baseUrl.$this->_getOrderRefundList;
        return $this->httrequest($params, $url);
    }

}