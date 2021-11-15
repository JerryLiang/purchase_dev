<?php
/**
 * 1688 退款退货
 * @author yefanli
 */
class Ali_order_refund extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_1688');

        $this->load->library('alibaba/AliProductApi');
        $this->load->library('alibaba/AliOrderApi');
        $this->load->model('Ali_order_refund_model');
    }

    /**
     * 获取 1688 退货退款信息
     */
    public function get_order_refund_data()
    {
        $order_id = $this->input->get_post('order_id');
        if(empty($order_id))$this->error_json('拍单号不能为空');

        $this->load->model('purchase/Purchase_order_determine_model');
        $this->load->model('ali/Ali_product_model');

        // 重复性校验
        $verify_order = $this->Ali_order_refund_model->verify_order_refund_data($order_id);
        if(isset($verify_order['code']) && $verify_order['code'] == 1)$this->error_json($verify_order['msg']);

        // 获取系统订单信息
        $order_res = ["purchase_account"=>"", "pai_number"=>"", "ali_order_status"=>""];

        // 获取1688订单信息
        $order_info = $this->Ali_order_refund_model->get_order_refund_data($order_id);
        // 阿里订单信息
        $ali_order = $order_info['ali_order'];

        $purchanse = $this->Ali_order_refund_model->get_purchase_info($order_id);
        if(isset($purchanse['code']) && $purchanse['code'] == 1){
            $order_res['purchase_account']      = $purchanse["msg"]['purchase_account'];
            $order_res['pai_number']            = $order_id;
            $order_res['ali_order_status']      = getAliOrderSubStatus($purchanse["msg"]['order_status']);

            // 采购数量信息
            $purchase_number = $purchanse['msg']['purchase_number'];
            $purchaseDetails = $this->Purchase_order_determine_model->get_order_info($purchase_number);
            $purchaseDetails = arrayKeyToColumn($purchaseDetails,'sku');
            $sku_list        = array_column($purchaseDetails,'sku');

            foreach($ali_order as &$ali_order_item){
                $where = [
                    'sku'           => $sku_list,
                    'product_id'    => $ali_order_item['productID'],
                    'spec_id'       => $ali_order_item['specId'],
                ];

                $aliProductInfo = $this->Ali_product_model->get_ali_product_one($where);
                if($aliProductInfo){
                    $sku        = $aliProductInfo['sku'];
                    $skuQtyList = isset($purchaseDetails[$sku])?$purchaseDetails[$sku]:[];

                    $ali_order_item['sku'] = $skuQtyList['sku'];
                    $ali_order_item['confirm_amount'] = $skuQtyList['confirm_amount'];
                    $ali_order_item['upselft_amount'] = $skuQtyList['upselft_amount'];
                    $ali_order_item['receive_amount'] = $skuQtyList['receive_amount'];
                    $ali_order_item['instock_qty'] = $skuQtyList['instock_qty'];
                    $ali_order_item['loss_amount'] = $skuQtyList['loss_amount'];
                    $ali_order_item['cancel_ctq'] = $skuQtyList['cancel_ctq'];
                    $ali_order_item['level_amount'] = $ali_order_item['confirm_amount']
                        - $ali_order_item['instock_qty']
                        - $ali_order_item['loss_amount']
                        - $ali_order_item['cancel_ctq'];
                }else{
                    $ali_order_item['sku'] = '';
                    $ali_order_item['confirm_amount'] = 0;
                    $ali_order_item['upselft_amount'] = 0;
                    $ali_order_item['receive_amount'] = 0;
                    $ali_order_item['instock_qty'] = 0;
                    $ali_order_item['loss_amount'] = 0;
                    $ali_order_item['cancel_ctq'] = 0;
                    $ali_order_item['level_amount'] = 0;

                }
            }
        }

        $order_res['ali_order'] = $ali_order;
        $order_res['shipping'] = $order_info['ship_price'];

        // 返回数据
        $res = [
            "order_info"        => $order_res,
            "refund_type"       => [ 1=> "退款", 2=> "退款退货"],
            "refund_reason"     => [],
            "goods_status"      => aliOrderGoodsStatus(),
            "error"             => $order_info['msg'],
        ];
        $this->success_json($res, [], "获取成功");
    }

    /**
     * 获取退款原因接口
     */
    public function get_order_refund_reason()
    {
        $order_id = $this->input->get_post('order_id');
        $subOrderId = $this->input->get_post('sub_order_id');
        $goods_status = $this->input->get_post('goods_status');
        if(empty($order_id) || empty($subOrderId) || empty($goods_status))$this->error_json('必要参数不能为空!');
        $order_id = (integer)$order_id;
        $subId = [];
        foreach ($subOrderId as $val){
            $subId[] = (integer)$val;
        }
        $data = $this->Ali_order_refund_model->get_order_refund_reason($order_id, $subId, $goods_status);
        if($data && !empty($data))$this->success_json(["refund_reason" => $data]);
        $this->error_json('没有相应的退款退货原因');
    }

    /**
     * 保存1688退货退款信息
     */
    public function save_order_refund_data()
    {
        $params = [
            "order_id"                  => $this->input->get_post('order_id'),
            "refund_type"               => $this->input->get_post('refund_type'),
            "refund_reason"             => $this->input->get_post('refund_reason'),
            "goods_status"              => $this->input->get_post('goods_status'),
            "refund_total"              => $this->input->get_post('refund_total'),
            "refund_ship"               => $this->input->get_post('refund_ship'),
            "remarks"                   => $this->input->get_post('remarks'),
            "images"                    => $this->input->get_post('images'),
            "ali_order"                 => $this->input->get_post('ali_order'),
        ];

        $nouNull = ["order_id","refund_type" ,"refund_reason","goods_status","remarks","ali_order",];
        $is_empty = false;
        foreach ($nouNull as $val){
            if(empty($params[$val]))$is_empty = true;
        }
        if($is_empty)$this->error_json('必要参数不能为空');
        if(!is_numeric($params['refund_total']) || $params['refund_total'] < 0)$this->error_json('退款退货的总金额不能为空或不能小于0。');
        if(!is_numeric($params['refund_ship']) || $params['refund_ship'] < 0)$this->error_json('退款退货的运费不能为空或不能小于0。');
        if(!is_array($params['ali_order']))$this->error_json('退款退货的子订单信息不能为空。');
        $params['order_id'] = (integer)$params['order_id'];

        $ali_order = [];
        $is_err = [];
        foreach ($params['ali_order'] as $val){
            try{
                $val = json_decode($val, true);
                if($val['number'] < 0)$is_err[] = "订单{$val['sub_order_id']}金额不能小于0";
                $val['sub_order_id'] = (integer)$val['sub_order_id'];
                $val['number'] = (int)$val['number'];
                $ali_order[] = $val;
            }catch (Exception $e){}
        }
        if(count($is_err) > 0)$this->error_json(implode(",", $is_err));
        if(count($ali_order) == 0 || count($ali_order) != count($params['ali_order']))$this->error_json('退款退货的子订单信息不完整，请检查后再提交。');
        $params['ali_order'] = $ali_order;

        // 重复性校验
        $verify_order = $this->Ali_order_refund_model->verify_order_refund_data($params['order_id']);
        if(isset($verify_order['code']) && $verify_order['code'] == 1)$this->error_json($verify_order['msg']);

        // 校验合规
        $verify = $this->Ali_order_refund_model->verify_order_refund_submit_data($params);
        if($verify['code'] == 1)$this->error_json($verify['msg']);

        // 发送并保存
        $save = $this->Ali_order_refund_model->save_and_send_refund_data($params);
        if($save['code'] == 1)$this->success_json($save, [],"申请成功");
        $this->error_json($save['msg']);
    }

    /**
     * 获取1688退款退货数据
     */
    public function get_order_refund_list()
    {
        $params = [
            "pai_number"                => $this->input->get_post('pai_number'),        // 拍单号
            "refund_no"                 => $this->input->get_post('refund_no'),         // 退款单号
            "purchase_account"          => $this->input->get_post('purchase_account'),  // 网拍账号
            "ali_order_status"          => $this->input->get_post('ali_order_status'),  // 阿里订单状态
            "goods_status"              => $this->input->get_post('goods_status'),      // 货物状态
            "refund_status"             => $this->input->get_post('refund_status'),     // 退款状态
            "refund_type"               => $this->input->get_post('refund_type'),       // 纠纷类型（退款、退货退款）
            "refund_reason"             => $this->input->get_post('refund_reason'),     // 退款原因
            "apply_time"                => $this->input->get_post('apply_time'),        // 申请退款时间
            "finish_time"               => $this->input->get_post('finish_time'),       // 完成时间
            "refund_ship"               => $this->input->get_post('refund_ship'),       // 买家退货物流
            "express_no"                => $this->input->get_post('express_no'),        // 运单号
            "sale_refuse_reason"        => $this->input->get_post('sale_refuse_reason'),// 卖家拒绝原因
            "create_time"               => $this->input->get_post('create_time'),       // 创建时间
            "supplier_code"             => $this->input->get_post('supplier_code'),     // 供应商
            "buyer_id"                  => $this->input->get_post('buyer_id'),          // 采购员
            "buyer_group"               => $this->input->get_post('buyer_group'),       // 采购组别
            "limit"                     => $this->input->get_post('limit'),             // 每页显示条数
            "pages"                     => $this->input->get_post('offset'),            // 页码
        ];
        $data = $this->Ali_order_refund_model->get_order_refund_list($params);

        $page_data = ["total" => 0, "offset" => (int)$params['pages'], "limit" => (int)$params['limit'], "pages" => (int)$params['pages']];
        $msg = '暂无数据！';
        if($data['code'] == 1){
            $msg = '查询成功！';
            $page_data['total'] = $data['count'];
        }
        unset($data['code']);
        unset($data['msg']);
        unset($data['count']);
        $this->success_json($data, $page_data, $msg);
    }

}