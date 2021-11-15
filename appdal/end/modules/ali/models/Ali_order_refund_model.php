<?php
/**
 * 1688 退款退货
 * @author yefanli
 */
class Ali_order_refund_model extends Purchase_model
{
    protected $table_name            = 'ali_order_refund';

    public function __construct()
    {
        parent::__construct();
        $this->load->library('alibaba/AliAccount');
        $this->load->library('alibaba/AliOrderApi');
        $this->load->model('ali/Ali_order_model');
    }

    /**
     * 验证是否已经发起退款退货
     */
    public function verify_order_refund_data($id=null)
    {
        $res = ['code' => 0, 'msg' => ''];
        if(empty($id)){
            $res['msg'] = '拍单号不能为空！';
            return $res;
        }
        $data = $this->purchase_db->from('ali_order_refund')
            ->where(['pai_number' => $id])
            ->where( "refund_status !=", 2)
            ->order_by("id", "desc")
            ->get()
            ->row_array();
        if(!$data || !isset($data['refund_status'])) return $res;
        switch ($data['refund_status']){
            case 0:
                $res['msg'] = '当前退货退款处理未完成，请勿重复申请！';
                break;
            case 1:
                $res['msg'] = '该订单已申请过退货退款，请勿重复申请！';
                break;
            default:
                $res['msg'] = '当前退货退款处理未完成，请勿重复申请！';
        }
        $res['code'] = 1;
        return $res;
    }

    /**
     * 获取系统采购单对应的拍单信息详情
     */
    public function get_purchase_info($id = false)
    {
        $res = ['code' => 0, 'msg' => ''];
        if(empty($id)){
            $res['msg'] = '拍单号不能为空！';
            return $res;
        }
        $data = $this->purchase_db->from('ali_order')->where(['order_id' => $id])->get()->row_array();
        if($data){
            $row = [];
            $row['purchase_account']        = $data['purchase_account'];
            $row['total_success_amount']    = $data['total_success_amount'];
            $row['order_status']            = $data['order_status'];
            $row['purchase_number']         = $data['purchase_number'];
            $res['code'] = 1;
            $res['msg'] = $row;
        }
        return $res;
    }

    /**
     * 获取1688退款退货信息
     */
    public function get_order_refund_data($order_id=null)
    {
        $res = [
            "refund_reason" => [],
            "ali_order"     => [],
            "ship_price"    => 0,
            "msg"           => '',
        ];
        try{
            $orderInfo = $this->aliorderapi->getListOrderDetail(null, $order_id);
            $res['msg'] = $orderInfo;
            if (isset($orderInfo[$order_id]) and isset($orderInfo[$order_id]['code']) and $orderInfo[$order_id]['code'] == 200
                && isset($orderInfo[$order_id]['data']['productItems'])) {
                $order = [];
                $goods_status = "";
                $subOrderId = [];
                foreach ($orderInfo[$order_id]['data']['productItems'] as $val){
                    $row = [];
                    $subItemID = (string)$val['subItemID'];
                    $subOrderId[]                           = $subItemID;
                    if($goods_status == '')$goods_status    = $val['logisticsStatus'];
                    $row['sub_order_id']                    = $subItemID;
                    $row['product']                         = $val['name'];
                    $row['images']                          = isset($val['productImgUrl'][0])?$val['productImgUrl'][0]:"";
                    $row['big_images']                      = isset($val['productImgUrl'][1])?$val['productImgUrl'][1]:"";
                    $row['price']                           = $val['price'];    // 原始单价
                    $row['pay_price']                       = $val['itemAmount'];    // 实付金额
                    $row['order_status']                    = $val['status'];       // 订单状态
                    $row['order_status_str']                = $val['statusStr']; // 订单状态描述
                    $row['goods_status']                    = $val['logisticsStatus']; // 货物状态
                    $row['quantity']                        = $val['quantity']; // 订单销售数量
                    $row['productID']                       = $val['productID']; // 1688 商品ID
                    $row['skuID']                           = $val['skuID']; // 1688 SKU ID
                    $row['specId']                          = $val['specId']; // 1688 SPEC ID
                    $order[] = $row;
                }

                $res['refund_reason'] = [];
                $res['ali_order'] = $order;
            }
            if(isset($orderInfo[$order_id]['data']['baseInfo']['shippingFee']))$res['ship_price'] = $orderInfo[$order_id]['data']['baseInfo']['shippingFee'];
        }catch (Exception $e){
            $res['msg'] = $e->getMessage();
        }
        return $res;
    }

    /**
     * 获取退款原因接口
     */
    public function get_order_refund_reason($order_id, $subOrderId, $goods_status)
    {
        $data = $this->aliorderapi->getOrderRefundReason($order_id, $subOrderId, $goods_status);
        if(isset($data['code']) && $data['code'] == 1)return $data['msg'];
        return [];
    }

    /**
     * 校验退款退货是否符合需求
     */
    public function verify_order_refund_submit_data($params)
    {
        $res = ["code" => 0, "msg" => ''];
        $base_order = $this->get_order_refund_data($params['order_id']);
        if(!isset($base_order['ali_order']) && count($base_order['ali_order']) == 0)return $res;
        $res['list'] = $base_order;

        // 获取接口数据，与提交数据进行校验
        $goods_status = $params['goods_status'];
        $refund_type = $params['refund_type'];
        $pay_price = 0;
        foreach ($params['ali_order'] as $pv){
            $is_break = false;
            foreach ($base_order['ali_order'] as $val){
                if($val['sub_order_id'] != $pv['sub_order_id'])continue;
                $pay_price += $val['price'] * $pv['number'];
                if($pv['number'] > $val['quantity']){
                    $res['msg'] = '退款产品数量不能大于购买数量！';
                    $is_break = true;
                    break;
                }
                $val['order_status'] = strtolower($val['order_status']);

                // 子订单状态：未发货
                $not_send_status = ['waitbuyerpay', 'waitsellersend', 'cancel', 'paid_but_not_fund', 'waitsellerconfirm', 'waitselleract', 'waitsellerpush'];
                if(in_array($val['order_status'], $not_send_status)){
                    if($refund_type != 1){
                        $res['msg'] = '订单未发货，只能选择退款。';
                        $is_break = true;
                        break;
                    }
                    if(!in_array($goods_status, ['refundWaitSellerSend'])){
                        $res['msg'] = '未发货状态，只能选择退款，且货物状态为“售中待卖家发货”。';
                        $is_break = true;
                        break;
                    }
                }

                // 子订单状态：已发货
                $send_status = ['waitlogisticstakein', 'waitbuyerreceive', 'send_goods_but_not_fund', 'waitbuyersign', 'signinfailed', 'waitbuyerconfirm', 'waitbuyerconfirmaction'];
                if(in_array($val['order_status'], $send_status)){
                    if($refund_type == 1){
                        if(!in_array($goods_status, ['refundWaitBuyerReceive', 'refundBuyerReceived'])){
                            $res['msg'] = '订单已发货进行退款时，货物状态为“售中待卖家收货”和“售中已收货（未确认完成交易）”。';
                            $is_break = true;
                            break;
                        };
                    }
                    if($refund_type == 2){
                        if(!in_array($goods_status, ['refundBuyerReceived'])){
                            $res['msg'] = '订单已发货进行退款退货时，货物状态应该为“售中已收货（未确认完成交易）”';
                            $is_break = true;
                            break;
                        };
                    }
                }

                // 子订单状态：已签收
                $sign_status = ['confirm_goods', 'success', 'confirm_goods_but_not_fund', 'confirm_goods_and_has_subsidy', 'signinsuccess'];
                if(in_array($val['order_status'], $sign_status)){
                    if($refund_type == 1){
                        if(!in_array($goods_status, ['aftersaleBuyerNotReceived', 'aftersaleBuyerReceived'])){
                            $res['msg'] = '订单状态为已收货退款时，货物状态应该是“售后未收货”或“售后已收货”。';
                            $is_break = true;
                            break;
                        };
                    }
                    if($refund_type == 2){
                        if(!in_array($goods_status, ['aftersaleBuyerReceived'])){
                            $res['msg'] = '订单状态为已收货退款退货时，货物状态应该是“售后已收货”。';
                            $is_break = true;
                            break;
                        };
                    }
                }

            }
            if($is_break){
                $res['code'] = 1;
                break;
            }
        }
        if($params['refund_total'] != $pay_price){
            $res = ["code" => 0, "msg" => '退款金额必须小于等于下单金额！'];
        }
        return $res;
    }

    /**
     * 保存并发送退款退货信息
     */
    public function save_and_send_refund_data($params, $type=false)
    {
        $res = ["code" => 0, "msg" => ""];
        $this->purchase_db->trans_begin();
        try{
            $ali_order = $this->get_order_refund_data($params['order_id']);
            $images = [];
            $images_api = [];
            $time = date('Y-m-d H:i:s');
            if(!empty($params['images']) && is_array($params['images'])){
                foreach ($params['images'] as $val){
                    $image = $this->aliorderapi->uploadRefundImages($val);
                    $images_api[] = $image;
                    if(!empty($image))$images[] = $image;
                }
            }
            $disputeRequest =$params['refund_type'] == 1 ? "refund": "returnRefund";
            $send_params = [
                "orderId"               => $params['order_id'],
                "orderEntryIds"         => [],
                "disputeRequest"        => $disputeRequest,
                "applyPayment"          => (float)$params['refund_total'],
                "applyCarriage"         => (float)$params['refund_ship'],
                "applyReasonId"         => (integer)$params['refund_reason'],
                "description"           => $params['remarks'],
                "goodsStatus"           => $params['goods_status'],
                "vouchers"              => $images,
                "orderEntryCountList"   => [], // ["id" => 123, "count" => 1]
            ];

            $insert_items = [];
            $product_price = 0;
            if(!empty($params['ali_order']) && is_array($params['ali_order'])){
                foreach ($params['ali_order'] as $val){
                    $send_params['orderEntryIds'][] = $val['sub_order_id'];
                    $send_params['orderEntryCountList'][] = [
                        "id"    => $val['sub_order_id'],
                        "count" => $val['number'],
                    ];
                    foreach ($ali_order['ali_order'] as $v){
                        if($val['sub_order_id'] != $v['sub_order_id'])continue;
                        $product_price += $v['price'] * $v['quantity'];
                        $insert_items[] = [
                            "pai_number"            => $params['order_id'],
                            "child_number"          => $v['sub_order_id'],
                            "product_name"          => $v['product'],
                            "images"                => $v['images'],
                            "unit_price"            => $v['price'],
                            "quantity"              => $v['quantity'],
                            "pay_price"             => $v['pay_price'],
                            "order_status"          => $v['order_status'],
                            "goods_status"          => $v['goods_status'],
                        ];
                    }
                }
            }

            $order = $this->get_purchase_info($params['order_id']);
            $order = $order['msg'];
            $insert_main = [
                "refund_number"                     => 0,
                "pai_number"                        => $params['order_id'],
                "purchase_number"                   => $order['purchase_number'], // 采购订单号
                "apply_carriage"                    => $params['refund_ship'], // 退款运费
                "apply_payment"                     => $product_price, // 退款商品额
                "apply_amount"                      => $params['refund_total'], // 退款总金额
                "create_time"                       => $time, // 创建时间/申请退款时间
                "completed_time"                    => '', // 完成时间
                "apply_reason"                      => $params['refund_reason'], // 申请原因
                "refund_type"                       => $params['refund_type'], // 操作类型：0退货，1退款，2退款退货
                "refund_status"                     => 0, // 退款退货状态：0已发起
                "apply_uid"                         => $type? 0 :getActiveUserId(), // 申请人id
                "apply_user"                        => $type? "系统自动发起退款" :getActiveUserName(), // 申请人
                "ali_order_status"                  => $order['order_status'], // ali订单状态
                "buyer_user"                        => $order['purchase_account'], // 网拍账号
                "goods_status"                      => $params['goods_status'], // 货物状态
                "apply_reason_text"                 => $params['remarks'], // 退款理由
                "images"                            => json_encode($params['images']), // 退款退货相关图片
            ];

            if($type){
                $insert_main['apply_source'] = 1; // 系统申请
            }

            if(count($send_params['orderEntryIds']) > 0){
                $res['params'] = $send_params;
                $refund = $this->aliorderapi->sendOrderRefund($send_params);
                operatorLogInsert(
                    [
                        'id'      => $params['order_id'],
                        'type'    => 'send_refund_data_to_alibaba',
                        'content' => '1688申请退款退货',
                        'detail'  => "request:".json_encode($send_params)."........response:".json_encode($refund)
                    ]
                );
                if(isset($refund['code']) && $refund['code'] == 200 && isset($refund['data'])){
                    if(isset($refund['data']['success']) && $refund['data']['success'] &&
                        isset($refund['data']['result']['refundId']) && !empty($refund['data']['result']['refundId'])){
                        $insert_main['refund_number'] = $refund['data']['refundId'];
                        $insert_main['refund_status'] = 1;
                    }else if(isset($refund['data']['message'])){
                        $insert_main['err_message'] = '申请失败，原因：'.$refund['data']['message'];
                        $insert_main['refund_status'] = 2;
                    }else{
                        $insert_main['err_message'] = '申请失败，原因：接口返回未知的错误，请联系IT处理。';
                        $insert_main['refund_status'] = 2;
                    }
                }else if(isset($refund['code']) && $refund['code'] == 500){
                    $insert_main['err_message'] = '申请失败，原因：'.$refund['msg'];
                    $insert_main['refund_status'] = 2;
                }else{
                    throw new Exception('请求接口失败！');
                }
            }else{
                throw new Exception('没有可申请的数据！');
            }
            $insert_main['completed_time'] = date('Y-m-d H:i:s');

            // 写入数据表
            $this->purchase_db->insert("ali_order_refund", $insert_main);
            $this->purchase_db->insert_batch("ali_order_refund_items", $insert_items);

            $this->purchase_db->trans_commit();
            if($this->purchase_db->trans_status() === false){
                throw new Exception('申请失败,提交事务失败!');
            }else{
                $res['code'] = 1;
                $res["msg"] = '申请成功!';
            }
        }catch (Exception $e){
            $res["msg"] = $e->getMessage();
            $res['sendOrderRefund'] = $e->getMessage();
            operatorLogInsert(
                [
                    'id'      => $params['order_id'],
                    'type'    => 'save_and_send_refund_data',
                    'content' => '1688申请退款退货',
                    'detail'  => json_encode($params).$res["msg"]
                ]
            );
            $this->purchase_db->trans_rollback();
        }
        return $res;
    }

    /**
     * 写入订阅数据 29160 异常订单增加1688退货退款的模块
     */
    public function subscribe_refund_data($params)
    {
        $result = false;
        $res = [];
        try{
            $this->purchase_db->trans_begin();
            if(empty($params) || !isset($params['orderId']) || !isset($params['refundId']))throw new Exception('未获取到相应的数据!');

            // 检测是否为本系统产生的数据
            $check_ref = $this->purchase_db->from('ali_order o')
                ->join("pur_ali_order_refund as r", "o.order_id=r.pai_number", "left")
                ->select('o.order_id,r.pai_number,o.purchase_number,o.order_status,o.purchase_account')
                ->where(["o.order_id" => $params['orderId']])
                ->get()->result_array();

            if(!$check_ref || empty($check_ref))throw new Exception('非系统产生的数据!');; // 非系统产生的数据不需要

            $res = $this->aliorderapi->getListOrderRefund($params['orderId'], true);
            $main = [];

            if($res && isset($res['code']) && $res['code'] == 200 && isset($res['data'])){
                $resData = $res['data'][0];
                $refundCarriage = is_numeric($resData['refundCarriage'])?$resData['refundCarriage']:0;
                $refundPayment = is_numeric($resData['refundPayment'])?$resData['refundPayment']:0;
                $applyPayment = is_numeric($resData['applyPayment'])?$resData['applyPayment']:0;
                $applyCarriage = is_numeric($resData['applyCarriage'])?$resData['applyCarriage']:0;
                $main['ali_payment_id']            = $resData['alipayPaymentId']??"";
                $main['saler_refuse_reason']       = $resData['rejectReason']??"";
                $main['refund_ship']               = $resData['buyerLogisticsName']??"";
                $main['refund_ship_number']        = $resData['freightBill']??"";
                $main['refund_carriage']           = format_two_point_price($refundCarriage/100);
                $main['refund_payment']            = format_two_point_price($refundPayment/100);
                $main['dispute_request']           = $resData['disputeRequest']??"";

                if(empty($check_ref[0]["pai_number"])){
                    $gmtCreate = $resData["gmtCreate"] && is_string($resData["gmtCreate"])?$resData["gmtCreate"]:"";
                    $gmtModified = $resData["gmtModified"] && is_string($resData["gmtModified"])?$resData["gmtModified"]:"";
                    $main['goods_status']           = $resData['goodsStatus']??""; // 货物状态  1：买家未收到货 2：买家已收到货 3：买家已退货
                    $main['pai_number']             = $params["orderId"]; // 订单id
                    $main['refund_number']          = $params["refundId"]; // 退款单号
                    $main['purchase_number']        = $check_ref[0]["purchase_number"]??""; // 采购单号
                    $main['apply_carriage']         = format_two_point_price($refundCarriage/100); // 申请退款运费
                    $main['apply_payment']          = format_two_point_price($applyPayment/100); // 申请退款商品额
                    $main['apply_amount']           = format_two_point_price($applyCarriage + $applyPayment); // 申请退款总金额
                    $main['create_time']            = $gmtCreate; // 创建时间/申请退款时间
                    $main['completed_time']         = $gmtModified; // 完成时间
                    $main['apply_reason']           = $resData["applyReason"]??""; // 申请原因
                    $main['refund_type']            = 1; // 操作类型
                    $main['apply_uid']              = $resData["buyerUserId"]??""; // 申请人id
                    $main['apply_user']             = "1688 backend user"; // 申请人
                    $main['refund_status']          = $params["currentStatus"]; // 退款退货状态
                    $main['ali_order_status']       = $check_ref[0]["order_status"]??''; // ali订单状态
                    $main['buyer_user']             = $check_ref[0]["purchase_account"]??''; // 网拍账号
                    $main['apply_reason_text']      = $resData["applyReason"]??""; // 退款理由
                }
            }

            if(empty($main))throw new Exception('没有要写入的数据!');

            // 如果非本系统发起 写入数据
            if(empty($check_ref[0]["pai_number"])){
                $this->purchase_db->insert($this->table_name(), $main);
            }else{
                $this->purchase_db->update($this->table_name(), $main, ["pai_number" => $params["orderId"], "refund_number" => $params["refundId"]]);
            }

            $this->purchase_db->trans_commit();
            if($this->purchase_db->trans_status() === false){
                $this->purchase_db->trans_rollback();
                throw new Exception('更新失败,提交事务失败!');
            }else{
                $result = 'success';
            }
        }catch (Exception $e){
            $this->purchase_db->trans_rollback();
            $result = $e->getMessage().json_encode($res);
        }
        return $result;
    }

    /**
     * 获取列表数据
     */
    public function get_order_refund_list($params)
    {
        $res = ["code" => 0, "value" => [], 'msg' => '暂无数据！'];
        $buyer = [];
        // 获取所有采购员和组别
        $user_list = [];
        $group = [];
        $user_group = $this->purchase_db->from("purchase_group")->get()->result_array();
        if($user_group && !empty($user_group)){
            foreach ($user_group as $val){
                $group[$val['category_id']] = $val['group_name'];
            }
        }
        $user = $this->purchase_db->from("purchase_user")->select("user_code,user_name")->get()->result_array();
        if($user && count($user) > 0){
            foreach ($user as $val){
                $user_list[$val['user_code']] = $val['user_name'].$val['user_code'];
            }
        }
        if(isset($params['buyer_id']) && !empty($params['buyer_id']))$buyer = $params['buyer_id']; // 采购员
        if(isset($params['buyer_group']) && !empty($params['buyer_group'])){ // 采购组别
            foreach ($user as $val){
                //if(!in_array($val['group_id'], $params['buyer_group']))$buyer[] = $val['user_id'];
            }
        }

        $base = $this->purchase_db->from("ali_order_refund as ar")
            ->join("pur_ali_order as pt", "ar.purchase_number=pt.purchase_number", "inner")
            ->join("pur_purchase_order as o", "pt.purchase_number=o.purchase_number", "left")
            ->join("pur_ali_order_refund_items as it", "ar.pai_number=it.pai_number", "left");

        $andWhere = [];
        if(isset($params['refund_no']) && !empty($params['refund_no']))                 $andWhere['ar.refund_number'] = $params['refund_no'];  // 退款单号
        if(isset($params['purchase_account']) && !empty($params['purchase_account']))   $andWhere['pt.purchase_account'] = $params['purchase_account'];  // 网拍账号
        if(isset($params['refund_status']) && is_numeric($params['refund_status']) && in_array($params['refund_status'], [0, 1, 2]))$andWhere['ar.refund_status'] = $params['refund_status'];  // 退款状态
        if(isset($params['refund_type']) && !empty($params['refund_type']))             $andWhere['ar.refund_type'] = $params['refund_type'];  // 纠纷类型（退款、退货退款）
        if(isset($params['refund_ship']) && !empty($params['refund_ship']))             $andWhere['ar.refund_ship'] = $params['refund_ship'];  // 买家退货物流
        if(isset($params['express_no']) && !empty($params['express_no']))               $andWhere['ar.refund_ship_number'] = $params['express_no'];  // 运单号
        if(isset($params['supplier_code']) && !empty($params['supplier_code']))         $andWhere['o.supplier_code'] = $params['supplier_code']; // 供应商
        if(!empty($andWhere))$base->where($andWhere);

        if(isset($params['pai_number']) && !empty($params['pai_number'])){
            $pai_number = explode(" ", $params['pai_number']);
            $base->where_in("ar.pai_number", $pai_number);
        }  // 拍单号
        if(isset($params['ali_order_status']) && !empty($params['ali_order_status']))   $base->where_in("ar.ali_order_status", $params['ali_order_status']); // 阿里订单状态
        if(isset($params['goods_status']) && !empty($params['goods_status']))           $base->where_in("ar.goods_status", $params['goods_status']); // 货物状态
        if(isset($params['sale_refuse_reason']) && !empty($params['sale_refuse_reason']))$base->where_in("ar.saler_refuse_reason", $params['saler_refuse_reason']); // 卖家拒绝原因

        if(isset($params['apply_time']) && is_array($params['apply_time']) && count($params['apply_time']) == 2 && !empty($params['apply_time'][0])){
            $base->where("ar.create_time between '{$params['apply_time'][0]}' and '{$params['apply_time'][1]}'");
        } // 申请退款时间
        if(isset($params['finish_time']) && is_array($params['finish_time']) && count($params['finish_time']) == 2 && !empty($params['finish_time'][0])){
            $base->where("ar.completed_time between '{$params['finish_time'][0]}' and '{$params['finish_time'][1]}'");
        } // 完成时间
        if(isset($params['create_time']) && is_array($params['create_time']) && count($params['create_time']) == 2 && !empty($params['create_time'][0])){
            $base->where("ar.create_time between '{$params['create_time'][0]}' and '{$params['create_time'][1]}'");
        } // 创建时间

        if(count($buyer) > 0)$base->where_in("o.buyer_id", $buyer); // 采购员

        $page = isset($params['page']) && !empty($params['page'])?(int)$params['page']:1;
        $offset = isset($params['limit']) && !empty($params['limit'])? (int)$params['limit']: 20;
        $start = ($page - 1) * $offset;
        $count_sql = clone $base;
        $count = $count_sql->select("count(DISTINCT ar.pai_number) as all_row")->get()->row_array();
        $data = $base->select("ar.*,pt.purchase_account,o.supplier_code,o.supplier_name,o.buyer_name,o.buyer_id,it.product_name,it.quantity")
            ->order_by("ar.id desc")->group_by("ar.pai_number")->limit($offset)->offset($start)->get()->result_array();
//        echo $base->last_query();exit;
        $res_temp = [];

        // 获取供应商联系旺旺
        $sup_code = array_column($data, "supplier_code");
        $supplier = [];
        if(count($sup_code) > 0){
            $sup_data = $this->purchase_db->from('supplier_contact')->select("supplier_code,want_want")->where_in("supplier_code", $sup_code)->get()->result_array();
            if($sup_data && count($sup_data)){
                foreach ($sup_data as $val){
                    $supplier[$val['supplier_code']] = $val['want_want'];
                }
            }
        }

        $res['buyer_id']            = $user_list;
        $res['buyer_group']         = $group;
        $res['refund_reason']       = getAliOrderRefundReason();
        $res['refund_status']       = getAliOrderRefundStatus();
        $res['refund_type']         = ["1" => "退款", "2" => "退货退款"];
        $res['goods_status']        = aliOrderGoodsStatus();
        $res['ali_order_status']    = getAliOrderSubStatus();
        $res['sale_refuse_reason']  = getSaleRefuseReason();
        $res['count']               = isset($count['all_row']) && !empty($count['all_row'])?(int)$count['all_row']:0;

        if(!$data && empty($data)) return $res;

        foreach ($data as $val){
            if(!in_array($val['pai_number'], array_keys($res_temp))){
                $row = [];
                $row['purchase_account']            = $val['purchase_account'];
                $row['pai_number']                  = $val['pai_number'];
                $row['refund_no']                   = $val['refund_number'];
                $row['ali_order_status']            = getAliOrderSubStatus($val['ali_order_status']);
                $row['goods_status']                = aliOrderGoodsStatus($val['goods_status']);
                $row['refund_status']               = getAliOrderRefundStatus($val['refund_status']);
                $row['goods_price']                 = round($val['apply_payment'], 2);
                $row['ship_price']                  = round($val['apply_carriage'], 2);
                $row['real_ship_price']             = round($val['refund_payment'], 2);
                $row['real_goods_price']            = round($val['refund_carriage'], 2);
                $row['refund_type']                 = getRefundType($val['refund_type']);
                $row['refund_reason']               = $val['apply_reason'];
                $row['refund_ship']                 = $val['refund_ship'];
                $row['express_no']                  = $val['refund_ship_number'];

                $row['sale_refuse_reason']          = $val['saler_refuse_reason'];
                $row['apply_time']                  = $val['create_time'];
                $row['create_time']                 = $val['create_time'];
                $row['finish_time']                 = $val['completed_time'];
                $row['buyer_name']                  = $val['buyer_name'];
                $row['supplier_code']               = $val['supplier_code'];
                $row['supplier_name']               = $val['supplier_name'];
                $row['contact_ww']                  = $supplier[$val['supplier_code']];
                $row['apply_reason']                = $val['apply_reason_text'];
                $row['ali_pay_no']                  = $val['ali_payment_id'];
                $row['after_sales_refund_remark']   = $val['dispute_request'];
                $row['items_list'] = [];
                $row['purchase_number']             = [];
                $res_temp[$val['pai_number']] = $row;
            }

            if(!in_array($val['purchase_number'], $res_temp[$val['pai_number']]['purchase_number']))$res_temp[$val['pai_number']]['purchase_number'][] = $val['purchase_number'];
            $res_temp[$val['pai_number']]['items_list'][] = [
                "product_name"      => $val['product_name'],
                "number"            => !empty($val['quantity'])?(int)$val['quantity']:0,
            ];
        }
        if(count($res_temp) > 0){
            $res['msg'] = '查询成功！';
            $res['value'] = array_values($res_temp);
            $res['code'] = 1;
        }
        return $res;
    }
}
