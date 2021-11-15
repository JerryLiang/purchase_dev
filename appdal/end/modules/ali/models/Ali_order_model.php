<?php
/**
 * 1688 订单数据模型
 * User: Jolon
 * Date: 2019/03/20 10:00
 */
class Ali_order_model extends Purchase_model {

    protected $table_name            = 'ali_order';
    protected $table_name_item       = 'ali_order_items';
    protected $table_name_pay        = 'purchase_order_pay';
    protected $table_order           = 'purchase_order';
    protected $table_cancel          = 'ali_order_cancel';
    protected $table_cancel_item     = 'ali_order_cancel_items';
    protected $table_order_pay_type  = 'purchase_order';
    //采购单物流信息表
    protected $table_logistics_info = 'purchase_logistics_info';

    public function __construct(){
        parent::__construct();

        $this->load->library('alibaba/AliAccount');
        $this->load->library('alibaba/AliOrderApi');

        $this->load->model('ali/Ali_product_model');
        $this->load->model('user/Purchase_user_model');
        $this->load->model('warehouse/Warehouse_model');
        $this->load->model('purchase/Purchase_order_model');
        $this->load->model('purchase/purchase_order_extend_model');
        $this->load->model('product/Product_model');
        $this->load->model('supplier/Supplier_model');
        $this->load->model('supplier/Supplier_payment_account_model');
        $this->load->model('supplier/Supplier_payment_info_model');
        $this->load->model('finance/purchase_order_pay_type_model');
        $this->load->model('purchase/Delivery_model');
        $this->load->model('purchase_suggest/purchase_suggest_map_model');
        $this->load->model('purchase_suggest/purchase_suggest_model');
        $this->load->helper('common');

    }


    /**
     * 1688下单预览订单接口（单个）
     * @author Jolon
     * @param  string $purchase_number 采购单号
     * @param array   $ali_sku_amount  二次预览订单所提交的SKU下单数量（针对打不开预览页面后，打开编辑页面，修改数量再次预览）
     * @return array
     */
    public function order_preview($purchase_number,$ali_sku_amount = []){
        $return = ['code' => false,'message' => '','data' => []];

        $result = $this->get_preview_order_info($purchase_number,false,$ali_sku_amount);
        if($result['code']){
            $first_ali_account = !empty($result['data']['user_ali_account'])?current($result['data']['user_ali_account']):'';
            $param_list        = $result['data']['cargo_param_list']['cargoParamList'];
            $cargoParamsResult = $errorParamsResult = $this->convertCargoParamList($param_list);
            $cargoParamsResult = array_values($cargoParamsResult);
            $result['data']['cargo_param_list']['cargoParamList'] = $cargoParamsResult;

            $preview_data      = $this->aliorderapi->createOrderPreview($first_ali_account,$result['data']['cargo_param_list']);// 创建订单前预览接口
            $check_return      = $this->convertCreateOrderPreview($preview_data,$errorParamsResult,$ali_sku_amount);// 验证下单预览结果
            if($check_return['code'] === false){// 直接报错
                $return['message'] = $check_return['message'];
                return $return;
            }elseif($check_return['passed_preview'] == 0){// 预览失败
                $preview_data['data']['orderPreviewResuslt'][0]['tradeModelList'] = [];// 构造 空值数据 跳过下面的数据验证
                $return['message'] = $check_return['message'];
            }
            $passed_preview      = $check_return['passed_preview'];
            $tradeModelList      = $check_return['tradeModelList'];// 支持的交易方式

            $orderPreviewResuslt = isset($preview_data['data']['orderPreviewResuslt'])?$preview_data['data']['orderPreviewResuslt']:'';
            $orderPreviewResuslt = $orderPreviewResuslt[0];// 订单信息

            // 创建订单前预览数据
            $ali_order_data = [
                'link_me'              => $this->Wangwang($result['data']['supplier_code']),// 和我联系
                'addressId'            => $result['data']['first_buyer_id'],
                'discountFee'          => isset($orderPreviewResuslt['discountFee']) ? $orderPreviewResuslt['discountFee'] /100 : 0,// 优惠额
                'sumCarriage'          => isset($orderPreviewResuslt['sumCarriage']) ? $orderPreviewResuslt['sumCarriage'] / 100 : 0,// 总运费
                'sumPayment'           => isset($orderPreviewResuslt['sumPayment']) ? $orderPreviewResuslt['sumPayment'] / 100 : 0,// 总金额
                'sumPaymentNoCarriage' => isset($orderPreviewResuslt['sumPaymentNoCarriage']) ? $orderPreviewResuslt['sumPaymentNoCarriage'] /100 : 0,// 未付运费
                'additionalFee'        => isset($orderPreviewResuslt['additionalFee']) ? $orderPreviewResuslt['additionalFee']/ 100 : 0,// 附加费用,
                'passed_preview'       => $passed_preview,
                'buyer_note'           => $this->convertOrderBuyerNote($purchase_number,(stripos($purchase_number,'ABD') !== false)?PURCHASE_TYPE_OVERSEA:PURCHASE_TYPE_INLAND),
            ];
            $result['data']['ali_order_data']                    = $ali_order_data;
            $result['data']['down_list_box']['trader_method']    = $tradeModelList;
            $result['data']['down_list_box']['purchase_account'] = $result['data']['user_ali_account'];// 采购员的采购账号 getPurchaseAccountList($buyer_id)
            $result['data']['down_list_box']['address_list']     = $result['data']['address_list'];
            $result['data']['down_list_box']['address_list']     = $result['data']['address_list'];
            $result['data']['cargo_param_list']['cargoParamList'] = $param_list;
            unset($result['data']['address_list'],$result['data']['user_ali_account']);

            $return['code'] = true;
            $return['data'] = $result['data'];
            return $return;
        }else{
            $return['message'] = $result['message'];
            return $return;
        }

    }

    /**
     * 批量检测是否生成采购单
     */
    public function checkPurchaseCreateOrder($purchase_number)
    {
        $data = $this->purchase_db->from($this->table_name)
            ->select("purchase_number")
            ->where_in("purchase_number", $purchase_number)
            ->get()
            ->result_array();
        if(!$data ||count($data) == 0)return [];
        return array_column($data, "purchase_number");
    }

    /**
     * 验证采购单是否已经生成了 1688订单
     * @param $purchase_number
     * @return bool
     */
    public function checkPurOrderExecuted($purchase_number){
        $executed = $this->findOne(['purchase_number' => $purchase_number,'success' => 1,'where_not_in'=>['order_status'=>['CANCEL','TERMINATED']]]);
        if($executed){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 1、验证SKU 下单数量是否满足 最低起订量，不满足则在第一个SKU数量上自动补足
     * 2、自动判断是否是 起订数量异常的订单
     * @param array $sku_list
     * @param int $default_qty  如果SKU没有最小下单数量 则设置默认最小 10个
     * @return mixed
     */
    public function checkOrderQuantityIsMeetMinimum($sku_list,$default_qty = 10){
        $is_qty_abnormal = 0;// 是否是 起订数量异常的订单
        $change_qty_list = [];
        $sku_list_mul_tmp = arrayKeyToColumnMulti($sku_list,'product_id');// 数组转换
        foreach($sku_list_mul_tmp as $product_id => $sku_list_tmp){// 根据 商品分组 计算最小下单数量
            $first_sku_range = $sku_list_tmp[0];
            $first_sku       = $sku_list_tmp[0]['sku'];
            $_first_range    = array_column($first_sku_range['price_range'],'startQuantity');
            sort($_first_range);// 升序
            $startQuantity   = current($_first_range);

            if(empty($startQuantity) or intval($startQuantity) <= 0) $startQuantity = $default_qty;// 设置默认最小下单数量

            $sku_total = array_sum(array_column($sku_list_tmp,'purchase_amount'));

            if($sku_total >= $startQuantity){

            }else{
                $is_qty_abnormal              = 1;
                $diff_quantity                = $startQuantity - $sku_total;
                $change_qty_list[$product_id] = [
                    'sku'             => $first_sku,
                    'diff_quantity'   => $diff_quantity,
                    'actual_quantity' => $sku_list_tmp[0]['purchase_amount'] + $diff_quantity
                ];
            }
        }

        // 把不满足最小下单数量的 SKU 补满数量
        foreach($sku_list as $key => $value){
            foreach($change_qty_list as $p_id => $p_value){
                $sku             = $p_value['sku'];
                $diff_quantity   = $p_value['diff_quantity'];
                $actual_quantity = $p_value['actual_quantity'];
                if($value['sku'] == $sku and $value['product_id'] == $p_id ){
                    $sku_list[$key]['ali_purchase_amount'] = $actual_quantity;// 改变 阿里下单数量
                }
            }
        }

        return array($sku_list,$is_qty_abnormal);
    }

    /**
     * 1688下单预览-下单商品数量合并
     * @param array $param_list 商品列表信息
     * @return array
     */
    public function convertCargoParamList($param_list){
        $cargoParamsResult = [];
        foreach($param_list as $k=>$v){
            $sid = isset($v['spec_id'])?$v['spec_id']:'';
            $spid = isset($v['specId'])?$v['specId']:'';
            $id = $v['offerId']."_".$sid."_".$spid;
            if(!isset($cargoParamsResult[$id])){
                $cargoParamsResult[$id] = $v;
            }else{
                $cargoParamsResult[$id]['quantity'] += $v['quantity'];
            }

            /*
            if(isset($v['spec_id'])) {
                if (!isset($cargoParamsResult[$v['spec_id']])) {
                    $cargoParamsResult[$v['spec_id']] = $v;
                } else {
                    $cargoParamsResult[$v['spec_id']]['quantity'] += $v['quantity'];
                }
            }elseif(isset($v['specId'])) {// 兼容下 specId
                if (!isset($cargoParamsResult[$v['specId']])) {
                    $cargoParamsResult[$v['specId']] = $v;
                } else {
                    $cargoParamsResult[$v['specId']]['quantity'] += $v['quantity'];
                }
            }else{
                if (!isset($cargoParamsResult[$v['offerId']])) {
                    $cargoParamsResult[$v['offerId']] = $v;
                } else {
                    $cargoParamsResult[$v['offerId']]['quantity'] += $v['quantity'];
                }
            }
            */
        }
        return $cargoParamsResult;
    }

    /**
     * 1688下单预览订单结果数据转换
     * @author Jolon
     * @param   array $preview_data      预览订单结果信息系
     * @param array   $errorParamsResult 商品列表
     * @param array   $ali_sku_amount    二次预览订单所提交的SKU下单数量
     * @return array
     */
    public function convertCreateOrderPreview($preview_data,$errorParamsResult,$ali_sku_amount = []){
        $return = ['code' => false,'message' => '','passed_preview' => 0,'tradeModelList' => []];

        if (empty($preview_data)){
            $return['message'] = "未知错误";
            return $return;
        }

        if(isset($preview_data['code']) and $preview_data['code'] === false and !empty($preview_data['errorMsg'])){
            $errorMsgReturn = $this->convert_message_content($preview_data['errorMsg'],$errorParamsResult);
            $return['message'] = !empty($errorMsgReturn)?$errorMsgReturn:$preview_data['errorMsg'];
            return $return;
        }

        if (!isset($preview_data['code']) or $preview_data['code'] != 200){
            $return['message'] = isset($preview_data['msg'])?$preview_data['msg']:$preview_data['errorMsg'];
            return $return;
        }


        // 创建订单  预览接口返回的数据
        $orderPreviewResuslt = isset($preview_data['data']['orderPreviewResuslt'])?$preview_data['data']['orderPreviewResuslt']:[];
        $orderPreviewResuslt = isset($orderPreviewResuslt[0])?$orderPreviewResuslt[0]:[];
        // 支持的交易方式
        $tradeModelList      = isset($orderPreviewResuslt['tradeModelList'])?$orderPreviewResuslt['tradeModelList']:[];
        if(is_array($tradeModelList)){
            $ModelList = [];
            foreach ($tradeModelList as $value){
                if($value['opSupport']){
                    $ModelList[] =$value;
                }
            }
            $tradeModelList = array_column($ModelList,'name', 'tradeType');
        }else{
            $tradeModelList      = array_column($tradeModelList, 'name', 'tradeType');
        }
        $tradeModelList                  = array_diff_key($tradeModelList,['creditBuy' => '诚e赊（免费赊账）']);// 去除交易方式：诚e赊（免费赊账）
        $return['tradeModelList']        = $tradeModelList;


        if (isset($preview_data['data']['success']) and empty($preview_data['data']['success']) ){
            $errorCode = $preview_data['data']['errorCode'];
            $errorMsg = $preview_data['data']['errorMsg'];

            if(!empty($errorMsg) and defined('ALI_PREVIEW_MIN_ORDER_PASSED') and empty($ali_sku_amount)
                and multiStrPos($errorMsg,explode(',',ALI_PREVIEW_MIN_ORDER_PASSED))){// $ali_sku_amount不为空表示是重新预下单

                $return['code']           = true;
                $return['passed_preview'] = 0;
            }elseif(empty($ali_sku_amount) and !empty($errorCode) and in_array($errorCode,[
                    '500_005','500_006','500_004',
                    'QUANTITY_UNMATCH_SELLUNIT_SCALE',
                    'FAIL_BIZ_FAIL_BIZ_UNSUPPORT_MIX',
                    'FAIL_BIZ_FAIL_BIZ_BOOKED_BEYOND_THE_MAX_QUANTITY',
                    'FAIL_BIZ_FAIL_BIZ_BOOKED_LESS_THAN_LEAST_QUANTITY',
                    'FAIL_BIZ_FAIL_BIZ_LESS_THAN_MIX_BEGIN',
                    'FAIL_BIZ_PRODUCT_TRADE_STAT_ERROR'])){// 数量异常、库存不足的错误码

                $return['code']           = true;
                $return['passed_preview'] = 0;
            }else{
                if(is_json($errorMsg)){
                    $errorMsg = json_decode($errorMsg,true);
                    if(isset($errorMsg['errorMessage'])){
                        $errorMsg = $errorMsg['errorMessage'];
                    }
                }
            }

            if(!empty($errorMsg)){
                $errorMsgReturn = $this->convert_message_content($errorMsg,$errorParamsResult);
                $errorMsg = !empty($errorMsgReturn)?$errorMsgReturn:$errorMsg;
            }
            $return['message'] = $errorMsg;
        }else{
            $return['code']           = true;
            $return['message']        = "操作成功";
            $return['passed_preview'] = 1;
        }

        return $return;
    }

    /**
     * 1、1688下单数量即按 【单位对应关系】生成
     * @param array $sku_list
     * @return mixed
     */
    public function convertOrderQuantityByAliRatio($sku_list){
        foreach($sku_list as $product_id => &$sku_value){// 根据 单位对应关系 计算下单数量
            if($sku_value['ali_ratio_own'] and $sku_value['ali_ratio_out']){
                $sku_value['ali_purchase_amount'] = ceil($sku_value['purchase_amount'] * $sku_value['ali_ratio_out'] / $sku_value['ali_ratio_own']);
            }else{
                $sku_value['ali_purchase_amount'] = isset($sku_value['ali_purchase_amount'])?$sku_value['ali_purchase_amount']:$sku_value['purchase_amount'];
            }
        }
        return array($sku_list,1);
    }

    /**
     * 买家留言 - 组装 买家留言信息
     * @author Jolon
     * @param string  $purchase_number  采购单号
     * @param  string $purchase_type_id 业务线
     * @param  string $warehouse_name   采购仓库名称
     * @param  array  $sku_list         SKU信息
     * @param int     $length           指定留言信息长度（超过长度自动截取该长度，后面追加省略符号“...”）
     * @return string
     */
    public function convertOrderBuyerNote($purchase_number,$purchase_type_id,$warehouse_name = '',$sku_list = [],$length = 500){
        //$skus       = array_unique(array_column($sku_list,'sku'));
        //$count      = count($skus);
        $buyer_note = [];
        if(in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){
            $buyer_note[] = "1.请将各订单号{$purchase_number}对应的SKU以及数量/采购单号 /采购仓库 用指定箱唛打印出来贴在外箱上,否则仓库拒收甲方概不负责";
            $buyer_note[] = "2.出货时内附出货清单，用指定的箱唛以及送货单发货";
            $buyer_note[] = "3.不同规格不同颜色的货物不能混装,产品需要单套包装，每套包装须用中性无任何logo字样的内盒或快递袋包装，如果是彩盒或透明袋子包装拒收";
            $buyer_note[] = "4.所有产品都要贴MADE IN CHINA标识";
            $buyer_note[] = "5.所有带电的或者需要通电才能使用的产品要贴CE标以及厂家信息地址电话标识（回邮标）";
            $buyer_note[] = "6.带插头的产品需要配置对应国家规格的插头";
        }else{
            $buyer_note[] = "1.所有快递面单/外箱/送货单务必附有订单号{$purchase_number},且出货时箱内务必附上送货清单(送货清单内容包含:订单号{$purchase_number}，SKU，数量，甲方对应的产品名称)，否则仓库拒收甲方概不负责";
            $buyer_note[] = '2.涉及补货或备品，送货清单/外箱/快递面单上需备注对应所属订单号及“补货”或“备品”字样';
            $buyer_note[] = "3.甲方货品拒绝到付和自提";
        }

        /*$sku_list_note  = $this->getBuyerNoteSkuList($sku_list);
        $buyer_note     = array_merge($buyer_note,$sku_list_note);// 合并SKU明细
        if(mb_strlen($buyer_note) > $length){
            $buyer_note = mb_substr($buyer_note,0,$length - 5);
            $buyer_note .= '...';
        }*/

        $buyer_note     = implode("\n",$buyer_note);

        return $buyer_note;
    }

    /**
     * 买家留言 - 组装 SKU 列表信息
     * @author Jolon
     * @param array $sku_list SKU信息列表(务必传入sku product_name purchase_packaging purchase_amount starting_qty_unit 参数)
     * @return array
     */
    public function getBuyerNoteSkuList($sku_list){
        $note_list = [];
        if($sku_list){
            foreach($sku_list as $value){
                $note   = [];
                $note[] = $value['sku'];
                $note[] = $value['product_name'];
                $note[] = $value['purchase_packaging'];
                $note[] = $value['purchase_amount'];
                $note[] = $value['starting_qty_unit'];

                $note_list[] = implode(" ",$note);// 不支持缩进
            }
        }

        return $note_list;
    }

    /**
     * 获取 采购单的信息（含1688订单信息）
     * @param $purchase_number
     * @return array
     */
    public function get_order_info($purchase_number){
        $return = ['code' => false,'message' => '','data' => ''];

        $purchase_order_info = $this->purchase_order_model->get_one($purchase_number);
        if(empty($purchase_order_info) or empty($purchase_order_info['items_list'])){
            $return['message'] = '采购单或采购单明细缺失';
            return $return;
        }

        $items_list    = $purchase_order_info['items_list'];
        $supplier_code = $purchase_order_info['supplier_code'];
        $supplier_name = $purchase_order_info['supplier_name'];

        // 采购系统采购订单信息
        $my_order_data['purchase_number'] = $purchase_order_info['purchase_number'];
        $my_order_data['supplier_code']   = $purchase_order_info['supplier_code'];
        $my_order_data['supplier_name']   = $purchase_order_info['supplier_name'];
        $my_order_data['link_me']         = 'https://www.baidu.com';// 和我联系

        // 汇总SKU数据
        $total_sku  = $total_sku_qty = $total_price = 0;
        $sku_list   = [];
        foreach($items_list as $item){
            $sku = $item['sku'];

            $now_sku          = [];
                $have_ali_product = $this->Ali_product_model->get_ali_product_one(['sku' => $sku, 'supplier_code' => $supplier_code]);
            if(empty($have_ali_product)){
                $return['message'] = "SKU[$sku]未关联1688供应商的商品或默认供应商已变更,请重新配置采购链接";
                return $return;
            }

            $product_info = $this->purchase_db->where('sku',$sku)->get('product')->row_array();

            $now_sku['sku']                 = $sku;
            $now_sku['product_img_url']     = erp_sku_img_sku($item['product_img_url']);
            $now_sku['product_name']        = $item['product_name'];
            $now_sku['sale_attribute']      = $product_info['sale_attribute'];
            $now_sku['supplier_code']       = $supplier_code;
            $now_sku['supplier_name']       = $supplier_name;
            $now_sku['purchase_amount']     = $item['purchase_amount'];
            $now_sku['purchase_unit_price'] = $item['purchase_unit_price'];
            $now_sku['purchase_item_total'] = $item['purchase_amount']*$item['purchase_unit_price'];

            // 1688 商品相关信息
            $now_sku['ali_supplier_name'] = $have_ali_product['ali_supplier_name'];
            $now_sku['main_image']        = $have_ali_product['main_image'];
            $now_sku['subject']           = $have_ali_product['subject'];
            $now_sku['attribute']         = $have_ali_product['attribute'];
            $now_sku['price_range']       = $this->Ali_product_model->convert_price_range($have_ali_product['price_range'],$have_ali_product['price'],$have_ali_product['min_order_qty']);
            $now_sku['price']             = $have_ali_product['price'];
            $now_sku['product_id']        = $have_ali_product['product_id'];
            $now_sku['spec_id']           = $have_ali_product['spec_id'];
            $now_sku['sku_id']           = $have_ali_product['sku_id'];

            $total_sku     ++;
            $total_sku_qty += $item['purchase_amount'];
            $total_price   += $now_sku['purchase_item_total'];
            $sku_list[]    = $now_sku;
        }

        $purchase_order['my_order_data']    = $my_order_data;
        $purchase_order['ali_order_data']   = [];// 1688 采购单信息
        $purchase_order['items_list']       = $sku_list;
        $return['code'] = true;
        $return['data'] = $purchase_order;
        return $return;
    }

    /**
     * 获取 1688下单-预览数据   1688下单确认-预览数据
     * @param      $purchase_number
     * @param bool $is_confirm
     * @param array $ali_sku_amount 设定预下单数量
     * @return array
     */
    public function get_preview_order_info($purchase_number,$is_confirm = false,$ali_sku_amount = null){
        $return = ['code' => false,'message' => '','data' => ''];
        $purchase_order_info = $this->purchase_order_model->get_one($purchase_number);

        if(empty($purchase_order_info) || empty($purchase_order_info['items_list'])){
            $return['message'] = '采购单或采购单明细缺失';
            return $return;
        }

        if (!$this->Supplier_payment_info_model->check_support_alipay($purchase_order_info['supplier_code'],$purchase_order_info['is_include_tax'],$purchase_order_info['purchase_type_id'])){
            $return['message'] = '采购单号:'.$purchase_order_info['purchase_number'].'供应商无支付宝支付方式，不支持1688下单';
            return $return;
        }
        //根据PO号查询备货单验备是退税，不允许进行1688下单
        if($purchase_order_info['is_drawback'] == PURCHASE_IS_DRAWBACK_Y){
            $return['message'] = '采购单是否退税=是，不允许进行1688下单';
            return $return;
        }
        $demand  = $this->select_demand_is_drawback($purchase_order_info['purchase_number']);
        if(!empty($demand)){
            $return['message'] = '备货单号'.$demand.'，是退税，不允许进行1688下单';
            return $return;
        }
        /*if($purchase_order_info['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE){

            if ($purchase_order_info['is_ali_abnormal'] != 1 ){
                $return['message'] = '只有【等待采购询价】状态才能【1688】下单';
                return $return;
            }

            if ($purchase_order_info['is_ali_abnormal'] == 1 && !in_array($purchase_order_info['purchase_order_status'],[PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT])){
                $return['message'] = '1688异常订单,只有【等待到货】【信息修改驳回】状态才能【1688】下单';
                return $return;
            }

            if ($purchase_order_info['is_ali_abnormal'] == 1){
                if(($check_res2 = $this->Purchase_order_model->check_pay_status_able_change($purchase_order_info['pay_status'])) !== true){
                    $return['message'] = $check_res2;
                    return $return;
                }
            }

        }*/

        $user_ali_account = $this->aliaccount->getSubAccountOneByUserId($purchase_order_info['buyer_id']);
        if(empty($user_ali_account)){
            $return['message'] = '采购员未配置采购账号';
            return $return;
        }


        //添加仓库显示 20190703
        $warehouse_name ='';
        $ware_code = $purchase_order_info['warehouse_code'];
        if(!empty($ware_code)){
            $warehouse_name = $this->warehouse_model->get_warehouse_one($ware_code, 'warehouse_name');
        }
//        if( !empty($ware_code) ) {
//            $warehouse_message = $this->warehouse_model->get_warehouse_address_message($ware_code);
//            $warehouse_name = $warehouse_message['region_name'].$warehouse_message['city_name'].$warehouse_message['region_name'].$warehouse_message['count_name'].$warehouse_message['address'];
//        }
//        $userInfo  = $this->purchase_order_model->get_access_purchaser_information($purchase_number);
        $userInfo = $this->get_purchase_user_info($purchase_order_info['buyer_id']);
        $warehouse_address = $this->warehouse_model->get_warehouse_address();

        $warehouse_address_list = array();
        if(!empty($warehouse_address)){
            //收货信息
            foreach ($warehouse_address as $val){
                $contact_number = isset($userInfo['phone_number'])?$userInfo['phone_number']:'';
                if(!empty($val['contact_number']) ){
                    $contact_number = $val['contact_number'];
                }
                if($ware_code != $val['warehouse_code']){
                    continue;// 只保留 与采购单对应的仓库地址
                }
                // contact_number
                $user_name = isset($userInfo['user_name'])?preg_replace("/\\d+/", '', $userInfo['user_name']):'';
                $warehouse_address_list[] = array(
                    'warehouse_code' => $val['warehouse_code'],
                    'warehouse_name' => isset($val['warehouse_name'])?$val['warehouse_name']:'',
                    'province_text' => $val['province_text'],
                    'city_text' => $val['city_text'],
                    'area_text' => $val['area_text'],
                    'town_text' => $val['town_text'],
                    'address' => $purchase_number.' '.$val['address'],
                    'post_code' => $val['post_code'],
                    'contacts' => $user_name.' '.$val['contacts'],
                    'contact_number' => $contact_number,
                );
            }
        }
        $items_list    = $purchase_order_info['items_list'];
        $supplier_code = $purchase_order_info['supplier_code'];
        $supplier_name = $purchase_order_info['supplier_name'];
        //供应商结算方式
        $account_type = $purchase_order_info['account_type'];
        //供应商支付方式

        //如果是临时供应商显示该供应商的下单次数
        $supplier_info = $this->supplier_model->get_supplier_info($supplier_code);

        if (!empty($supplier_info) && $supplier_info['supplier_source'] == 3) {//临时供应商且下单量>0
            $order_num = $this->Purchase_order_model->set_temporary_supplier_order_number($supplier_code);
        }

        // 采购系统采购订单信息
        $my_order_data['purchase_number'] = $purchase_order_info['purchase_number'];
        $my_order_data['supplier_code']   = $purchase_order_info['supplier_code'];
        $my_order_data['shipment_type']   = $purchase_order_info['shipment_type'];
        // 34415
        $my_order_data['is_postage']      = isset($supplier_info['is_postage']) && $supplier_info['is_postage'] == 1? true:false;
        $my_order_data['supplier_name']   = $purchase_order_info['supplier_name'];
        $my_order_data['buyer_id']        = $purchase_order_info['buyer_id'];
        $my_order_data['buyer_name']      = $purchase_order_info['buyer_name'];
        $my_order_data['is_qty_abnormal'] = 0;// 数量异常订单
        $my_order_data['is_ali_abnormal'] = $purchase_order_info['is_ali_abnormal'];// 1688订单是否异常(1688订单已关闭的,且采购单状态为等待到货,信息修改驳回)0.否;1.是
        $my_order_data['is_disabled']     = 0;// 是否禁用修改操作
        $my_order_data['warehouse_name']  = $warehouse_name;
        $my_order_data['account_type']    = $account_type;


        $is_expedited_ch = '否';
        if( isset($purchase_order_info['is_expedited']) ) {
            if( $purchase_order_info['is_expedited'] == 1) {
                $is_expedited_ch = '否';
            }else if( $purchase_order_info['is_expedited'] == 2) {
                $is_expedited_ch = "是";
            }
        }
        $my_order_data['is_expedited_ch'] = $is_expedited_ch;//是否加急

        // 汇总SKU数据
        $total_sku = $total_sku_qty = $total_price = $product_weight =0;
        $sku_list  = [];
        foreach($items_list as $item){
            $sku              = $item['sku'];
            $now_sku          = [];
            //
            $have_ali_product = $this->Ali_product_model->get_ali_product_one(['sku' => $sku, 'supplier_code' => $supplier_code]);
            if(empty($have_ali_product) and empty($is_confirm)){// $is_confirm=true下单确认的时候不用报错，因为已经下单成功了
                $return['message'] = "SKU[$sku]对应的供应商:".$supplier_code.",未关联1688或默认供应商已变更,请重新关联1688";
                return $return;
            }

            $product_info = $this->purchase_db->where('sku',$sku)->get('product')->row_array();

            $now_sku['sku']                 = $sku;
            $now_sku['product_img_url']     = erp_sku_img_sku($item['product_img_url']);
            $now_sku['product_name']        = $item['product_name'];
//            $now_sku['sale_attribute']      = $product_info['sale_attribute'];
            $now_sku['sale_attribute']      = $this->Ali_product_model->convert_sale_attribute($product_info['sale_attribute']);// 销售属性
            $now_sku['supplier_code']       = $supplier_code;
            $now_sku['product_weight']       = $product_info['product_weight'];
            $now_sku['supplier_name']       = $supplier_name;
            if (!empty($order_num)) {
                $now_sku['order_num']       = $order_num;

            }

            if ($purchase_order_info['is_ali_abnormal'] == 1){//1688异常,二次下单只能下已确认的单
                $now_sku['purchase_amount']     = $item['confirm_amount'];
            }else{
                $now_sku['purchase_amount']     = $is_confirm? $item['confirm_amount']:$item['purchase_amount'];
            }

            $product_weight += format_price($now_sku['purchase_amount'] * $product_info['product_weight']);
            $now_sku['purchase_unit_price'] = $item['purchase_unit_price'];
            $now_sku['purchase_item_total'] = format_price($now_sku['purchase_amount']*$item['purchase_unit_price']);
            $now_sku['product_cn_link']     = $product_info['product_cn_link'];//添加采购连接
            $now_sku['starting_qty']        = $product_info['starting_qty'];//采购最小起订量
            $now_sku['starting_qty_unit']   = $product_info['starting_qty_unit'];//采购最小起订量
                // 1688 商品相关信息
            $now_sku['ali_supplier_name'] = $have_ali_product['ali_supplier_name'];
            $now_sku['main_image']        = $have_ali_product['main_image'];
            $now_sku['subject']           = $have_ali_product['subject'];
            $now_sku['attribute']         = $have_ali_product['attribute'];
            $now_sku['price_range']       = $this->Ali_product_model->convert_price_range($have_ali_product['price_range'],$have_ali_product['price'],$have_ali_product['min_order_qty']);
            $now_sku['price']             = $have_ali_product['price'];
            $now_sku['product_id']        = $have_ali_product['product_id'];
            $now_sku['spec_id']           = $have_ali_product['spec_id'];
            $now_sku['ali_purchase_amount'] = isset($ali_sku_amount[$sku])?$ali_sku_amount[$sku]:$now_sku['purchase_amount'];// $ali_sku_amount重新预下单数量
            $now_sku['ali_min_order_qty']       = $have_ali_product['min_order_qty'];//1688最小起订量
            $now_sku['ali_min_order_qty_unit']  = $have_ali_product['min_order_qty_unit'];//1688最小起订量
            $now_sku['price_total']       = format_price($now_sku['ali_purchase_amount']*$now_sku['price']);
            $now_sku['ali_ratio_own']     = $product_info['ali_ratio_own'];// 单位对应关系
            $now_sku['ali_ratio_out']     = $product_info['ali_ratio_out'];// 单位对应关系
            //业务线加进去
            $now_sku['purchase_type_id']  = $item['purchase_type_id'];

            $total_sku     ++;
            $total_sku_qty += $now_sku['purchase_amount'];
            $total_price   += $now_sku['purchase_item_total'];
            $sku_list[]    = $now_sku;
        }
        $product_weight = $product_weight / 1000;
        $my_order_data['total_sku']     = $total_sku;
        $my_order_data['total_sku_qty'] = $total_sku_qty;
        $my_order_data['total_price']   = format_price($total_price);
        $my_order_data['product_weight']   = format_price($product_weight);

        if($is_confirm){// 获取采购单确认信息
            $this->load->model('purchase/Purchase_order_transport_model');
            $this->load->model('finance/purchase_order_pay_type_model');
            $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);
            if($have_pay_type){
                $my_order_data['freight']      = $have_pay_type['freight'];
                $my_order_data['discount']     = $have_pay_type['discount'];
                $my_order_data['process_cost'] = $have_pay_type['process_cost'];
                $my_order_data['freight_note'] = $have_pay_type['freight_note'];
            }else{
                $return['message'] = '获取采购单确认信息失败';
                return $return;
            }
            $reference_freight = $this->Purchase_order_transport_model->get_calculate_order_reference_freight(['purchase_number' => $purchase_number]);
            if($reference_freight['code'] === false){
                $my_order_data['reference_freight']     = null;
                $my_order_data['reference_freight_msg'] = $reference_freight['message'];
            }else{
                $my_order_data['reference_freight']     = format_two_point_price($reference_freight['data']);
                $my_order_data['reference_freight_msg'] = $reference_freight['message'];
            }
        }

        if(!empty($ali_sku_amount)){
            // 用户自己设定的 预下单数量

        }else{
            //// 验证SKU 下单数量是否满足 最低起订量，不满足则在第一个SKU数量上自动补足
            //list($sku_list,$is_qty_abnormal) = $this->checkOrderQuantityIsMeetMinimum($sku_list);

            // 验证SKU 下单数量是否满足 最低起订量，不满足则在第一个SKU数量上自动补足
            list($sku_list,$is_qty_abnormal) = $this->convertOrderQuantityByAliRatio($sku_list);

            $my_order_data['is_qty_abnormal'] = 1;//1688下单时下单数量可修改
        }

        // 组装返回的数据
        $purchase_order['my_order_data']    = $my_order_data;
        $purchase_order['items_list']       = $sku_list;
        if(empty($is_confirm)){  // 1688下单-预览数据
            //买家获取保存的收货地址信息列表
            $address_data = $this->aliorderapi->getTradeByReceiveAddress($user_ali_account['account']);

            if(empty($address_data['code'])){
                $return['message'] = $address_data['errorMsg'];
                return $return;
            }
            if(empty($address_data['data'])){
                $return['message'] = '获取1688账号收货人地址失败';
                return $return;
            }
            $address_data = isset($address_data['data'])?$address_data['data']:[];
            //这里收货人和地址默认选第一个
            $address_list = [];
            $first_buyer_id =  null;
            // 再次组装地址列表
            foreach($address_data as $address){
                if(empty($first_buyer_id)) $first_buyer_id = $address['id'];
                $address_list[$address['id']] = $address['addressCodeText'].$address['townName'].$address['address'];//收货地址
            }

            // 组装 1688订单数据
            $preview_form_data = $this->get_preview_format_data($first_buyer_id, '', $sku_list, $supplier_code);
            if(empty($preview_form_data['code'])){
                $return['message'] = $preview_form_data['message'];
                return $return;
            }

            $purchase_order['first_buyer_id']   = $first_buyer_id;
            $purchase_order['cargo_param_list'] = $preview_form_data['data'];
            $purchase_order['address_list']     = $address_list;
            $purchase_order['supplier_code']    = $supplier_code;
            $purchase_order['note'] =' （外箱必贴好唛头，送货清单随货走，需要有SKU，采购员，PO号，否则会被拒收）';
            $purchase_order['user_ali_account'] = [$user_ali_account['account'] => $user_ali_account['account']];
            $purchase_order['warehouse_address_list'] = $warehouse_address_list;

        }else{
            if(in_array($purchase_order_info['purchase_order_status'],[PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT])){
                $purchase_order['my_order_data']['is_disabled'] = 1;// 审核中的不能执行修改操作
            }

            // 1688下单确认-预览数据
            // 获取1688订单信息
            $purchase_account   = isset($have_pay_type['purchase_acccount'])?$have_pay_type['purchase_acccount']:'';
            $pai_number         = isset($have_pay_type['pai_number'])?$have_pay_type['pai_number']:'';
            $ali_order_data     = $this->get_ali_order_info(null,$pai_number);

            if(empty($ali_order_data['code'])){
                $return['message'] = $ali_order_data['message'];
                return $return;
            }
            $baseInfo           = isset($ali_order_data['data']['baseInfo'])?$ali_order_data['data']['baseInfo']:[];
            $receiverInfo       = isset($baseInfo['receiverInfo'])?$baseInfo['receiverInfo']:[];
            $productItems       = isset($ali_order_data['data']['productItems'])?$ali_order_data['data']['productItems']:[];
            $total_quantity     = array_column($productItems, 'quantity');//不需要specId：不同productId可以有相同的specId，导致计数失败
            $total_quantity     = array_sum($total_quantity);
            $ali_order_data_tmp = [
                'link_me'            => $this->Wangwang($supplier_code),// 和我联系
                'order_id'           => $pai_number,
                'address'            => isset($receiverInfo['toArea'])?$receiverInfo['toArea']:'',
                'purchase_account'   => $purchase_account,
                'buyer_note'         => isset($baseInfo['remark'])?$baseInfo['remark']:'',
                'tradeType'          => isset($baseInfo['tradeType'])?getTradeType($baseInfo['tradeType']):'',
                'tradeTypeDesc'      => isset($baseInfo['tradeTypeDesc'])?$baseInfo['tradeTypeDesc']:'',
                'oldTotalAmount'     => $this->get_ali_order_old_price($purchase_number),
                'currentTotalAmount' => isset($baseInfo['totalAmount'])?$baseInfo['totalAmount']:'',
                'shippingFee'        => isset($baseInfo['shippingFee'])?$baseInfo['shippingFee']:'',
                'discount'           => isset($baseInfo['discount'])?$baseInfo['discount']:'',
                'status'             => isset($baseInfo['status'])?$baseInfo['status']:'',
                'quantity'           => $total_quantity,

            ];

            $purchase_order['ali_order_data'] = $ali_order_data_tmp;
        }
        $purchase_order['down_list_box']['pay_type'] = getPayType();

        $return['code'] = true;
        $return['data'] = $purchase_order;
        return $return;
    }

    /**
     * 获取旺旺客服聊天
     * @param string $supplier_code
     * @author 2019-5-8
     * @return string
     */
    public function Wangwang($supplier_code){
        $like_me_url = $this->rediss->getData('like_me_'.$supplier_code);
        if(empty($like_me_url)){
            $wangwang = $this->purchase_db->select('want_want')->where('supplier_code', $supplier_code)->get('supplier_contact')->row_array();
            if(empty($wangwang) || empty($wangwang['want_want'])){
                return '';
            }
            $uid  = urlencode($wangwang['want_want']);
            $html = '<a target="_blank" href="'.WANG_IP.$uid.'&site=cnalichn&s=10&charset=UTF-8" ><img border="0" src="'.WANG_IP_IMG.$uid.'&site=cntaobao&s=1&charset=utf-8" alt="点击这里给我发消息" /></a>';
            $this->rediss->setData('like_me_'.$supplier_code,base64_encode($html),600);
        }else{
            $html = base64_decode($like_me_url);
        }
        return $html;
    }

    /**
     * 1688下单-确认提交到1688下单
     * @param $data
     * @return array
     */
    public function do_one_key_order($data){
        $return = ['code' => false,'message' => '','data' => ''];

        $purchase_number  = $data['purchase_number'];
        $order_discount   = $data['order_discount'];
        $order_freight    = $data['order_freight'];
        $order_process_cost = $data['order_process_cost'];
        $sku_amount       = $data['sku_amount'];
        $ali_sku_amount   = $data['ali_sku_amount'];
        $ali_ratio_list   = $data['ali_ratio_list'];
        $buyer_note       = $data['buyer_note'];
        $trader_method    = $data['trader_method'];
        $purchase_account = $data['purchase_account'];
        $address_id       = $data['address_id'];

        $modify_remark    = $data['modify_remark'];
        $freight_note     = $data['freight_note'];
        // 20190702 添加
        $full_name   = $data['full_name'];   //收货人姓名
        $mobile      = $data['mobile'];   //手机
        $phone       = $data['phone'];   //电话
        $area        = $data['area'];   //区
        $province    = $data['province'];   //省
        $town        = $data['town'];//镇
        $address     = $data['address'];   //街道地址
        $city        = $data['city'];   //
        $postCode    = $data['post_code'];


        if($this->checkPurOrderExecuted($purchase_number)){
            $return['message'] = "采购单[$purchase_number]已经生成了1688订单，请勿重复下单";
            return $return;
        }
        // $this->purchase_db->trans_begin();
        try{
            $result = $this->get_order_info($purchase_number);
            if(empty($result['code'])) throw new Exception($result['message']);

            $purchase_order             = $result['data'];
            $purchase_order['discount'] = $order_discount;
            $purchase_order['freight']  = $order_freight;
            $purchase_order['process_cost'] = $order_process_cost;


            // 组装数据为 1688订单数据
            $ali_order                 = [];
            $ali_order['addressId']    = '';
            $ali_order['provinceText'] = $province;
            $ali_order['cityText']     = $city;
            $ali_order['areaText']     = $area;
            $ali_order['townText']     = $town;
            $ali_order['fullName']     = $full_name;
            $ali_order['mobile']       = $mobile;
            $ali_order['postCode']     = $postCode;
            $ali_order['districtCode'] = '';
            $ali_order['tradeType']    = $trader_method;
            $ali_order['message']      = $buyer_note;
            //添加 20190702
            $ali_order['phone']     = $phone; //电话
            $ali_order['address']   = $address; //街道地址

            $purchase_sku_change = [];// 变更的SKU采购数量
            $purchase_sku_old    = [];// SKU备货数量
            $cargoParams         = [];
            $product_money       = 0;
            $purchase_modify_remark_change = [];//添加的备注
            $purchase_unit_price = [];//单价
            if($purchase_order['items_list']){
                $cargoParamsResult = array();
                $this->load->model('product/Product_model');
                $this->load->model('product/Product_update_log_model');
                foreach($purchase_order['items_list'] as $item){
                    $sku        = $item['sku'];
                    $new_amount = isset($sku_amount[$sku])?$sku_amount[$sku]:0;
                    $new_ali_amount = isset($ali_sku_amount[$sku])?$ali_sku_amount[$sku]:0;
                    $new_modify_remark = isset($modify_remark[$sku])?$modify_remark[$sku]:'';
                    $new_ali_ratio = isset($ali_ratio_list[$sku])?$ali_ratio_list[$sku]:[];
                    if(empty($new_amount)) throw new Exception("SKU[$sku]采购数量[$new_amount]缺失");
                    if(empty($new_ali_amount)) throw new Exception("SKU[$sku]下单数量[$new_amount]缺失");

                    // 验证 单位对应关系 是否修改，是否存在SKU单位对应关系变更的待审核的记录
                    $skuInfo = $this->Product_model->get_product_info($sku);
                    if($new_ali_ratio and ($new_ali_ratio['ali_ratio_own'] != $skuInfo['ali_ratio_own'] or $new_ali_ratio['ali_ratio_out'] != $skuInfo['ali_ratio_out'])){
                        $skuLatestAuditInfo = $this->purchase_db->where('sku',$sku)
                            ->where_not_in('audit_status',[3,4])// 审核中的
                            ->where_in("old_ali_ratio_own!=new_ali_ratio_own or old_ali_ratio_out!=new_ali_ratio_out")// sku的修改单位对应关系的数据
                            ->order_by('create_time','desc')
                            ->get('product_update_log')
                            ->row_array();
                        if($skuLatestAuditInfo) throw new Exception("SKU[$sku]产品列表中正在修改对应关系，且处理审核中。请直接前往产品管理模块申请！");
                        $this->Product_model->update_one($sku,['ali_ratio_own' => $new_ali_ratio['ali_ratio_own'],'ali_ratio_out' => $new_ali_ratio['ali_ratio_out']]);
                    }
                    //判断修改数量是变大还是变小,允许修改下单数量
                    if($new_amount > $item['purchase_amount'] or $new_amount < 0) throw new Exception("SKU[$sku]下单数量只能往小修改且必须大于0");
                    if( ($new_amount != $item['purchase_amount']) && empty($new_modify_remark) ) throw new Exception("SKU[$sku]采购数量修改,备注必填");
                    if(false and $skuInfo['is_equal_sup_id'] == 2) throw new Exception("SKU[$sku]的采购链接的店铺ID与供应商管理的店铺ID不一致，无法下单");// 屏蔽供应商ID不一致

                    // 数量发生改变  需要更新采购单
                    $purchase_sku_change[$sku]           = $new_amount;
                    $purchase_sku_old[$sku]              = $item['purchase_amount'];
                    $purchase_modify_remark_change[$sku] = $new_modify_remark;
                    $purchase_unit_price[$sku]           = $item['purchase_unit_price'];

                    $sum_id = $item['sku_id'].'_'.$item['product_id'].'_'.$item['spec_id'];

                    if(trim($item['product_id']) != trim($item['spec_id'])){
                        $cargoParams= [
                            'offerId'  => $item['product_id'],
                            'specId'   => $item['spec_id'],
                            'quantity' => $new_ali_amount,
                        ];
                    }else{
                        $cargoParams = [
                            'offerId'  => $item['product_id'],
                            'quantity' => $new_ali_amount,
                        ];
                    }
                    // 相同 product_id sku_id spec_id 合并下单
                    if (!isset($cargoParamsResult[$sum_id])) {
                        $cargoParamsResult[$sum_id] = $cargoParams;
                    } else {
                        $cargoParamsResult[$sum_id]['quantity']+= $new_ali_amount;
                    }
                    $product_money += $item['purchase_unit_price'] * $new_amount;
                }
            }else{
                throw new Exception("采购单明细缺失");
            }

            // 更新  采购确认数量与备注
            foreach($purchase_sku_change as $sku => $amount){
                $res = $this->purchase_db->update('purchase_order_items',['confirm_amount' => $amount,'modify_remark'=>$purchase_modify_remark_change[$sku]],['purchase_number' => $purchase_number,'sku' => $sku],1);
                if(empty($res)){
                    throw new Exception("更新采购单SKU确认数量失败");
                }

                $suggest_map = $this->purchase_suggest_map_model->get_one_by_sku($purchase_number,$sku,'',true);

                if(!empty($suggest_map)){
                    if($purchase_sku_old[$sku] != $amount){
                        $suggest_info = $this->purchase_suggest_model->get_one(0,$suggest_map['demand_number']);
                        $update_data['sales_note'] = $suggest_info['sales_note'].' '.$purchase_modify_remark_change[$sku];
                        $update_data['is_abnormal'] = SUGGEST_ABNORMAL_TRUE;
                        $result5 = $res = $this->purchase_db->where('demand_number',$suggest_map['demand_number'])->update('purchase_suggest', $update_data);
                        if(empty($result5)){
                            throw new Exception("采购单明细[$purchase_number - $sku]备货单[".$suggest_map['demand_number']."]更新失败");
                        }

                        $res2 = $this->purchase_db->update('purchase_suggest_map',
                                                           ['confirm_number' => $amount,
                                                            'purchase_total_price' => $amount*$purchase_unit_price[$sku]],
                                                           ['id' => $suggest_map['id']]);
                        if(empty($res2)) throw new Exception("采购单明细[$purchase_number - $sku]备货单[".$suggest_map['demand_number']."]同步采购确认数量失败");
                    }
                }else{
                    throw new Exception("采购单明细[$purchase_number - $sku]未找到关联的备货单");
                }
            }

            $cargoParams = array_values($cargoParamsResult);
            $ali_order['cargoParams']  = $cargoParams;

            // 2021/01/19 优化 提交前检测10秒钟内如果已下过单，则不能继续下单
            $lock_str = 'CREATE_ALI_ORDER_LOCK_';
            $order_lock = $this->rediss->getData($lock_str.$purchase_number);
            if($order_lock && !empty($order_lock) && $order_lock == $purchase_number)throw new Exception("生成失败，订单重复！");
            $this->rediss->setData($lock_str.$purchase_number, $purchase_number, 10);

            $ali_result = $this->aliorderapi->createCrossOrder($purchase_account,$ali_order);
            // 创建订单成功
            if($ali_result and $ali_result['code'] and isset($ali_result['data']['orderId'])){
                $ali_result         = $ali_result['data'];
                $totalSuccessAmount = $ali_result['totalSuccessAmount'];
                $orderId            = $ali_result['orderId'];
                $postFee            = $ali_result['postFee'];
                $accountPeriod      = isset($ali_result['accountPeriod']) ? $ali_result['accountPeriod'] : [];
                $failedOfferList    = isset($ali_result['failedOfferList']) ? $ali_result['failedOfferList'] : [];

                operatorLogInsert(
                    [
                        'id'      => $purchase_number,
                        'type'    => $this->table_name,
                        'content' => '创建ALI订单成功',
                        'detail'  => $ali_result,
                    ]);

                // 保存创建1688订单记录
                $ali_order['order_id']                   = $orderId;
                $ali_order['purchase_account']           = $purchase_account;
                $ali_order['post_fee']                   = $postFee;
                $ali_order['total_success_amount']       = $totalSuccessAmount;
                $ali_order['trader_method']              = $trader_method;
                $ali_order['is_account_period']          = $accountPeriod ? 1 : 0;
                $ali_order['account_period_tap_type']    = isset($accountPeriod['tapType']) ? $accountPeriod['tapType'] : '';
                $ali_order['account_period_tap_date']    = isset($accountPeriod['tapDate']) ? $accountPeriod['tapDate'] : '';
                $ali_order['account_period_tap_overdue'] = isset($accountPeriod['tapOverdue']) ? $accountPeriod['tapOverdue'] : '';
                $ali_order['failed_offer_list']          = $failedOfferList ? json_encode($failedOfferList, JSON_UNESCAPED_UNICODE) : '';
                $this->save_ali_order($purchase_number,$ali_order);

                // 更新 采购单-1688相关状态
                $update_order_data = [
                    'is_ali_order'    => 1,
                    'is_ali_abnormal' => 0,
                    'pay_type'        => PURCHASE_PAY_TYPE_ALIPAY,
                    'source'          => SOURCE_NETWORK_ORDER,
                ];
                if($accountPeriod){// 更新采购单、备货单结算方式为 1688账期
                    $update_order_data['account_type'] = SUPPLIER_SETTLEMENT_CODE_TAP_DATE;
                }else{// 更新采购单、备货单结算方式为 款到发货
                    $update_order_data['account_type'] = 10;
                }
                $demand_number_arr = $this->purchase_suggest_map_model->get_demand_number_list($purchase_number);//查询备货单号
                $this->purchase_suggest_model->update_suggest_account_type($demand_number_arr,$update_order_data['account_type']);
                $this->purchase_suggest_model->update_suggest_pay_type($demand_number_arr,$update_order_data['pay_type']);
                $this->Purchase_order_model->update_order_ali_status($purchase_number,$update_order_data);

                $purchase_sku_change_tmp[$purchase_number] = $purchase_sku_change;
                // 分摊 优惠额 和 运费
                /*
                $result = $this->update_item_data($purchase_sku_change_tmp, $order_discount, $order_freight, $order_process_cost);
                if(empty($result)){
                    throw new Exception("采购单优惠额和运费分摊失败");
                }
                */

                // 插入  采购单确认记录
                $this->load->model('finance/purchase_order_pay_type_model');
                $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);
                $user_name = getActiveUserName();
                $user_name = !empty($user_name)?$user_name:' ';

                if($have_pay_type){
                    $update = [
                        'product_money'     => floatval($product_money),
                        'freight'           => floatval($order_freight),
                        'discount'          => floatval($order_discount),
                        'process_cost'      => floatval($order_process_cost),
                        'real_price'        => floatval($product_money + $order_freight - $order_discount + $order_process_cost + $have_pay_type['commission']),
                        'settlement_ratio'  => '100%',
                        'purchase_acccount' => $purchase_account,
                        'pai_number'        => $orderId,
                        'accout_period_time'=> '0000-00-00 00:00:00',
                        'is_request'        => 0,
                        'express_no'        => '',
                        'cargo_company_id'  => '',
                        'is_push_express'   => 0,
                        'freight_note'      => !empty($freight_note)?$freight_note.'_'.$user_name.'_'.date("Y-m-d H:i:s",time()):'',
                    ];
                    $result = $this->purchase_order_pay_type_model->update_one($have_pay_type['id'], $update);
                }else{
                    $update = [
                        'purchase_number'   => $purchase_number,
                        'product_money'     => floatval($product_money),
                        'freight'           => floatval($order_freight),
                        'discount'          => floatval($order_discount),
                        'process_cost'      => floatval($order_process_cost),
                        'real_price'        => floatval($product_money + $order_freight - $order_discount + $order_process_cost),
                        'settlement_ratio'  => '100%',
                        'purchase_acccount' => $purchase_account,
                        'pai_number'        => $orderId,
                        'express_no'        => '',
                        'freight_note'      => !empty($freight_note)?$freight_note.'_'.$user_name.'_'.date("Y-m-d H:i:s",time()):'',
                    ];
                    $result = $this->purchase_order_pay_type_model->insert_one($update);
                }
                if(empty($result)){
                    throw new Exception("采购单确认记录保存失败");
                }
            }else{
                $errorMsg       = $ali_result['errorMsg'];
                $errorMsgReturn = $this->convert_message_content($errorMsg);
                $errorMsg       = !empty($errorMsgReturn)?$errorMsgReturn:$errorMsg;
                throw new Exception($errorMsg);
            }

            //$this->purchase_db->trans_commit();

            $return['code'] = true;
        }catch(Exception $e){
            //$this->purchase_db->trans_rollback();
            $return['message'] = $e->getMessage();

        }
        return $return;
    }

    /**
     * 1688下单-不预览直接下单到1688平台
     * @param $purchase_number
     * @return array
     */
    public function one_key_order_not_preview($purchase_number){
        $flagMsg = '';
        $note    = ' （外箱必贴好唛头，送货清单随货走，需要有SKU，采购员，PO号，否则会被拒收）';
        try{
            $warehouse_address_list = $this->warehouse_model->get_warehouse_address();
            $warehouse_address_list = arrayKeyToColumn($warehouse_address_list,'warehouse_code');

            // 按照流程完成各种数据验证
            // 1.拦截非法订单状态
            $purchase_order_info = $this->Purchase_order_model->get_one($purchase_number);
            if(empty($purchase_order_info) or empty($purchase_order_info['items_list'])){
                throw new Exception('采购单或采购单明细缺失');
            }
            if($purchase_order_info['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE){
                throw new Exception('只有【等待采购询价】状态才能【1688】下单');
            }

            if($purchase_order_info['is_drawback'] == PURCHASE_IS_DRAWBACK_Y){
                throw new Exception('采购单是否退税=是，不允许进行1688下单');
            }
            $demand  = $this->select_demand_is_drawback($purchase_number);
            if(!empty($demand)){
                throw new Exception('备货单号'.$demand.'，是退税，不允许进行1688下单');
            }

            $scree_result = $this->Purchase_order_model->check_order_is_scree_with_sku($purchase_number);
            if($scree_result !== false) { throw new Exception('中 存在SKU：'.$scree_result.' 属于屏蔽申请中'); }

            // 2.验证供应商支付方式、结算方式
            $supplierBaseInfo    = $this->Supplier_model->get_supplier_info($purchase_order_info['supplier_code'],false);
            $supplierPaymentInfo = $this->Supplier_payment_info_model->check_payment_info($purchase_order_info['supplier_code'],$purchase_order_info['is_include_tax'],$purchase_order_info['purchase_type_id']);
            if(empty($supplierPaymentInfo)) throw new Exception('供应商财务结算资料缺失，请完善供应商资料');

            if($supplierPaymentInfo['payment_method'] != 1) throw new Exception('供应商不支持支付宝支付，无法1688自助下单');
            if(empty($supplierPaymentInfo['settlement_type']) || empty($supplierPaymentInfo['supplier_settlement'])) throw new Exception('供应商结算方式缺失，无法1688自助下单');

            // 交易方式和结算方式 对应关系不要搞乱了！
            // 交易方式   国内仓/FBA：供应商的结算方式含有1688账期，那么结算方式=账期交易
            //            海外仓：采购单的供应商的结算方式，含有1688账期+其他结算方式，那么结算方式=担保交易；采购单的供应商的结算方式，只有1688账期，那么结算方式=账期交易
            // 结算方式   国内仓/FBA：供应商的结算方式含有1688账期，那么结算方式=1688账期
            //            海外仓：供应商的结算方式，=1688账期+其他结算方式，那么结算方式=非1688账期的那一个结算方式；采购单的供应商的结算方式，只有1688账期，那么结算方式=1688账期
            if($supplierPaymentInfo['supplier_settlement'] == SUPPLIER_SETTLEMENT_CODE_TAP_DATE){
                $trader_method = 'period';// 账期交易
            }else{
                $trader_method = 'fxassure';// 担保交易
            }

            // 3.验证产品信息
            $items_list = $purchase_order_info['items_list'];
            foreach($items_list as &$item_value){
                $skuInfo = $this->Product_model->get_product_info($item_value['sku']);
                if(empty($skuInfo)) throw new Exception('产品信息获取缺失');
                if(empty($skuInfo['is_relate_ali'])) throw new Exception($item_value['sku'].'，未关联1688，无法1688自助下单');
                $item_value['ali_ratio_own'] = $skuInfo['ali_ratio_own'];
                $item_value['ali_ratio_out'] = $skuInfo['ali_ratio_out'];
            }
            list($items_list,$is_qty_abnormal) = $this->convertOrderQuantityByAliRatio($items_list);// 根据 单位对应关系 转换成1688下单数量

            $ware_code = $purchase_order_info['warehouse_code'];
            // 28329
            if($purchase_order_info['purchase_type_id'] == PURCHASE_TYPE_OVERSEA){
                $vow_data = $this->purchase_order_extend_model->verify_overseas_warehouse($purchase_number, true);
                if($vow_data['code'] == 1)$ware_code = $vow_data['msg'];
            }

//             4.验证收货地址、采购账号、联系人信息
            if(!isset($warehouse_address_list[$ware_code]) || empty($warehouse_address_list[$ware_code])){
                throw new Exception('仓库收货地址错误');
            }
            $warehouse_address = $warehouse_address_list[$ware_code];
//            $userInfo          = $this->Purchase_order_model->get_access_purchaser_information($purchase_number);
            $userInfo = $this->get_purchase_user_info($purchase_order_info['buyer_id']);
            if(!$userInfo)$userInfo = [
                'user_name' => $purchase_order_info['buyer_name']
            ];
            $user_ali_account  = $this->aliaccount->getSubAccountOneByUserId($purchase_order_info['buyer_id']);
            if(empty($user_ali_account) or empty($user_ali_account['account'])) throw new Exception('采购员未配置采购账号');
            $contact_number = (isset($warehouse_address['contact_number']) and $warehouse_address['contact_number']) ? $warehouse_address['contact_number'] : (isset($userInfo['phone_number'])?$userInfo['phone_number']:'');
            if(empty($contact_number)) throw new Exception('联系电话缺失');

            $buyer_note = $this->convertOrderBuyerNote($purchase_number,$purchase_order_info['purchase_type_id']);

            // 提交要下单的数据
            $user_name = isset($userInfo['user_name']) ? preg_replace("/\\d+/", '', $userInfo['user_name']): preg_replace("/\\d+/", '', $purchase_order_info['buyer_name']);
            $data = [
                'purchase_number'  => $purchase_number,
                'order_discount'   => 0,// 默认 0
                'order_freight'    => 0,// 默认 0
                'order_process_cost' => 0,// 默认 0
                'sku_amount'       => array_column($items_list, 'purchase_amount', 'sku'),
                'ali_sku_amount'   => array_column($items_list, 'ali_purchase_amount', 'sku'),
                'ali_ratio_list'   => [],
                //'buyer_note'       => '1.po/FBA 单号一定要写在外箱与快递面单上 2.按照截图发货，发货清单一定要写放在内箱 3.每一款产品，请贴上sku，谢谢',// 固定
                'buyer_note'       => $buyer_note,
                'trader_method'    => $trader_method,
                'purchase_account' => $user_ali_account['account'],
                'address_id'       => 0,
                'modify_remark'    => [],
                'freight_note'     => '',// 运费说明（为空）
                'full_name'        => $user_name.' '.$warehouse_address['contacts'], //收货人姓名
                'mobile'           => $contact_number, //电话
                'phone'            => '', //电话
                'city'             => $warehouse_address['city_text'],
                'area'             => $warehouse_address['area_text'],
                'province'         => $warehouse_address['province_text'],
                'town'             => $warehouse_address['town_text'],
                'address'          => $purchase_number.' '.$warehouse_address['address'].$note, //街道地址
                'post_code'        => $warehouse_address['post_code'],
            ];

            $result = $this->do_one_key_order($data);
            if(empty($result['code'])){
                if(stripos($result['message'],'已经生成了1688订单，请勿重复下单') === false){
                    throw new Exception($result['message']);
                }else{
                    $flagMsg = '【已经生成了1688订单，请勿重复下单】';
                }
            }

            // 计算SKU的权均交期
            $skuDeliveryInfo = $this->Delivery_model->get_delivery_info($ware_code,array_column($items_list,'sku'));
            $deliveryTime    = date('Y-m-d H:i:s',time() + (isset($skuDeliveryInfo['avg_delivery_time'])?$skuDeliveryInfo['avg_delivery_time']:0) );

            // 更新采购单
            // 1.支付方式：线上支付宝
            // 2.结算方式：1688账期为账期交易，非1688账期款到发货
            // 3.结算比例：=100%
            // 4.发运类型：=中转仓发运
            // 5.运费支付：供应商管理中的“是否包邮”字段，包邮=乙方支付，不包邮或为空=甲方支付
            // 6.运费计算方式：国内仓/FBA=体积，海外仓=重量
            // 7.供应商运输：=快递
            // 8.预计到货时间：=1688自助下单时间+sku权均交期（从新采购系统的权均交期表获取）
            $this->Purchase_order_model->update_order_ali_status($purchase_number,
                 [
                     'shipment_type'      => 2,// 发运类型(1.工厂发运;2.中转仓发运)
                     'shipping_method_id' => 2,// 供应商运输1:自提,2:快递,3:物流,4:送货,5:直发整柜,6:直发散货
                     'first_plan_product_arrive_time' => $deliveryTime,// 首次预计到货时间
                     'plan_product_arrive_time'       => $deliveryTime// 预计到货时间
                 ]
            );
            $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);
            $this->purchase_order_pay_type_model->update_one($have_pay_type['id'],
                 [
                     'settlement_ratio'     => '100%',
                     'is_freight'           => ($supplierBaseInfo['is_postage'] == 1) ? PURCHASE_FREIGHT_PAYMENT_B : PURCHASE_FREIGHT_PAYMENT_A,
                     'freight_formula_mode' => (in_array($purchase_order_info['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) ? 'weight' : 'volume',// 运费计算方式(volume.体积,weight.重量)
                 ]
            );

            $return = ['code' => true,'message' => $purchase_number.' 下单成功'.$flagMsg,'data' => $purchase_number];
            return $return;
        }catch(Exception $e){
            $return = ['code' => false,'message' => $purchase_number.' '.$e->getMessage(),'data' => $purchase_number];
            return $return;
        }
    }

    /**
     * 获取采购用户信息
     */
    public function get_purchase_user_info($id=false)
    {
        if(!$id)return false;
        $data = $this->purchase_db->from('purchase_user_info')->where('user_id =', $id)->get()->row_array();
        return $data && isset($data['user_id']) ? $data:false;
    }

    /**
     * 1688下单确认-确认提交采购经理审核
     * @param mixed $data
     * @param bool $auto
     * @return array
     */
    public function one_key_order_submit($data, $auto=true, $manual=false){
        $return = ['code' => false,'message' => '','data' => ''];

        $purchase_number  = $data['purchase_number'];
        $purchase_number_arr = explode("\n",$purchase_number);
        $purchase_number = $purchase_number_arr[0];//避免提交过来的单号有临时供应商换行符
        $order_discount   = floatval($data['order_discount']);
        $order_freight    = floatval($data['order_freight']);
        $order_process_cost    = floatval($data['order_process_cost']);
        $purchasing_order_audit       = $data['purchasing_order_audit'];
        $freight_note = isset($data['freight_note'])?$data['freight_note']:'';
        $freight_sku = $data['freight_sku'];
        $discount_sku = $data['discount_sku'];
        $process_cost_sku = $data['process_cost_sku'];
        $is_freight = $data['is_freight'];

        $this->load->model('purchase/purchase_auto_audit_model');
        $this->load->model('purchase/purchase_order_extend_model');
        $this->load->model('purchase_suggest/purchase_demand_model');
        $this->load->model('calc_pay_time_model');
        $calc_base = $this->calc_pay_time_model->getSetParamData('PURCHASE_ORDER_PAY_TIME_SET');

        $this->purchase_db->trans_begin();
        try{
            $newest = $this->get_ali_order_newest_price($purchase_number);
            if(empty($newest['code']))throw new Exception($newest['message']);

            // 验证供应商是否一致
            $verify_supplier = $this->purchase_order_extend_model->verify_sku_supplier([$purchase_number]);
            if($verify_supplier !== true){
                throw new Exception($verify_supplier);
            }

            $newest_price = $newest['data'];

            $purchase_order = $this->Purchase_order_model->get_one($purchase_number);
            $items_list = $purchase_order['items_list'];

            $purchase_sku_list_tmp = [];
            $total_product_money = 0;
            foreach($items_list as $item){
                $total_product_money += $item['confirm_amount'] * $item['purchase_unit_price'];
                $purchase_sku_list_tmp[$item['sku']] = $item['confirm_amount'];

                // 分摊下单数量
                $this->purchase_demand_model->apportionPurchaseAmount($item['demand_number'],$item['confirm_amount']);
            }
            $purchase_sku_list[$purchase_number] = $purchase_sku_list_tmp;
            $total_price = $total_product_money + $order_freight - $order_discount + $order_process_cost;

            if(!$manual){
                // 验证是否满足自动审核金额
                $config_data = $this->purchase_db->from("data_control_config")->where(['config_type'=> 'ALI_AUTO_ONE_KEY'])->get()->row_array();
                $config = isset($config_data['config_values']) ? $config_data['config_values'] : false;
                if($config){
                    $config = json_decode($config, true);
                }
                if(SetAndNotEmpty($config, 'purchase_total_price_min') || SetAndNotEmpty($config, 'purchase_total_price_max')){
                    $p_min = (float)$config['purchase_total_price_min'];
                    $p_max = (float)$config['purchase_total_price_max'];
                    if(($p_min > 0 && $total_product_money < $p_min) || ($p_max > 0 && $total_product_money > $p_max) || bccomp($total_price,$newest_price,3) != 0){
                        throw new Exception("采购单:{$purchase_number} 不满足自动审核要求。");
                    }
                }

                // 运费自动审核
                if(SetAndNotEmpty($config, 'freight') && SetAndNotEmpty($newest, 'freight') && (float)$config['freight'] > 0 &&
                    (float)$newest['freight'] > 0 && (float)$newest['freight'] > (float)$config['freight']){
                    throw new Exception("采购单:{$purchase_number} 不满足自动审核要求。");
                }

            }

            if(bccomp($total_price,$newest_price,3) != 0){
                throw new Exception("当前订单总价[$newest_price]不等于采购系统订单总价[$total_price]");
            }
            operatorLogInsert(
                [
                    'id'      => $purchase_number,
                    'type'    => $this->table_name,
                    'content' => '1688下单确认',
                    'detail'  => '1688下单确认-确认提交采购经理审核成功',
                ]);

            if ($purchase_order['is_ali_abnormal']==1){
                //当采购单状态为1688异常时,1688下单确认后,订单状态变更为[信息变更等待审核]
                $result = $this->Purchase_order_model->change_status($purchase_number, PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT);
            }else{
                // 更新采购单状态
                $orderInfoNew = $this->Purchase_order_model->get_one($purchase_number);
                $automaticResult = $this->purchase_auto_audit_model->checkPurchaseOrderAutomaticAudit($orderInfoNew, $auto);
                if($automaticResult['code']){
                    $result = $this->Purchase_order_model->change_status($purchase_number, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL);

                    /* --------------- 计算应付款时间 start ----------------- */
                    $account_type = $purchase_order['account_type'];
                    $source = $purchase_order['source'];
                    $calc_date = $this->calc_pay_time_model->calc_pay_time_audit_service($calc_base, $account_type, $source, date("Y-m-d H:i:s"), '');
                    if(SetAndNotEmpty($calc_date, 'code') && $calc_date['code'] === true){
                        $this->purchase_db->where(['purchase_number' => $purchase_number])->update('purchase_order_pay_type', ['accout_period_time' => $calc_date['data']]);
                        $this->purchase_db->where(['purchase_number' => $purchase_number])->update('purchase_order_items', ['need_pay_time' => $calc_date['data']]);
                    }
                    /* --------------- 计算应付款时间 end ----------------- */
                }else{
                    $result = $this->Purchase_order_model->change_status($purchase_number, PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT);
                }
            }

            if(empty($result))throw new Exception("采购单状态更新失败");

            //更新采购单支付方式
            $update_data = [
                'pay_type' => $data['pay_type'],
//                'plan_product_arrive_time' => $data['plan_product_arrive_time'],
//                'first_plan_product_arrive_time' => $data['plan_product_arrive_time'],
            ];
            $update_res = $this->purchase_db->where('purchase_number', $purchase_number)->update('purchase_order', $update_data);
            if (!$update_res)throw new Exception("采购单付款方式更新失败");

            //更新PO单下所有备货单的预计到货时间
            $demand_numbers = $this->purchase_db->select('demand_number')->where('purchase_number', $purchase_number)->get('purchase_suggest_map')->result_array();
//            $update_arrive_data = [
//                'plan_product_arrive_time' => $data['plan_product_arrive_time'],
//            ];

            foreach ($demand_numbers as $demand_number){
                $suggest_info = $this->purchase_db->select('lock_type')->where_in('demand_number', $demand_number['demand_number'])->get('purchase_suggest')->row_array();

                //锁单不得提交采购经理审核
                if ($suggest_info['lock_type']==LOCK_SUGGEST_ENTITIES){
                    throw new Exception("备货单[".$demand_number['demand_number']."]锁单中");
                }

//                $update_arrive_time = $this->purchase_db->where_in('demand_number', $demand_number['demand_number'])->update('purchase_suggest', $update_arrive_data);
//                if (!$update_arrive_time){
//                    throw new Exception("备货单预计到货时间更新失败");
//                }
            }

            // 分摊 优惠额 和 运费
            /*
            $result = $this->update_item_data_by_sign($purchase_sku_list,$discount_sku,$freight_sku,$process_cost_sku);
            if(empty($result)){
                throw new Exception("采购单优惠额和运费分摊失败");
            }
            */

            // 插入  采购单确认记录
            $this->load->model('finance/purchase_order_pay_type_model');
            $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);

            if($have_pay_type){
                $update = [
                    'freight'           => floatval($order_freight),
                    'discount'          => floatval($order_discount),
                    'process_cost'      => floatval($order_process_cost),
                    'real_price'        => floatval($total_price),
                    'is_freight'        => $is_freight,
                    'freight_formula_mode' => 'weight',
                ];
                $result = $this->purchase_order_pay_type_model->update_one($have_pay_type['id'], $update);
                if(empty($result))throw new Exception("采购单确认记录更新失败");

                //更新运费说明
                if(!empty($freight_note)){
                    $update_freight_note =[
                        'freight_note'=>$freight_note
                    ];

                    $result = $this->purchase_order_pay_type_model->update_one($have_pay_type['id'], $update_freight_note);
                    if(empty($result)){
                        throw new Exception("更新运费说明失败！");
                    }
                }
            }

            //限制 临时供应商最多只能下8个p
            $order_number = $this->Purchase_order_model->temporary_supplier_order_number($purchase_order['supplier_code'],$purchase_order['source']);
            if(!empty($order_number)){
                throw new Exception('供应商['.$purchase_order['supplier_name'].']:'.implode(';',$order_number));
            }

            if ($purchasing_order_audit == PUSHING_BLUE_LING) {
                //推送蓝凌系统
                $this->Purchase_order_model->pushing_blue_ling($purchase_number);
            }

            $this->purchase_db->trans_commit();
            if($this->purchase_db->trans_status() === false){
                $this->purchase_db->trans_rollback();
                throw new Exception("提交失败，请联系IT处理！");
            }else{
                $return['code'] = true;
            }
        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $return['message'] = $e->getMessage();
        }

        return $return;
    }


    /**
     * 更新采购单的 运费和优惠额信息（自动分摊）
     * @param      $purchase_sku_change
     * @param null $order_discount
     * @param null $order_freight
     * @param null $order_process_cost
     * @return bool
     */
    public function update_item_data($purchase_sku_change,$order_discount = null,$order_freight = null,$order_process_cost = null){
        $this->load->helper('status_finance');
        $this->load->model('purchase/purchase_order_items_model');

        if(!is_null($order_discount)){
            $purchase_sku_change_discount = amountAverageDistribute($order_discount,$purchase_sku_change);
        }
        if(!is_null($order_freight)){
            $purchase_sku_change_freight = amountAverageDistribute($order_freight,$purchase_sku_change);
        }
        if(!is_null($order_process_cost)){
            $purchase_sku_change_process_cost = amountAverageDistribute($order_process_cost,$purchase_sku_change);
        }

        // 更新数据库中数据
        if(isset($purchase_sku_change_freight)){
            foreach ($purchase_sku_change_freight as $purchase_number => $value1) {
                foreach ($value1 as $sku => $value2) {
                    $item_result1 = $this->purchase_db->update('purchase_order_items', ['freight' => $value2],['purchase_number' => $purchase_number,'sku' => $sku],1);
                    if(empty($item_result1)) return false;
                }
            }
        }
        if(isset($purchase_sku_change_discount)){
            foreach ($purchase_sku_change_discount as $purchase_number => $value1) {
                foreach ($value1 as $sku => $value2) {
                    $item_result1 = $this->purchase_db->update('purchase_order_items', ['discount' => $value2],['purchase_number' => $purchase_number,'sku' => $sku],1);
                    if(empty($item_result1)) return false;
                }
            }
        }

        if(isset($purchase_sku_change_process_cost)){
            foreach ($purchase_sku_change_process_cost as $purchase_number => $value1) {
                foreach ($value1 as $sku => $value2) {
                    $item_result1 = $this->purchase_db->update('purchase_order_items', ['process_cost' => $value2],['purchase_number' => $purchase_number,'sku' => $sku],1);
                    if(empty($item_result1)) return false;
                }
            }
        }

        return true;
    }

    /**
     * 更新采购单的 运费和优惠额信息（手动填写）
     * @param      $purchase_sku_change
     * @param null $order_discount
     * @param null $order_freight
     * @param null $process_cost_sku
     * @return bool
     */
    public function update_item_data_by_sign($purchase_sku_change,$order_discount = null,$order_freight = null,$process_cost_sku = null){
        $this->load->helper('status_finance');
        $this->load->model('purchase/purchase_order_items_model');

        // 更新数据库中数据
        if(isset($purchase_sku_change)){
            foreach ($purchase_sku_change as $purchase_number => $value1) {
                foreach ($value1 as $sku => $value2) {
                    $save_order_item = [
                        'freight'  => $order_freight[$sku],
                        'discount' => $order_discount[$sku],
                        'process_cost' => $process_cost_sku[$sku],
                    ];
                    $item_result1 = $this->purchase_db->update('purchase_order_items', $save_order_item,['purchase_number' => $purchase_number,'sku' => $sku]);
                    if(empty($item_result1)) return false;
                }
            }
        }

        return true;
    }

    /**
     * 验证 是否1688下单
     * @param $purchase_number
     * @return array
     */
    public function check_have_order($purchase_number){
        $local_ali_order = $this->purchase_db->where('purchase_number',$purchase_number)->order_by('id desc')->get($this->table_name)->row_array();
        return $local_ali_order;
    }

    /**
     * 获取 本地 的1688订单记录
     * @param $purchase_number
     * @return array
     */
    public function get_local_ali_order($purchase_number){
        return $this->check_have_order($purchase_number);
    }

    /**
     * 获取 格式化 的预览数据（阿里下单产品列表）
     * @param        $address_id
     * @param string $warehouse_info
     * @param        $items_list
     * @param        $supplier_code
     * @return array
     */
    public function get_preview_format_data($address_id, $warehouse_info = '', $items_list, $supplier_code){
        $return = ['code' => false,'message' => '','data' => ''];

        // 组装数据为获取 1688订单预览数据
        $ali_order                       = [];
        $ali_order['addressId']          = $address_id;
        $ali_order['fullName']           = '';//收件人(采购员)
        $ali_order['mobile']             = '';
        $ali_order['phone']              = '';
        $ali_order['postCode']           = '';//邮编
        $ali_order['cityText']           = '';
        $ali_order['provinceText']       = '';
        $ali_order['areaText']           = '';
        $ali_order['townText']           = '';
        $ali_order['address']            = '';
        $ali_order['districtCode']       = '';


        $ali_order['invoice'] = $this->getInvoice();

        $ali_order['cargoParamList'] = [];

        foreach ($items_list as $item){
            $now_sku = [];
            $sku = $item['sku'];
            $have_ali_product = $this->Ali_product_model->get_ali_product_one(['sku' => $sku, 'supplier_code' => $supplier_code]);
            if(empty($have_ali_product)){
                $return['message'] = "SKU[$sku]未关联1688供应商的商品或默认供应商已变更,请重新配置采购链接";
                return $return;
            }
            // 1688 商品相关信息
            $now_sku['offerId']  = $have_ali_product['product_id'];
            if(trim($have_ali_product['product_id']) != trim($have_ali_product['spec_id'])){// 两者相等则为单属性产品
                $now_sku['specId']      = $have_ali_product['spec_id'];
            }
            $now_sku['quantity'] = $item['ali_purchase_amount'];// 1688下单数量

            $ali_order['cargoParamList'][] = $now_sku;
        }
        $return['code'] = true;
        $return['data'] = $ali_order;
        return $return;
    }

    /**
     * 查询 1688订单的最新 总价
     * @param $purchase_number
     * @return array
     */
    public function get_ali_order_newest_price($purchase_number){
        $return = ['code' => false,'message' => '','data' => '', 'freight' => 0];

        $this->load->model('finance/purchase_order_pay_type_model');
        $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);
        if(empty($have_pay_type) or empty($have_pay_type['pai_number'])){
            $return['message'] = '获取1688拍单号失败';
            return $return;
        }
        $newest = $this->aliorderapi->getOrderPrice($have_pay_type['pai_number']);

        if($newest['code']){
            $return['code'] = true;
            $return['data'] = $newest['data'];
            $return['freight'] = $have_pay_type['freight'];
            return $return;
        }else{
            $return['message'] = '获取订单最新总金额失败';
            return $return;
        }
    }

    /**
     * 查询 1688订单的最新 下单时的总价
     * @param $purchase_number
     * @return array|null
     */
    public function get_ali_order_old_price($purchase_number){
        $return = ['code' => false,'message' => '','data' => ''];

        $ali_order = $this->purchase_db->where('purchase_number',$purchase_number)->get($this->table_name)->row_array();
        if(empty($ali_order)){
            $return['message'] = '获取1688下单记录失败';
            return $return;
        }

        return $ali_order?$ali_order['total_success_amount']:null;
    }

    /**
     * 保存1688下单数据
     * @author Jeff
     * @param $purchase_number
     * @param $ali_order
     * @return bool
     */
    public function save_ali_order($purchase_number,$ali_order){
        $purchase_order = $this->Purchase_order_model->get_one($purchase_number);
        $order_main = [
            'purchase_number'            => $purchase_number,
            'supplier_code'              => $purchase_order['supplier_code'],
            'purchase_account'           => $ali_order['purchase_account'],
            'message'                    => $ali_order['message'],
            'flow'                       => $this->aliorderapi->getFlow(),
            'trader_method'              => $ali_order['trader_method'],
            'address_id'                 => $ali_order['addressId'],
            'provinceText'               => $ali_order['provinceText'],
            'cityText'                   => $ali_order['cityText'],
            'areaText'                   => $ali_order['areaText'],
            'townText'                   => $ali_order['townText'],
            'fullName'                   => $ali_order['fullName'],
            'mobile'                     => $ali_order['mobile'],
            'postCode'                   => $ali_order['postCode'],
            'districtCode'               => $ali_order['districtCode'],
            'phone'                      => $ali_order['phone'], //电话
            'address'                    => $ali_order['address'], //街道地址
            'success'                    => 1,
            'order_id'                   => $ali_order['order_id'],
            'total_success_amount'       => $ali_order['total_success_amount'] / 100,
            'post_fee'                   => $ali_order['post_fee'] / 100,
            'is_account_period'          => $ali_order['is_account_period'],
            'account_period_tap_type'    => $ali_order['account_period_tap_type'],
            'account_period_tap_date'    => $ali_order['account_period_tap_date'],
            'account_period_tap_overdue' => $ali_order['account_period_tap_overdue'],
            'failed_offer_list'          => $ali_order['failed_offer_list'],
            'order_status'               => 'WAITBUYERPAY',// 默认为 等待买家付款
            'create_user_id'             => getActiveUserId(),
            'create_user_name'           => getActiveUserName(),
            'create_time'                => date('Y-m-d H:i:s')
        ];
        $cargoParams = $ali_order['cargoParams'];
        $res = $this->purchase_db->insert($this->table_name,$order_main);
        if($res){
            foreach($cargoParams as $value){
                $order_detail = [
                    'purchase_number' => $purchase_number,
                    'sku'             => '',
                    'purchase_amount' => $value['quantity'],
                    'offer_id'        => $value['offerId'],
                    'spec_id'         => isset($value['specId'])?$value['specId']:'',
                ];
                $res = $this->purchase_db->insert($this->table_name_item,$order_detail);
                if(empty($res)){
                    operatorLogInsert(
                        [
                            'id'      => $purchase_number,
                            'type'    => $this->table_name,
                            'content' => '创建ALI订单',
                            'detail'  => ['order_main' => $order_main, 'cargoParams' => $cargoParams],
                        ]);
                }
            }
        }else{
            operatorLogInsert(
                [
                    'id'      => $purchase_number,
                    'type'    => $this->table_name,
                    'content' => '创建ALI订单',
                    'detail'  => ['order_main' => $order_main, 'cargoParams' => $cargoParams],
                ]);
        }
        return true;
    }

    /**
     * 根据 拍单号 获取1688平台上订单信息
     * @param null $purchase_number
     * @param null $pai_number
     * @return array
     */
    public function get_ali_order_info($purchase_number = null,$pai_number = null){
        $return = ['code' => false,'message' => '','data' => ''];
        if(is_null($pai_number)){
            $this->load->model('finance/purchase_order_pay_type_model');
            $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);
            if(empty($have_pay_type)){
                $return['message'] = '未找到采购单确认记录[确认是否已1688下单]';
                return $return;
            }
            $pai_number = $have_pay_type['pai_number'];
        }
        $ali_order_data = $this->aliorderapi->getListOrderDetail(null,$pai_number);
        if(!isset($ali_order_data[$pai_number]) or empty($ali_order_data[$pai_number])){
            $return['message'] = '未在1688平台找到该交易[确认是否已1688下单]';
            return $return;
        }

        $return['code'] = true;
        $return['data'] = $ali_order_data[$pai_number]['data'];
        $return['pai_number'] = $pai_number;

        return $return;
    }

    /**
     * 解析 1688下单 返回的错误提示信息
     * @author Jolon
     * @param $errorMsg
     * @param $errorParamsResult
     * @return bool|string
     */
    public function convert_message_content($errorMsg,$errorParamsResult = []){
        if(strpos($errorMsg, '库存不足') !== false){
            $substr = substr($errorMsg, strlen('[')+strpos($errorMsg, '['),(strlen($errorMsg) - strpos($errorMsg, '_'))*(-1));
            if(!empty($substr)) {
                $spec_id = '';
                if (isset($errorParamsResult[$substr]['specId'])) {
                    $spec_id = $errorParamsResult[$substr]['specId'];
                }
                if (!empty($spec_id)) {
                    $have_ali_product = $this->Ali_product_model->get_ali_product_group_concat(['product_id' => $substr, 'spec_id' => $spec_id]);
                    if (!empty($have_ali_product)) {
                        $sku = $have_ali_product['sku'];
//                                    $errorMsg = "商品[".$sku."]库存不足，请核实库存后订购";
                        $errorMsgReturn = "1688商品[" . $substr . "]-[" . $sku . "]库存不足，请核实库存后订购";
                    }
                }
            }
        }
        if(strpos($errorMsg, '不属于同一卖家或者规格') !== false){
            $substr = substr($errorMsg, strlen('[')+strpos($errorMsg, '['),(strlen($errorMsg) - strpos($errorMsg, ']'))*(-1));
            if(!empty($substr)) {
                $spec_id = '';
                if (isset($errorParamsResult[$substr]['specId'])) {
                    $spec_id = $errorParamsResult[$substr]['specId'];
                }
                if (!empty($spec_id)) {
                    $have_ali_product = $this->Ali_product_model->get_ali_product_group_concat(['product_id' => $substr, 'spec_id' => $spec_id]);
                    if (!empty($have_ali_product)) {
                        $sku = $have_ali_product['sku'];
                        $attribute = $have_ali_product['attribute'];
                        $errorMsgReturn = "商品-[" . $sku . "]不属于同一卖家或者规格-[" . $attribute . "]不属于商品-[" . $sku . "]";
                    }
                }
            }
        }
        if(strpos($errorMsg, '不属于同一卖家或者没有指定specId') !== false){
            $substr = substr($errorMsg, strlen('[')+strpos($errorMsg, '['),(strlen($errorMsg) - strpos($errorMsg, ']'))*(-1));
            if(!empty($substr)) {
                $have_ali_product = $this->Ali_product_model->get_ali_product_group_concat(['product_id' => $substr]);
                if (!empty($have_ali_product)) {
                    $sku = $have_ali_product['sku'];
                    $errorMsgReturn = "商品[".$sku."]不属于同一卖家或者没有指定specId";
                }
            }
        }
        if(strpos($errorMsg, '的购买数量或者价格不满足混批限制') !== false){
            $substr = substr($errorMsg, strlen('[')+strpos($errorMsg, '['),(strlen($errorMsg) - strpos($errorMsg, ']'))*(-1));
            if(!empty($substr)) {
                if (strpos($substr, ',') !== false) {
                    $sku_all = '';
                    $ids= explode(',',$substr);
                    foreach ($ids as $val){
                        $spec_id = '';
                        $substr = trim($val);
                        if (isset($errorParamsResult[$substr]['specId'])) {
                            $spec_id = $errorParamsResult[$substr]['specId'];
                        }
                        if (!empty($spec_id)) {
                            $have_ali_product = $this->Ali_product_model->get_ali_product_group_concat(['product_id' => $substr, 'spec_id' => $spec_id]);
                            if (!empty($have_ali_product)) {
                                $sku = $have_ali_product['sku'];
                              $sku_all.=' '.$sku;
                            }
                        }
                    }
                    $errorMsgReturn ="商品[" . $sku_all . "]的购买数量或者价格不满足混批限制";
                } else {
                    $spec_id = '';
                    if (isset($errorParamsResult[$substr]['specId'])) {
                        $spec_id = $errorParamsResult[$substr]['specId'];
                    }
                    if (!empty($spec_id)) {
                        $have_ali_product = $this->Ali_product_model->get_ali_product_group_concat(['product_id' => $substr, 'spec_id' => $spec_id]);
                        if (!empty($have_ali_product)) {
                            $sku = $have_ali_product['sku'];
                            $errorMsgReturn = "商品[" . $sku . "]的购买数量或者价格不满足混批限制";
                        }
                    }
                }
            }
        }
        if(strpos($errorMsg, '不支持在线交易，无法下单') !== false){
            $substr = substr($errorMsg, strlen('[')+strpos($errorMsg, '['),(strlen($errorMsg) - strpos($errorMsg, ']'))*(-1));
            if(!empty($substr)) {
                $spec_id = '';
                if (isset($errorParamsResult[$substr]['specId'])) {
                    $spec_id = $errorParamsResult[$substr]['specId'];
                }
                if (!empty($spec_id)) {
                    $have_ali_product = $this->Ali_product_model->get_ali_product_group_concat(['product_id' => $substr, 'spec_id' => $spec_id]);
                    if (!empty($have_ali_product)) {
                        $sku = $have_ali_product['sku'];
                        $errorMsgReturn = "商品[".$sku."]不支持在线交易，无法下单";
                    }
                }
            }
        }
        if(strpos($errorMsg, '的购买数量不满足起批量限制') !== false){
            $substr = substr($errorMsg, strlen('[')+strpos($errorMsg, '['),(strlen($errorMsg) - strpos($errorMsg, ']'))*(-1));
            if(!empty($substr)) {
                if (strpos($substr, ',') !== false) {
                    $sku_all = '';
                    $ids = explode(',', $substr);
                    foreach ($ids as $val) {
                        $spec_id = '';
                        $substr = trim($val);
                        if (isset($errorParamsResult[$substr]['specId'])) {
                            $spec_id = $errorParamsResult[$substr]['specId'];
                        }
                        if (!empty($spec_id)) {
                            $have_ali_product = $this->Ali_product_model->get_ali_product_group_concat(['product_id' => $substr, 'spec_id' => $spec_id]);
                            if (!empty($have_ali_product)) {
                                $sku = $have_ali_product['sku'];
                                $sku_all .= ' ' . $sku;
                            }
                        }
                    }
                    $errorMsgReturn = "商品[" . $sku_all . "]的购买数量不满足起批量限制!";
                } else {
                    $spec_id = '';
                    if (isset($errorParamsResult[$substr]['specId'])) {
                        $spec_id = $errorParamsResult[$substr]['specId'];
                    }
                    if (!empty($spec_id)) {
                        $have_ali_product = $this->Ali_product_model->get_ali_product_group_concat(['product_id' => $substr, 'spec_id' => $spec_id]);
                        if (!empty($have_ali_product)) {
                            $sku = $have_ali_product['sku'];
                            $errorMsgReturn = "商品[" . $sku . "]的购买数量不满足起批量限制!";
                        }
                    }
                }
            }
        }
        if(stripos($errorMsg, 'offer no exist') !== false){
            preg_match_all('/\d+/i',$errorMsg,$matches);
            if($matches){
                $sku_all = '';
                $matches = current($matches);
                foreach($matches as $substr){
                    $have_ali_product = $this->Ali_product_model->get_ali_product_group_concat(['product_id' => $substr]);
                    if (!empty($have_ali_product)) {
                        $sku = $have_ali_product['sku'];
                        $sku_all .= ' ' . $sku;
                    }
                }
                $errorMsgReturn = "商品[" . trim($sku_all) . "]已经下架!";
            }
        }
        if(stripos($errorMsg, '系统繁忙,请重试!') !== false){
            $errorMsgReturn = '采购单部分SKU所属商品1688平台已经更新，请排查后重新关联';
        }
        if(stripos($errorMsg, '拆单结果发生了变化') !== false){
            $errorMsgReturn = $errorMsg.'：采购单部分SKU所属商品1688平台已经更新，请排查后重新关联';
        }
        if(stripos($errorMsg, 'Cargo list not duplicate is required') !== false){
            $errorMsgReturn = '货物清单重复，请检验SKU是否重复或关联相同的商品属性或采购链接是否重复';
        }
        if(stripos($errorMsg, 'availableQuota less than sumPayment') !== false){
            $errorMsgReturn = str_replace('availableQuota less than sumPayment','账期余额不足',$errorMsg);
        }
        if(stripos($errorMsg, 'not support tradeType') !== false){
            if(stripos($errorMsg,':')){// 去除重复
                $errorMsg = implode('：',array_unique(explode(':',$errorMsg)));
            }
            $errorMsgReturn = str_replace('not support tradeType','不支持的交易方式',$errorMsg);

            $tradeTypeList = $this->aliorderapi->tradeTypeList;
            if($tradeTypeList){
                foreach($tradeTypeList as $tradeKey => $tradeValue){
                    $errorMsgReturn = str_replace($tradeKey,$tradeValue,$errorMsgReturn);
                }
            }
        }

        return isset($errorMsgReturn)?$errorMsgReturn:false;

    }

    /**
     * 生成一个 空的 发票信息
     * @return array
     */
    public function getInvoice(){
        $invoice = [
            'invoiceType'        => '',
            'provinceText'       => '',
            'cityText'           => '',
            'areaText'           => '',
            'townText'           => '',
            'postCode'           => '',
            'address'            => '',
            'fullName'           => '',
            'phone'              => '',
            'mobile'             => '',
            'companyName'        => '',
            'taxpayerIdentifier' => '',
            'bankAndAccount'     => '',
            'localInvoiceId'     => '',
        ];

        return $invoice;
    }


    /**
     * 计划任务 自动刷新 1688订单状态
     */
    public function autoUpdateOrderStatus() {
        set_time_limit(300);
        $pur_order    = $this->input->get_post('pur_order');
        $debug        = $this->input->get_post('debug');
        $operator_key = 'autoUpdateOrderStatus';

        // 验证 redis 里面是否还有要待处理的数据
        $len = $this->rediss->llenData($operator_key);
        if($len <= 0){
            // 没有数据 则自动增加待处理的数据
            $query_sql = "SELECT * FROM (
                            SELECT purchase_number,TRIM(order_id) AS ali_order_id,purchase_account,order_status
                            FROM `pur_ali_order` 
                            WHERE order_id!=''
                            AND (order_status IN ('WAITBUYERPAY', 'WAITSELLERSEND', 'WAITLOGISTICSTAKEIN', 'WAITBUYERRECEIVE', 'WAITBUYERSIGN', 'CONFIRM_GOODS') OR order_status='')
                            
                            UNION ALL
                            
                            SELECT A.purchase_number,TRIM(A.pai_number) AS ali_order_id,A.purchase_acccount AS purchase_account,B.ali_order_status AS order_status
                            FROM pur_purchase_order_pay_type AS A
                            INNER JOIN pur_purchase_order AS B ON A.purchase_number=B.purchase_number
                            WHERE pai_number !='' AND A.purchase_acccount!='' AND LENGTH(TRIM(pai_number))=18
                            AND (B.ali_order_status IN('等待买家付款','等待卖家发货','等待物流公司揽件','等待买家收货','等待买家签收','已收货') OR B.ali_order_status='')
                            
                            ) AS  tmp GROUP BY purchase_number";

            $query     = $this->purchase_db->query($query_sql);
            $ali_order_ids = $query->result_array();
            if($ali_order_ids){
                foreach($ali_order_ids as $order_value){
                    $value = $order_value['purchase_number'].'_'.$order_value['ali_order_id'];
                    $this->rediss->lpushData($operator_key,$value);
                }

                $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_autoUpdateOrderStatus');
            }else{
                echo '没有需要执行的数据-1';exit;
            }
        }

        if($pur_order){// 执行指定订单
            echo '<pre>';
            $wait_list[] = $pur_order;
        }else{
            $wait_list = [];
            for($i = 0; $i < 50 ;$i ++){
                $order_id = $this->rediss->rpopData($operator_key);
                if(empty($order_id)) break;
                $wait_list[] = $order_id;
            }

            if(empty($wait_list)){
                echo '没有需要执行的数据-2';exit;
            }
        }


        $wait_list_tmp  = [];
        foreach($wait_list as $now_value){
            $now_value       = explode('_', $now_value);
            $purchase_number = $now_value[0];
            $order_id        = $now_value[1];
            $wait_list_tmp[$purchase_number] = $order_id;
        }
        $wait_list = $wait_list_tmp;
        unset($wait_list_tmp);

        $this->doUpdateOrderStatusByIds($wait_list,$debug);

        echo 'sss';exit;
    }

    /**
     * 抓取1688 订单状态，更新到采购系统
     * @param array  $wait_list  订单列表
     * @param string  $debug  是否开启调试
     * @return bool
     * @example $wait_list = array(
     *      'ABD00001' => '523123456789987653',
     *      'ABD00002' => '523123456789987654'
     *   )
     */
    public function doUpdateOrderStatusByIds($wait_list,$debug = null){
        if(empty($wait_list)) return false;

        $this->load->helper('status_1688');
        $aliOrderStatus = aliOrderStatus();

        $orderInfo = $this->aliorderapi->getListOrderDetail(null, array_values($wait_list));
        if($debug) print_r($orderInfo);

        $this->load->model('finance/purchase_order_pay_type_model');
        foreach($wait_list as $purchase_number => $order_id){
            if (isset($orderInfo[$order_id]) and isset($orderInfo[$order_id]['code']) and $orderInfo[$order_id]['code'] == 200) {
                $baseInfo   = $orderInfo[$order_id]['data']['baseInfo'];
                $status     = strtoupper($baseInfo['status']);
                if($debug) print_r($orderInfo[$order_id]);
                // 更新阿里订单状态
                $have_ali_order = $this->get_local_ali_order($purchase_number);
                if($debug) print_r($have_ali_order);
                if($have_ali_order and isset($have_ali_order['id'])){
                    $this->purchase_db->where('id', $have_ali_order['id'])->update($this->table_name, ['order_status' => $status]);
                }

                // 更新采购单 1688订单状态
                $have_order = $this->Purchase_order_model->get_one($purchase_number,false);
                if($have_order){
                    $status_text = isset($aliOrderStatus[$baseInfo['status']])?$aliOrderStatus[$baseInfo['status']]:$baseInfo['status'];

                    if($debug) print_r($status_text);
                    if($have_order['ali_order_status'] != $status_text){

                        //标记1688异常
                        if ( ($status_text=='交易取消'||$status_text=='交易终止')
                            && in_array($have_order['purchase_order_status'],[
                                //PURCHASE_ORDER_STATUS_WAITING_QUOTE,
                                PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                                PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,
                                PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                                PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                                PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                                PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT,
                            ])){
                            $this->Purchase_order_model->update_order_ali_status($purchase_number, ['ali_order_status' => $status_text,'is_ali_abnormal'=>1]);
                        }else{
                            $this->Purchase_order_model->update_order_ali_status($purchase_number, ['ali_order_status' => $status_text,'is_ali_abnormal'=>0]);
                        }

                        if (in_array($status, ['WAITSELLERSEND', 'WAITLOGISTICSTAKEIN', 'WAITBUYERRECEIVE', 'WAITBUYERSIGN', 'CONFIRM_GOODS', 'SIGNINSUCCESS', 'SUCCESS'])
                            and ( empty($have_order['pay_time']) or $have_order['pay_time'] == '0000-00-00 00:00:00') and (isset($baseInfo['payTime']) && !empty($baseInfo['payTime']))) {
                          /*  $this->purchase_db->where('pur_number', $purchase_number)
                                    ->where('payer_time', '0000-00-00 00:00:00')
                                ->update($this->table_name_pay, ['pay_status' => PAY_PAID, 'payer_time' => $baseInfo['payTime']]);

                            $this->purchase_db->where('purchase_number', $purchase_number)->update($this->table_order, ['pay_status' => PAY_PAID, 'pay_time' => $baseInfo['payTime']]);

                            operatorLogInsert(
                                [
                                    'id' => $purchase_number,
                                    'type' => 'PURCHASE_ORDER',
                                    'content' => '更新订单付款时间【1688】',
                                    'detail' => "付款时间：改为【" . $baseInfo['payTime'] . "】",
                                    'is_show' => 1,
                                ]
                            ); */
                        }
                     /*   operatorLogInsert(
                            [
                                'id'      => $purchase_number,
                                'type'    => 'PURCHASE_ORDER',
                                'content' => '更新订单状态【1688】',
                                'detail'  => "1688订单状态：从【{$have_order['ali_order_status']}】改为【{$status_text}】",
                                'is_show' => 1,
                            ]
                        );*/
                    }else{

                        apiRequestLogInsert(
                            [
                                'record_number'    => $purchase_number,
                                'record_type'      => '更新订单状态【1688】',
                                'post_content'     => '订单已经是最新状态了，请勿加入队列',
                                'response_content' => '',
                                'status'           => 1,
                            ],
                            'api_request_ali_log'
                        );
                    }
                }

            }else{
                apiRequestLogInsert(
                    [
                        'record_number'    => $purchase_number,
                        'record_type'      => '更新订单状态【1688】',
                        'post_content'     => '获取订单信息失败',
                        'response_content' => '',
                        'status'           => 0,
                    ],
                    'api_request_ali_log'
                );
            }
        }

        return true;
    }

    /**
     * 计划任务 自动获取 1688订单的物流单号
     */
    public function autoUpdateOrderTrackingNumber(){
        set_time_limit(300);

        $pur_order    = $this->input->get_post('pur_order');
        $debug        = $this->input->get_post('debug');
        $operator_key = 'aliOrderTrackingNumber';

        // 验证 redis 里面是否还有要待处理的数据
        $len = $this->rediss->llenData($operator_key);
        if($len <= 0){
            // 没有数据 则自动增加待处理的数据
            $query_sql = "SELECT * FROM (
                                SELECT A.purchase_number,TRIM(A.order_id) AS ali_order_id,A.purchase_account AS purchase_account
                                FROM `pur_ali_order` AS A
                                LEFT JOIN `pur_purchase_order` AS B ON A.`purchase_number`=B.`purchase_number`
                                WHERE B.`purchase_order_status` NOT IN(9,11,14)
                                AND A.tracking_number='' 
                                AND A.order_id!=''
                                AND A.order_status NOT IN('cancel', 'TERMINATED') 
                            
                                UNION 
                            
                                SELECT A.purchase_number,TRIM(A.pai_number) AS ali_order_id,A.purchase_acccount AS purchase_account
                                FROM `pur_purchase_order_pay_type` AS A
                                LEFT JOIN `pur_purchase_order` AS B ON A.`purchase_number`=B.`purchase_number`
                                WHERE  B.`purchase_order_status` NOT IN(9,11,14)
                                AND A.pai_number !='' 
                                AND A.purchase_acccount LIKE 'yibaisuperbuyers%'
                                AND (A.express_no='' OR A.express_no IS NULL) 
                                AND LENGTH(TRIM(A.pai_number))=18
                                AND A.purchase_number NOT LIKE 'YPO%'
                            
                            ) AS tmp GROUP BY ali_order_id";

            $query     = $this->purchase_db->query($query_sql);
            $ali_order_ids = $query->result_array();
            if($ali_order_ids){
                foreach($ali_order_ids as $order_value){
                    $value = $order_value['purchase_number'].'_'.$order_value['ali_order_id'];
                    $this->rediss->lpushData($operator_key,$value);
                }

                $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_aliOrderTrackingNumber');
            }else{
                echo '没有需要执行的数据-1';exit;
            }
        }

        if($pur_order){// 执行指定订单
            echo '<pre>';
            $wait_list[] = $pur_order;
        }else{
            $wait_list = [];
            for($i = 0; $i < 50 ;$i ++){
                $order_id = $this->rediss->rpopData($operator_key);
                if(empty($order_id)) break;
                $wait_list[] = $order_id;
            }

            if(empty($wait_list)){
                echo '没有需要执行的数据-2';exit;
            }
        }

        $wait_list_tmp  = [];
        foreach($wait_list as $now_value){
            $now_value       = explode('_', $now_value);
            $purchase_number = $now_value[0];
            $order_id        = $now_value[1];
            $wait_list_tmp[$purchase_number] = $order_id;
        }
        $wait_list = $wait_list_tmp;
        unset($wait_list_tmp);
        $this->doUpdateOrderTrackingNumberByIds($wait_list,$debug);

        echo 'sss';exit;
    }

    /**
     * 抓取1688 订单发货信息、物流单号，更新到采购系统
     * @param array  $wait_list  订单列表
     * @param string  $debug  是否开启调试
     * @return bool
     * @example $wait_list = array(
     *      'ABD00001' => '523123456789987653',
     *      'ABD00002' => '523123456789987654'
     *   )
     */
    public function doUpdateOrderTrackingNumberByIds($wait_list,$debug = null){
        if(empty($wait_list)) return false;

        $orderLogInfo    = $this->aliorderapi->listLogisticsInfo(array_values($wait_list));
        if($debug) print_r($orderLogInfo);

        if(!empty($orderLogInfo) and isset($orderLogInfo['code']) and $orderLogInfo['code'] == 200){
            $response_data_list = $orderLogInfo['data'];

            $this->load->model('finance/purchase_order_pay_type_model');
            $this->load->model('warehouse/parcel_urgent_model');
            $this->load->model('Purchase_order_progress_model','m_progress',false,'purchase');
            foreach($wait_list as $purchase_number => $order_id){
                if(isset($response_data_list[$order_id]) and $response_data_list[$order_id]){
                    $now_order_data_list = $response_data_list[$order_id];
                    $now_order_data = (isset($now_order_data_list['result']) and isset($now_order_data_list['result'][0]))?$now_order_data_list['result'][0]:[];
                    if($debug) print_r($now_order_data);

                    if($now_order_data){
                        $tracking_number  = $now_order_data['logisticsBillNo'];
                        $tracking_company = $now_order_data['logisticsCompanyName'];
                        $status           = strtoupper($now_order_data['status']);
                        $receiver         = json_encode($now_order_data['receiver'], JSON_UNESCAPED_UNICODE);
                        $sender           = json_encode($now_order_data['sender'], JSON_UNESCAPED_UNICODE);
                        //转换快递公司编码
                        $carrier_code = $this->_get_carrier_code($now_order_data['logisticsCompanyId'],$tracking_company);
                        if($tracking_number){
                            $have_ali_order = $this->get_local_ali_order($purchase_number);
                            if($have_ali_order and isset($have_ali_order['id'])){
                                // 更新 1688下单记录的物流信息
                                $updateData = [
                                    'tracking_number' => $tracking_number,
                                    'tracking_company' => $tracking_company,
                                    'status'           => $status,
                                    'receiver'         => $receiver,
                                    'sender'           => $sender,
                                ];
                                $this->purchase_db->update($this->table_name, $updateData,['id' => $have_ali_order['id']]);
                                if($debug) print_r($updateData);
                            }

                            $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);
                            if($have_pay_type){
                                // 更新采购单中的 快递单号
                                $updateOrderData = [
                                    'express_no'       => $tracking_number,
                                    'cargo_company_id' => $tracking_company
                                ];

                                $this->purchase_db->update('purchase_order_pay_type', $updateOrderData,['purchase_number' => $purchase_number],1);
                                if($debug) print_r($updateOrderData);
                                //查询该采购单存在多少对应的sku
                                $skus = $this->purchase_order_pay_type_model->get_sku_by_po($purchase_number);
                                if ($skus){
                                    $insert_data = [];
                                    foreach ($skus as $sku){
                                        $insert_data[] = [
                                            'purchase_number' => $purchase_number,
                                            'sku'=> $sku,
                                            'express_no'       => $tracking_number,
                                            'cargo_company_id' => $tracking_company,
                                            'carrier_code' => $carrier_code
                                        ];
                                    }
                                    //如果已经存在物流信息则跳过
                                    $row = $this->purchase_db->select('*')->where('purchase_number',$purchase_number)->where('express_no !=','')->get('purchase_logistics_info')->row_array();
                                    if (empty($row) &&!empty($insert_data)){
//                                        $this->purchase_db->insert_batch('purchase_logistics_info',$insert_data);//将物流信息写入到物流信息表
                                        foreach ($insert_data as $v){
                                            $this_date = date("Y-m-d H:i:s");
                                            $this->purchase_db->query("INSERT INTO pur_purchase_logistics_info ( purchase_number, sku, cargo_company_id, carrier_code, express_no, `status`, `type`, create_time )
                                            VALUES
                                                ('{$v['purchase_number']}', '{$v['sku']}', '{$v['cargo_company_id']}', '{$v['carrier_code']}', '{$v['express_no']}', 0, 1, '{$this_date}' ) 
                                                ON DUPLICATE KEY UPDATE 
                                                purchase_number =VALUES(purchase_number),
                                                sku =VALUES(sku),
                                                express_no =VALUES(express_no);");
                                        }
                                        if($debug) print_r($insert_data);
                                        //变成等待到货，有物流单号的po的推送到WMS
                                        if (!empty($tracking_number)&&!empty($purchase_number)){
                                            $pushData[] = [
                                                'expressNo' => $tracking_number,
                                                'cargoCompanyName' => $tracking_company,
                                                'purchaseOrderNo' => $purchase_number,
                                            ];

                                            $pushNewWmsData[] = [

                                                'poNumber' => $purchase_number, // 采购单号
                                                'expressNo' => $tracking_number, // 快递单号
                                                'expressSupplier' => $tracking_company, // 快递公司
                                                'expressSupplierCode' => $carrier_code, //快递公司编码
                                                'createName' => getActiveUserName() // 操作人
                                            ];
                                            $this->m_progress->push_express_info_to_wms($pushData);
                                            $this->m_progress->push_receive_bind_express(NULL,$pushNewWmsData); // 推送到新仓库系统
                                            if($debug) print_r($pushData);
                                        }
                                    }
                                }
                                //$this->purchase_db->update('purchase_order', ['is_push' => 0],['purchase_number' => $purchase_number],1);// 重推采购单

                            }

                            //更新包裹加急的物流单号
                            $have_parcel = $this->parcel_urgent_model->get_one($purchase_number);
                            if($have_parcel){
                                // 更新采购单包裹加急中的 快递单号
                                $updateOrderData = [
                                    'logistics_num' => $tracking_number,//物流单号
                                    'update_time'   => date('Y-m-d H:i:s'),
                                    'push_status'   => 0,//改为未推送
                                    'push_res'      => '未推送',//改为未推送
                                ];

                                $this->parcel_urgent_model->update_logistics(['purchase_order_num' => $purchase_number], $updateOrderData);
                                //$this->purchase_db->update('purchase_order', ['is_push' => 0],['purchase_number' => $purchase_number],1);// 重推采购单
                                if($debug) print_r($updateOrderData);
                            }


                            operatorLogInsert(
                                [
                                    'id'      => $purchase_number,
                                    'type'    => 'PURCHASE_ORDER',
                                    'content' => '获取物流信息【1688】',
                                    'detail'  => '物流单号：'.$tracking_number,
                                    'is_show' => 1,
                                ]
                            );
                        }else{
                            apiRequestLogInsert(
                                [
                                    'record_number'    => $purchase_number,
                                    'record_type'      => '获取物流信息【1688】',
                                    'post_content'     => '获取失败',
                                    'response_content' => '',
                                    'status'           => 0,
                                ],
                                'api_request_ali_log'
                            );
                        }

                    }else{
                        apiRequestLogInsert(
                            [
                                'record_number'    => $purchase_number,
                                'record_type'      => '获取物流信息【1688】',
                                'post_content'     => '获取失败',
                                'response_content' => $response_data_list[$order_id],
                                'status'           => 0,
                            ],
                            'api_request_ali_log'
                        );
                    }

                }else{
                    apiRequestLogInsert(
                        [
                            'record_number'    => $purchase_number,
                            'record_type'      => '获取物流信息【1688】',
                            'post_content'     => '获取失败',
                            'response_content' => '没有获取到订单信息',
                            'status'           => 0,
                        ],
                        'api_request_ali_log'
                    );
                }
            }

        }else{
            apiRequestLogInsert(
                [
                    'record_number'    => 'autoUpdateOrderTrackingNumber',
                    'record_type'      => '获取物流信息【1688】',
                    'post_content'     => $wait_list,
                    'response_content' => $orderLogInfo,
                    'status'           => 0,
                ],
                'api_request_ali_log'
            );
        }

        return true;
    }

    /**
     * 获取 1688物流单号的物流轨迹
     * @param $order_id
     */
    public function get_logistics_tracking($order_id){
        $logisticsList = $this->aliorderapi->getLogisticsTraceInfo($order_id);
        print_r($logisticsList);exit;
    }

    /**
     * 获取一小时内的退款退货订阅数据
     */
    public function sync_refund_order_data($refundAll = 0)
    {
        $last_hour = "2020-10-01 00:00:00";
        $order_list = $this->purchase_db
            ->from('api_request_ali_log')
            ->where(['record_number' => 'JAVA_MSG_FROM_ALI'])
            ->where("create_time >= '{$last_hour}'")
            ->order_by('id desc')
            ->get()
            ->result_array();
        $refund_type = ["ORDER_BUYER_VIEW_ORDER_REFUND_AFTER_SALES", "ORDER_BUYER_VIEW_ORDER_BUYER_REFUND_IN_SALES"];
        $x = 0;
        if(!$order_list || empty($order_list))return;
        $this->load->model('ali/ali_order_refund_model');
        foreach($order_list as $value) {
            if (empty($value['response_content']) || $value['response_content'] == '[]') continue;
            $response_content = json_decode($value['response_content'], true, 512, JSON_BIGINT_AS_STRING);// 解析成数据，注意BIG INT的科学计数法
            if (isset($response_content['message'][0]) && is_json($response_content['message'][0])) {// 获取1688消息体
                $data = json_decode($response_content['message'][0], true, 512, JSON_BIGINT_AS_STRING);// 解析成数据，注意BIG INT的科学计数法

                // 29160 异常订单增加1688退货退款的模块
                if (isset($data['type']) && in_array($data['type'], $refund_type)) {
                    $result = $this->ali_order_refund_model->subscribe_refund_data($data['data']);
                    echo $x ."......".$data['data']['orderId']."......".$result."<br />";
                }
                $x ++;
            }
        }
    }

    /**
     * 接收 1688 平台消息 - 来自 JAVA 的中转数据
     * @author Jolon
     * @param $data
     * @param $debug
     * @param $limit
     * @return int
     */
    public function receive_ali_order_message($data,$debug = null,$limit = 1){
        set_time_limit(300);
        if($debug) print_r($data);

        if($data){
            // 先保存数据，保证保存成功
            apiRequestLogInsert(
                [
                    'record_number'    => 'JAVA_MSG_FROM_ALI',
                    'record_type'      => '接收消息【From 1688】',
                    'post_content'     => '',
                    'response_content' => $data,
                    'status'           => 1,// 接收信息成功
                ],
                'api_request_ali_log'
            );
        }

        $flag = true;
        do{
            $order_list = $this->purchase_db
                ->where("record_number='JAVA_MSG_FROM_ALI' AND status=1")
                ->order_by('id desc')
                ->get('api_request_ali_log',$limit)
                ->result_array();
            // 获取数据 后立即标记处理中
            $ids = array_column($order_list,'id');
            $order_list = arrayKeyToColumn($order_list,'id');
            if($ids){
                // 标记数据已处理，跳过已处理的数据
                foreach($ids as $id_v){
                    $session_key = 'receive_ali_order_message_'.$id_v;
                    if (!$this->rediss->getData($session_key)) {
                        $this->rediss->setData($session_key, '1', 30); //设置缓存和有效时间

                        $this->purchase_db->where("id",$id_v)->update('api_request_ali_log',['post_content' => 'handing','status' => 2]);//2 .处理中
                    } else {
                        unset($order_list[$id_v]);// 跳过已处理中的数据
                    }
                }
            }

            if($debug) print_r($order_list);
            if(empty($order_list)){ $flag = false;break; }

            $this->load->model('finance/purchase_order_pay_type_model');
            $this->load->model('purchase/Purchase_order_model');
            $this->load->model('ali/ali_order_refund_model');
            $this->load->model('ali/ali_order_message_model');

            $refund_type = ["ORDER_BUYER_VIEW_ORDER_REFUND_AFTER_SALES", "ORDER_BUYER_VIEW_ORDER_BUYER_REFUND_IN_SALES"];

            foreach($order_list as $value){
                $id = intval($value['id']);
                if(empty($value['response_content']) or $value['response_content'] == '[]') continue;

                $response_content = json_decode($value['response_content'],true,512,JSON_BIGINT_AS_STRING);// 解析成数据，注意BIG INT的科学计数法
                if(isset($response_content['message'][0]) and is_json($response_content['message'][0])){// 获取1688消息体
                    $data           = json_decode($response_content['message'][0],true,512,JSON_BIGINT_AS_STRING);// 解析成数据，注意BIG INT的科学计数法

                    // 29160 异常订单增加1688退货退款的模块
                    if(isset($data['type']) && in_array($data['type'], $refund_type)){
                        $this->ali_order_refund_model->subscribe_refund_data($data['data']);
                        $this->purchase_db->where('id',$id)->update('api_request_ali_log',['status' => 3]);// 标记已处理信息
                        continue;
                    }

                    // 34124  发送消息和自动确认
                    if(isset($data['type']) && in_array($data['type'], ['ORDER_BUYER_VIEW_ORDER_PRICE_MODIFY'])){
                        $this->ali_order_message_model->subscribe_change_price($data['data']);
                        $this->purchase_db->where('id',$id)->update('api_request_ali_log',['status' => 3]);// 标记已处理信息
                        continue;
                    }

                    $data           = $data['data'];

                    //物流单状态变更处理
                    //物流单发生变化的状态，包括发货（CONSIGN）、揽收（ACCEPT）、运输（TRANSPORT）、派送（DELIVERING）、签收（SIGN）
                    if(isset($data['OrderLogisticsTracingModel'])){
                        $TracingData = $data['OrderLogisticsTracingModel'];
                        $statusChanged = isset($TracingData['statusChanged']) ? $TracingData['statusChanged'] : '';
                        $express_no = isset($TracingData['mailNo']) ?$TracingData['mailNo'] :'';
                        switch ($statusChanged){
                            case 'ACCEPT':
                                //揽收（ACCEPT）-已揽收
                                $track_status = COLLECT_STATUS;
                                break;
                            case 'TRANSPORT':
                                //运输（TRANSPORT）-已发货
                                $track_status = SHIPPED_STATUS;
                                break;
                            case 'DELIVERING':
                                //派送（DELIVERING）-派送中
                                $track_status = DELIVER_STATUS;
                                break;
                            case 'SIGN':
                                //签收（SIGN）-已签收
                                $track_status = RECEIVED_STATUS;
                                break;
                            case 'CONSIGN':
                                //1688发货状态，系统不做处理
                            default:
                                $track_status = 0;
                                break;
                        }

                        $not_have = true;
                        // 如果是物流轨迹，则判断对应的采购单和轨迹状态 yefanli 20210720
                        if(!empty($express_no)){
                            $has_data = $this->purchase_db->from("purchase_logistics_info")->where(["express_no" => $express_no])->get()->result_array();
                            if(!$has_data || count($has_data) == 0){
                                $order_data = isset($TracingData['orderLogsItems']) ?$TracingData['orderLogsItems'] : [];
                                $aliorder = [];
                                foreach ($order_data as &$o_val){
                                    if(SetAndNotEmpty($o_val, 'orderId'))$aliorder[] = $o_val['orderId'];
                                }
                                $pai_number = false;
                                if(count($aliorder) > 0){
                                    $pai_number = $this->purchase_db->from("purchase_order_pay_type as pt")
                                        ->select("pt.*,it.sku")
                                        ->join("pur_purchase_order_items as it", "pt.purchase_number=it.purchase_number", "left")
                                        ->where_in("pt.pai_number", $aliorder)->get()->result_array();
                                }

                                if($pai_number && is_array($pai_number)){
                                    $cpCode = isset($TracingData['cpCode'])?$TracingData['cpCode']:"";
                                    $cpName = $this->purchase_db->from("logistics_carrier")->where("carrier_code=", $cpCode)->get()->row_array();
                                    $cpName = isset($cpName['carrier_name']) ? $cpName['carrier_name'] : '';
                                    foreach ($pai_number as &$pa_val){
                                        $add_query = [
                                            "purchase_number"           => $pa_val['purchase_number'],
                                            "sku"                       => $pa_val['sku'],
                                            "cargo_company_id"          => $cpName,
                                            "carrier_code"              => $cpCode,
                                            "express_no"                => $express_no,
                                            "status"                    => $track_status,
                                            "type"                      => 1,
                                            "create_time"               => date("Y-m-d H:i:s"),
                                        ];
                                        try{
                                            $this_date = date("Y-m-d H:i:s");
                                            $sql = "insert into pur_purchase_logistics_info (
                                                purchase_number, 
                                                sku, 
                                                cargo_company_id, 
                                                carrier_code, 
                                                express_no, 
                                                `status`, 
                                                `type`, 
                                                create_time
                                                ) VALUES (
                                                    '{$pa_val['purchase_number']}',
                                                    '{$pa_val['sku']}',
                                                    '{$cpName}',
                                                    '{$cpCode}',
                                                    '{$express_no}',
                                                    {$track_status},
                                                    1,
                                                    '{$this_date}'
                                                ) ON DUPLICATE KEY UPDATE
                                                    purchase_number = values(purchase_number) ,
                                                    sku = values(sku),
                                                    express_no = values(express_no)";
                                            $this->purchase_db->query($sql);
//                                            $this->purchase_db->insert("pur_purchase_logistics_info", $add_query);
                                            $this->purchase_db->where(["id" => $pa_val['id']])->update("purchase_order_pay_type", [
                                                "express_no" => $express_no,
                                                "cargo_company_id" => $cpName
                                            ]);
                                        }catch (Exception $e){}
                                    }
                                }

                                $not_have = false;
                            }
                        }

                        //根据快递单号更新物流单轨迹状态
                        if($not_have && $track_status && !empty($express_no)){
                            $this->_update_track_status($express_no,$track_status);
                        }
                        $this->purchase_db->update('api_request_ali_log',['post_content' => $express_no.'-'.$statusChanged,'status' => 3],['id'=>$id],1);// 标记已处理信息
                        continue;
                    }

                    $currentStatus  = strtolower($data['currentStatus']);
                    $orderId        = $data['orderId'];

                    if($debug) echo $orderId . '/'.$currentStatus.'<br>';

                    $this->purchase_db->where('id',$id)->update('api_request_ali_log',['post_content' => $orderId.'-'.$currentStatus,'status' => 3],null,1);// 标记已处理信息

                    $have_pay_type = $this->purchase_order_pay_type_model->get_one_by_pai_number($orderId);
                    if(empty($have_pay_type)){// 新系统不存在该订单

                        // 尝试 执行老系统-更新应付款时间
                        $res_old = getCurlData(OLD_PURCHASE.'/v1/ali-check-period-time/check-time-by-page?pai_number='.$orderId,'','get');

                        apiRequestLogInsert(
                            [
                                'record_number'    => $orderId,
                                'record_type'      => 'JAVA接收消息【From 1688】',
                                'post_content'     => $id,
                                'response_content' => $res_old,
                                'status'           => 1,
                            ],
                            'api_request_ali_log'
                        );
                    }
                    else{
                        $purchase_number = $have_pay_type['purchase_number'];
                        $wait_list = [ $purchase_number => $orderId];
                        // 消息分发处理
                        switch($currentStatus){
                            case 'waitbuyerreceive':// 等待买家收货：1688订单发货（买家视角） 之后的操作
                                $this->doUpdateOrderTrackingNumberByIds($wait_list,$debug);// 获取物流单号
                                $this->doUpdateOrderStatusByIds($wait_list,$debug);// 获取订单状态
                                break;
                            case 'waitsellersend':// 等待卖家发货：1688交易付款（买家视角） 之后的操作
                            case 'success':// 交易成功：1688交易成功
                            case 'cancel':// 交易取消：1688买家关闭订单/1688卖家关闭订单/1688运营后台关闭订单
                                $this->doUpdateOrderStatusByIds($wait_list,$debug);// 获取订单状态
                                break;
                            case 'confirm_goods_and_has_subsidy':// 确认收货：1688订单确认收货（买家视角） 之后的操作
                                // 抓取一下 应付款时间
                                getCurlData(CG_SYSTEM_APP_DAL_IP.'purchase_api/update_order_pay_account_date?purchase_number='.$purchase_number.'_'.$orderId,'','get');
                                break;
                        }
                    }
                }else{
                    $this->purchase_db->where('id',$id)->update('api_request_ali_log',['post_content' => '解析失败','status' => 4],null,1);// 标记已处理信息
                }
            }
            echo 'sss';exit;

        }while($flag);

        return true;
    }

    /**
     * 自动开启下个进程
     * @param string $url
     * @param array $params
     * @param string $type
     * @param int $timeout
     * @return boolean
     */
    public function throwTheader($url, $params = array(), $type = 'GET', $timeout = 60) {
        if(isset($params['page'])){
            apiPageCircleInsert(['page' => $params['page'],'api_type' => $url]);// 记录分页码
        }
        $urlInfo = parse_url($url);
        if (!isset($urlInfo['host']) || empty($urlInfo['host']))
            $urlInfo = parse_url($_SERVER['HTTP_HOST']);
        $host = isset($urlInfo['host']) ? $urlInfo['host'] : $_SERVER['HTTP_HOST'];
        $scheme = isset($urlInfo['scheme']) ? $urlInfo['scheme'] : '';
        $hostStr = $scheme . "://" . $host;
        $uri = str_replace($hostStr, '', $url);
        $port = isset($urlInfo['port']) ? $urlInfo['port'] : '80';
        if (empty($host))
            return false;
        $socket = fsockopen($host, $port, $errno, $error, $timeout);
        if (!$socket)
            return false;
        stream_set_blocking($socket, false);
        $data = '';
        $body = '';
        if (is_array($params)) {
            foreach ($params as $key => $value)
                $data .= strval($key) . '=' . strval($value) . '&';
        } else
            $data = $params;
        $header = '';
        if ($type == 'GET') {
            if (strpos($uri, '?') !== false) {
                $uri .= '&' . $data;
            } else {
                $uri .= '?' . $data;
            }
            $header .= "GET " . $uri . ' HTTP/1.0' . "\r\n";
        } else {
            $header .= "POST " . $uri . ' HTTP/1.0' . "\r\n";
            $header .= "Content-length: " . strlen($data) . "\r\n";
            $body = $data;
            //$header .=
        }
        $header .= "Host: " . $host . "\r\n";
        $header .= 'Cache-Control:no-cache' . "\r\n";
        $header .= 'Connection: close' . "\r\n\r\n";
        $header .= $body;
        //file_put_contents('./test.log', $header . "\r\n\r\n", FILE_APPEND);
        fwrite($socket, $header, strlen($header));
        usleep(300);   //解决nginx服务器连接中断的问题
        fclose($socket);
        return true;
    }

    /**
     * 取消1688订单
     */
    public function cancel_ali_order($data){
        $return = ['code' => false,'message' => '','data' => ''];
        $purchase_number  = $data['purchase_number'];
        $pai_number       = $data['pai_number'];
        $note       = $data['note'];
        if(!$this->checkPurOrderExecuted($purchase_number)){
            $return['message'] = "采购单[$purchase_number],拍单号对应的[$pai_number]拍单号,未生成了1688订单";
            return $return;
        }
        $this->purchase_db->trans_begin();
        try{
            $result = $this->get_order_info($purchase_number);
            if(empty($result['code'])) {
                $return['message'] = '根据采购订单号'.$purchase_number.'的拍单号获取1688订单信息失败!';
                return $return;
            }

            $insert_cancel= $this->add_cancel_ali_order($purchase_number,$pai_number);
            if($insert_cancel){
                $bool = $this->purchase_db->delete($this->table_name,array('purchase_number'=>$purchase_number));
                if($bool){
                    $bool_item = $this->purchase_db->delete($this->table_name_item,array('purchase_number'=>$purchase_number));
                    if(!$bool_item){
                        $return['message'] = '1688下单订单明细记录更新失败!';
                        return $return;
                    }
                }else{
                    $return['message'] = '1688下单订单记录更新失败!';
                    return $return;
                }
            }else{
                $return['message'] = '添加1688取消记录失败!';
                return $return;
            }
            // 更新 采购单-1688相关状态
            $this->Purchase_order_model->update_order_ali_status($purchase_number, ['is_ali_order' => 0,'ali_order_status' => '','ali_order_amount' => '','is_ali_abnormal' => 0,'is_ali_price_abnormal' => 0]);

            // 删除  采购单确认记录
            $this->load->model('finance/purchase_order_pay_type_model');
            $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);
            if($have_pay_type){
                $result = $this->purchase_order_pay_type_model->delete_one($have_pay_type['id']);
                if(empty($result)){
                    $return['message'] = '采购单确认记录更新失败!';
                    return $return;
                }
            }

            $ali_result = $this->aliorderapi->getTradeCancel($pai_number,$note);
            // 取消订单成功
            if($ali_result['code']){
                operatorLogInsert([
                    'id'      => $purchase_number,
                    'type'    => 'purchase_order',
                    'content' => '取消1688订单成功',
                    'detail'  => '采购单:'.$purchase_number.'由于['.$note.']原因,取消1688'.$pai_number.'订单!',
                ]);
            }else{
                $return['message'] = $ali_result['errorMsg'];
                return $return;
            }
            $this->purchase_db->trans_commit();
            $return['code'] = true;
        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $return['message'] = $e->getMessage();
        }
        return $return;
    }

    /**
     * 添加取消的记录
     */
    public function add_cancel_ali_order($purchase_number,$pai_number){
        $sql = "insert into pur_ali_order_cancel select * from pur_ali_order  where order_id='".$pai_number."'";
        $result = $this->purchase_db->query($sql);
        if($result){
            $sql = "insert into pur_ali_order_cancel_items select * from pur_ali_order_items  where purchase_number='".$purchase_number."'";
            $result = $this->purchase_db->query($sql);
            return $result;
        }else{
            return false;
        }
    }

    /**
     * 1688 刷新订单金额
     * @author Jolon
     * @param $purchase_numbers
     * @return array
     */
    public function refresh_order_price($purchase_numbers){
        $return         = ['code' => false,'message' => '','data' => ''];
        $error_list     = [];
        $success_list   = [];

        // 获取 待刷新1688金额的 订单列表
        $this->load->model('finance/purchase_order_pay_type_model');
        $have_pay_type = $this->purchase_db->select('id,purchase_number,pai_number,freight,discount')
            ->where_in('purchase_number',$purchase_numbers)
            ->get($this->purchase_order_pay_type_model->table_name())
            ->result_array();
        if(empty($have_pay_type)){
            $return['message'] = '未获取1688拍单号信息';
            return $return;
        }

        $have_pay_type_list         = array_column($have_pay_type,'pai_number','purchase_number');
        $have_pay_type_price_list   = arrayKeyToColumn($have_pay_type,'purchase_number');
        foreach($have_pay_type_list as $purchase_number => $pai_number){
            if(empty($pai_number) or strlen($pai_number) <= 16){
                $error_list[$purchase_number] = '：刷新失败，拍单号错误';
                continue;
            }
        }

        // 获取 1688 订单信息
        $aliOrderInfoList = $this->aliorderapi->getListOrderDetail(null,array_values($have_pay_type_list));

        foreach($have_pay_type_list as $purchase_number => $pai_number){
            try{
                if(!isset($aliOrderInfoList[$pai_number])){
                    $error_list[$purchase_number] = '：获取1688订单信息失败';
                    continue;
                }
                if(empty($aliOrderInfoList[$pai_number]['data']) and isset($aliOrderInfoList[$pai_number]['msg'])){
                    $error_list[$purchase_number] = $aliOrderInfoList[$pai_number]['msg'];
                    continue;
                }
                if(!isset($have_pay_type_price_list[$purchase_number])){
                    $error_list[$purchase_number] = '：获取采购系统订单确认信息失败';
                    continue;
                }

                $now_have_pay_type = $have_pay_type_price_list[$purchase_number];

                // 更新 1688 订单金额信息
                $aliOrderData         = $aliOrderInfoList[$pai_number]['data'];
                $baseInfo             = $aliOrderData['baseInfo'];
                $aliSumProductPayment = $baseInfo['sumProductPayment'];// 商品总金额
                $aliTotalAmount       = $baseInfo['totalAmount'];// 订单总金额
                $aliShippingFee       = $baseInfo['shippingFee'];// 运费
                $aliDiscount          = $baseInfo['discount'];// 优惠额

                $update_ali_order = [
                    'total_success_amount' => $aliTotalAmount,
                    'discount'             => $aliDiscount,
                    'post_fee'             => $aliShippingFee,
                ];
                $this->purchase_db->where('purchase_number',$purchase_number)->update($this->table_name,$update_ali_order);

                // 更新采购系统订单金额信息，标记是否1688金额异常
                $orderInfo          = $this->Purchase_order_model->get_one($purchase_number);
                $itemsList          = $orderInfo['items_list'];
                $totalProductMoney  = 0;
                foreach($itemsList as $item){
                    $totalProductMoney += format_price($item['confirm_amount'] * $item['purchase_unit_price']);
                }
                $totalAmount = $totalProductMoney + $now_have_pay_type['freight'] - $now_have_pay_type['discount'];// 采购订单总金额

                if(bccomp($totalAmount,$aliTotalAmount,2) === 0){// 比较总金额
                    $is_ali_price_abnormal = 0;// 0.正常
                }else{
                    $is_ali_price_abnormal = 1;// 1.异常
                }

                $update_order = [
                    'ali_order_amount'      => $aliTotalAmount,
                    'is_ali_price_abnormal' => $is_ali_price_abnormal
                ];
                $this->Purchase_order_model->update($update_order,['purchase_number' => $purchase_number]);

                $success_list[$purchase_number] = '：刷新金额成功';
            }catch(Exception $e){
                $error_list[$purchase_number] = $e->getMessage();
            }
        }

        $data              = [
            'success_list' => $success_list,
            'error_list'   => $error_list
        ];
        $return['code']    = true;
        $return['data']    = $data;
        $return['message'] = '刷新成功';

        return $return;

    }

    /**
     * @desc 手动刷新1688订单是否异常
     * @author Jeff Jolon
     * @Date 2019/6/28 11:52
     * @param $purchase_number
     * @return
     */
    public function fresh_ali_order_abnormal($purchase_number)
    {
        $return = ['code' => 0, 'msg' => '', 'data' => ''];
        $this->load->helper('status_1688');
        $aliOrderStatus = aliOrderStatus();
        $this->purchase_db->trans_start();
        try {
            $orderList = $this->purchase_db
                ->select('a.id,order_id,purchase_account,order_status,o.purchase_order_status')
                ->from('ali_order a')
                ->join('purchase_order o','o.purchase_number=a.purchase_number','left')
                ->where("a.purchase_number",$purchase_number)
                //->where_in('order_status', ['WAITBUYERPAY', 'WAITSELLERSEND', 'WAITLOGISTICSTAKEIN', 'WAITBUYERRECEIVE', 'WAITBUYERSIGN', 'CONFIRM_GOODS'])
                ->order_by('a.id desc')
                ->get('',1)
                ->result_array();//获取最新一条

            if (empty($orderList)) {
                throw new Exception('订单异常（可能还没1688下单）');
            }

            if ($orderList and is_array($orderList)) {
                foreach ($orderList as $order) {
                    $id                    = $order['id'];
                    $order_id              = $order['order_id'];
                    $purchase_order_status = $order['purchase_order_status'];

                    $order = $this->aliorderapi->getListOrderDetail(null, $order_id);

                    if (isset($order[$order_id]) and $order[$order_id]['code'] == 200) {
                        $baseInfo = $order[$order_id]['data']['baseInfo'];
                        $status = strtoupper($baseInfo['status']);

                        $this->purchase_db->where('id', $id)->update($this->table_name, ['order_status' => $status]);

                        if (isset($aliOrderStatus[$baseInfo['status']])){
                            $status = $aliOrderStatus[$baseInfo['status']];
                        }
                        if ( ($status=='交易取消'||$status=='交易终止')
                            && in_array($purchase_order_status,[
                                //PURCHASE_ORDER_STATUS_WAITING_QUOTE, 该状态不能刷新，会导致订单无法推送到仓库
                                PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                                PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,
                                PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                                PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                                PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                                PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT,
                            ])){
                            $this->Purchase_order_model->update_order_ali_status($purchase_number, ['ali_order_status' => $status,'is_ali_abnormal'=>1]);
                        }else{
                            $this->Purchase_order_model->update_order_ali_status($purchase_number, ['ali_order_status' => $status,'is_ali_abnormal'=>0]);
                        }
                    }
                }
            }
            $this->purchase_db->trans_commit();
            $return['code'] = 1;

        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }


    /**
     * @desc 1688下单确认/1688批量下单确认 获取编辑数据
     * @author Jeff
     * @Date 2019/11/1 17:02
     * @param string $purchase_number
     * @param string $total_freight
     * @param string $total_discount
     * @param string $total_process_cost
     * @return
     */
    public function get_order_sku_infos($purchase_number, $total_freight=0, $total_discount=0,$total_process_cost=0)
    {
        $purchase_order_info = $this->Purchase_order_model->get_one_with_demand_number($purchase_number);
        if(empty($purchase_order_info) or empty($purchase_order_info['items_list'])){
            return [];
        }

        $total_price = 0;
        //计算总的采购金额保留两位小数
        foreach ($purchase_order_info['items_list'] as &$value){
            $total_price += round($value['purchase_unit_price']*$value['confirm_amount'],2);
        }

        $sum_freight = 0;//除了最后一个sku的运费分摊总和
        $sum_discount = 0;//除了最后一个sku的优惠分摊总和
        $sum_process_cost = 0;//除了最后一个sku的加工费分摊总和

        foreach ($purchase_order_info['items_list'] as $key => &$value){
            $cancel_data = $this->Purchase_order_model->get_order_cancel_list($value['purchase_number'],$value['sku']);
            if (!empty($cancel_data)){
                $value['cancel_amount'] = $cancel_data[$value['sku']];
            }else{
                $value['cancel_amount'] = 0;
            }

            if ($key == count($purchase_order_info['items_list'])-1){
                $value['freight'] = format_two_point_price($total_freight-$sum_freight);
                $value['discount'] = format_two_point_price($total_discount-$sum_discount);
                $value['process_cost'] = format_two_point_price($total_process_cost-$sum_process_cost);
            }else{
                $value['freight'] = format_two_point_price($total_freight*round($value['purchase_unit_price']*$value['confirm_amount'],2)/$total_price);
                $value['discount'] = format_two_point_price($total_discount*round($value['purchase_unit_price']*$value['confirm_amount'],2)/$total_price);
                $value['process_cost'] = format_two_point_price($total_process_cost*round($value['purchase_unit_price']*$value['confirm_amount'],2)/$total_price);
                $sum_freight+=$value['freight'];
                $sum_discount+=$value['discount'];
                $sum_process_cost+=$value['process_cost'];
            }
        }
        unset($sum_freight);
        unset($sum_discount);
        unset($sum_process_cost);
        $my_order_data = $purchase_order_info['items_list'];

        return $my_order_data;
    }

    /**
     * totoro
     * 根据PO号查询备货单是否退税
     */
    public function select_demand_is_drawback($purchase_number){
        $tax_list =[];
        $demand_number ='';
        $sql ="select a.is_drawback,a.demand_number from pur_purchase_suggest a join pur_purchase_suggest_map b on a.demand_number = b.demand_number where b.purchase_number='".$purchase_number."'";
        $resultList = $this->purchase_db->query($sql)->result_array();
        if(!empty($resultList)){
            foreach ($resultList as $value){
                if($value['is_drawback']==1){
                    $tax_list[] = $value['demand_number'];
                }
            }

        }
        if(!empty($tax_list)){
            $demand_number= implode($tax_list,',');
        }
        return $demand_number;
    }

    /**
     * 根据快递单号更新状态
     * @param $express_no
     * @param $track_status
     */
    private function _update_track_status($express_no, $track_status)
    {
        $set_data = array(
            'status' => $track_status,
            'update_time' => date('Y-m-d H:i:s')
        );
        $where = array(
            'express_no' => $express_no,
            'status <' => $track_status
        );
        $this->purchase_db->update($this->table_logistics_info, $set_data, $where);
    }

    /**
     * 根据1688推送物流公司数据，转换内部快递公司编码
     * @param $company_id 1688物流公司id
     * @param $company_name 1688物流公司名称
     * @return string
     */
    private function _get_carrier_code($company_id, $company_name)
    {
        if (empty($company_id) && empty($company_name)) return 'other';

        if (8 == $company_id) {
            $query = $this->purchase_db;
            $query->select('carrier_code');
            $query->from('pur_logistics_carrier');
            $query->like('carrier_name', mb_substr($company_name, 0, 2));
            //查询数据
            $result = $query->get()->row_array();
            if (!empty($result)) {
                $carrier_code = $result['carrier_code'];
            } else {
                $carrier_code = 'other';
            }
            return $carrier_code;
        }

        $carrier_code = $this->rediss->getData('CARRIER_CODE_INFO_' . $company_id);
        if (empty($carrier_code)) {
            $query = $this->purchase_db;
            $query->select('carrier_code_100');
            $query->from('pur_logistics_carrier_1688');
            $query->where('id', $company_id, false);
            //查询数据
            $result = $query->get()->row_array();
            //不存在，则新增1688物流公司，编码不缓存，需要人工在数据表维护
            if (empty($result)) {
                $query->insert('pur_logistics_carrier_1688', ['id' => $company_id, 'carrier_name_1688' => $company_name]);
                $carrier_code = 'other';
                return $carrier_code;
            }

            //系统自动新增的1688物流公司，人工为进行维护的统一返回‘other’，编码不缓存
            if (empty($result['carrier_code_100'])) {
                $carrier_code = 'other';
            } else {
                $carrier_code = $result['carrier_code_100'];
                //缓存规则
                $this->rediss->setData('CARRIER_CODE_INFO_' . $company_id, $carrier_code);
            }

        }
        return $carrier_code;
    }

    /**
     * 根据采购单获取1688下单
     */
    public function get_ali_order_list($purchase_number)
    {
        $data_ali = $this->purchase_db->from($this->table_name)
            ->where_in('purchase_number', $purchase_number)
            ->select('purchase_number, order_id')
            ->get()
            ->result_array();
        $res = [];
        if($data_ali && count($data_ali) > 0){
            foreach ($data_ali as $val){
                $res[] = [
                    'purchase_number' => $val['purchase_number'],
                    'order_id' => $val['order_id'],
                ];
            }
        }

        $data_pur = $this->purchase_db->from('purchase_order_pay_type')
            ->where_in('purchase_number', $purchase_number)
            ->where("pai_number != ", "")
            ->select('purchase_number, pai_number')
            ->get()
            ->result_array();

        if($data_pur && count($data_pur) > 0){
            foreach ($data_pur as $val){
                $res[] = [
                    'purchase_number' => $val['purchase_number'],
                    'order_id' => $val['pai_number'],
                ];
            }
        }
        return $res;
    }

    /**
     * 获取物流信息
     */
    public function get_purchase_logistics_info($purchase_number)
    {
        return $this->purchase_db->from('purchase_logistics_info')
            ->where_in('purchase_number', $purchase_number)
            ->select('purchase_number, express_no')
            ->get()
            ->row_array();
    }

    /**
     * 更新1688账期信息
     */
    public function update_account_period_time($order_id=false, $account=false)
    {
        if(!$order_id || !$account)return false;

        $orderList = $this->purchase_db->select('purchase_number')
            ->where('pai_number',$order_id)
            ->get('purchase_order_pay_type')
            ->result_array();
        $purchase_numbers = array_column($orderList,'purchase_number');
        if($purchase_numbers){
            $this->purchase_db->where_in('purchase_numbers', $purchase_numbers)->update('purchase_order_pay_type', ['accout_period_time' => $account]);
            $this->purchase_db->where_in('purchase_numbers', $purchase_numbers)->update('purchase_order_items', ['need_pay_time' => $account]);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取产品对应的ali id信息
     */
    public function get_verify_ali_product($sku)
    {
        $data = $this->purchase_db->from('ali_product')
            ->select("sku,product_id,sku_id,spec_id")
            ->where_in("sku", $sku)
            ->get()
            ->result_array();
        if($data && count($data))return $data;
        return [];
    }

    /**
     * 刷新1688信息
     */
    public function refresh_ali_order_data($pur_number=[], $res = [])
    {
        // 验证是否1688已下单
        $is_ali_order = $this->get_ali_order_list($pur_number);

        if(!$is_ali_order || count($is_ali_order) == 0){
            $res['errorMess'] = '采购单没有向1688下单，请确认后再操作';
            return $res;
        }
        $this->load->model('purchase/Purchase_order_progress_model');
        $this->load->model('Logistics_info_model');

        // 刷新金额
        try{
            $res_price = $this->refresh_order_price($pur_number);
            if(isset($res_price['code'])){
                $res['price']['list']     = $res_price['data']['error_list'];
                $res['price']['total']    = count($res_price['data']['error_list']);
            }
        }catch (Exception $e){}

        foreach ($pur_number as $val){
            // 刷新状态
            try{
                $res_status = $this->fresh_ali_order_abnormal($val);
                if (!$res_status['code']) {
                    $res['order_status']['list'][] = $val;
                    $res['order_status']['total']++;
                }
            }catch (Exception $e){}

            // 刷新物流轨迹
            try{
                $logistics_list = $this->get_purchase_logistics_info($val);
                if(!$logistics_list || !isset($logistics_list['express_no']))break;
                $logistics = $this->Purchase_order_progress_model->express_is_exists(1, [$logistics_list['express_no']]);
                if(!$logistics && count($logistics['data']) == 0)break;
                foreach ($logistics['data'] as $key =>$item){
                    if( RECEIVED_STATUS == $item)unset($logistics['data'][$key]);
                }
                $exception_data = array();//更新异常数据
                $success_data = array();//更新成功数据
                $unchanged_data = array();//未发生变化数据

                //快递单号数据对应物流轨迹状态
                $real_express_data = $logistics['data'];
                $express_no_arr = array_keys($real_express_data);
                $express_no_arr_1688 = array();//成功获取到轨迹状态的1688单快递单号

                //1.根据快递单号判断是否为1688单，是则优先通过1688接口获取轨迹详情
                if(empty($express_no_arr))continue;
                $warehouse_address = $this->Logistics_info_model->get_warehouse_address();//所有仓库地址信息,仅当order_type=1时，才需要仓库地址匹配提货点，退货单不需要匹配提货点
                $order_info = $this->Logistics_info_model->get_pai_number_info($express_no_arr);

                $pai_number_arr = array_column($order_info, 'pai_number');
                foreach (array_chunk($pai_number_arr, 100) as $pai_number) {
                    $result = $this->aliorderapi->listLogisticsTraceInfo($pai_number);
                    if(!$result['code']) {
                        operatorLogInsert(
                            [
                                'type' => 'PUR_PURCHASE_REFRESH_LOGISTICS_STATE',
                                'content' => '手动刷新  轨迹状态失败',
                                'detail' => '1688接口获取轨迹详情失败[code:001]',
                                'user' => getActiveUserName(),
                            ]);
                        continue;
                    }

                    //循环处理每条快递单数
                    foreach ($result['data'] as $key => $item) {
                        if (!isset($order_info[$key])) continue;
                        $carrier_code = $order_info[$key]['carrier_code'];
                        $express_no = $express_no_arr_1688[] = $order_info[$key]['express_no'];
                        $old_status = $order_info[$key]['status'];
                        $order_type = 1;
                        //轨迹详情（最新记录！！必须！！在前面！！！匹配效率最高）
                        foreach ($item['remark'] as $remark) {
                            $status = $this->Logistics_info_model->resolve_logistics_tracks($warehouse_address, $carrier_code, $express_no, $remark);

                            //匹配成功，更新快递单物流数据状态(重新抓取轨迹状态未发生变化则不更新)
                            if ($status && ($status > $old_status)) {
                                $where = array('express_no' => $express_no);
                                $update_data = array('status' => $status, 'update_time' => date('Y-m-d H:i:s'));
                                $update_res = $this->Logistics_info_model->update_express_order($order_type, $where, $update_data);
                                if ($update_res) {
                                    //写入操作日志表
                                    operatorLogInsert(
                                        array(
                                            'id' => $express_no,
                                            'type' => 'PUR_PURCHASE_REFRESH_LOGISTICS_STATE',
                                            'content' => '手动刷新轨迹状态成功',
                                            'detail' => '手动刷新轨迹状态,由' . $old_status . '更新为' . $status,
                                            'user' => getActiveUserName(),
                                        )
                                    );
                                    $success_data[] = $express_no;
                                } else {
                                    //更新失敗
                                    //写入操作日志表
                                    operatorLogInsert(
                                        array(
                                            'id' => $express_no,
                                            'type' => 'PUR_PURCHASE_REFRESH_LOGISTICS_STATE',
                                            'content' => '手动刷新轨迹状态失败',
                                            'detail' => '手动刷新轨迹状态失败',
                                            'user' => getActiveUserName(),
                                        )
                                    );
                                    $exception_data[] = $express_no;
                                }
                                break;
                            }
                        }
                    }
                }
            }catch (Exception $e){}
        }

        foreach ($is_ali_order as $val){
            // 账期   刷新应付款时间
            try{
                $account = $this->Purchase_order_model->get_ali_order_info($val['order_id']);
                if($account && $account['status'] == "success" && $account['accoutPeriodTime'] != "'0000-00-00 00:00:00'" &&
                    $this->update_account_period_time($val['order_id'], $account['accoutPeriodTime'])){}else{
                    $res['periodTime']['list'][] = $val;
                    $res['periodTime']['total']++;
                }
            }catch (Exception $e){}
        }

        return $res;
    }

    /**
     * 全量刷新1688信息
     */
    public function refresh_ali_order_data_all($purchase_number=[], $is_system=false)
    {
        $res = ['status' => 0, "errorMess" => '没有要处理的数据'];
        if(empty($purchase_number))return $res;
        $pur_list = array_unique($purchase_number);
        $aliorder = $this->purchase_db->from("purchase_order_pay_type as t")
            ->join("pur_purchase_order as o", "t.purchase_number=o.purchase_number", "inner")
            ->select('t.pai_number,t.purchase_number,o.purchase_order_status')
            ->where_in('t.purchase_number', $pur_list)->get()->result_array();
        if(empty($aliorder))return $res;
        $ali_list = $aliorder;
        $aliorder = array_column($aliorder, 'pai_number');
        if(empty($aliorder))return $res;
        $baseOrder = $this->aliorderapi->getListOrderDetail(null, $aliorder);
        //$logistics = $this->aliorderapi->listLogisticsTraceInfo($aliorder);

        $this->purchase_db->trans_start();
        try {
            $ali_order_upd = []; // pur_ali_order 表信息
            $pur_upd = []; // pur_purchase_order 表
            $pay_t_upd = []; // pur_purchase_order_pay_type 表
            if($baseOrder && !empty($baseOrder)){
                foreach ($baseOrder as $key=>$val){
                    if(!in_array($key, $aliorder) || !isset($val['code']) || $val['code'] != 200 || !isset($val['data']))continue;
                    $ali_row = $pur_row = $pay_t_row = [];
                    $pur_number = false;
                    $pur_status = false;
                    foreach ($ali_list as $pv){
                        if($key == $pv['pai_number']){
                            $pur_number = $pv['purchase_number'];
                            $pur_status = $pv['purchase_order_status'];
                        }
                    }
                    $val = $val['data'];
                    $ali_row['order_id'] = $key;
                    if($pur_number){
                        $pur_row['purchase_number'] = $pur_number;
                        $pay_t_row['purchase_number'] = $pur_number;
                    }
                    $BizInfo = isset($val['orderBizInfo'])?$val['orderBizInfo']:false;
                    if(isset($val['baseInfo'])){
                        $bInfo = $val['baseInfo'];
                        $ali_row['discount'] = $bInfo['discount'];
                        $ali_row['post_fee'] = $bInfo['shippingFee'];
                        $ali_row['order_amount'] = $bInfo['totalAmount'];

                        if($bInfo['refundStatus'] == 'refundsuccess')$pur_row['refund_status'] = 2;
                        $pur_row['ali_order_status'] = $bInfo['status'];
                        $pur_row['ali_order_amount'] = $bInfo['totalAmount'];

                        // 账期数据
                        if((isset($bInfo['tradeTypeDesc']) && $bInfo['tradeTypeDesc'] =='账期交易') ||
                            (isset($bInfo['flowTemplateCode']) && $bInfo['flowTemplateCode'] == "accountPeriod30min")){
                            $pay_t_row['accout_period_time'] = !isset($BizInfo['accountPeriodTime']) || empty($BizInfo['accountPeriodTime']) ? '0000-00-00 00:00:00' : $BizInfo['accountPeriodTime'];
                        }

                        if(!empty($bInfo['refundPayment'])){
                            $pay_t_row['apply_amount'] = $bInfo['refundPayment'];
                        }

                        // 获取退款数据
                        if(!empty($bInfo['refundStatus']) && $bInfo['refundStatus'] == 'refundsuccess'){
                            $refund = $this->aliorderapi->getListOrderRefund($key, true);
                            if($refund && isset($refund['code']) && $refund['code'] == 200){
                                $refData = $refund['data'][0];
                                $pay_t_row['apply_carriage'] = SetAndNotEmpty($refData, 'applyCarriage')?$refData['applyCarriage'] / 100:0;

                                // pur_ali_order_refund
                                $has_refund = $this->purchase_db->from('ali_order_refund')->where("pai_number=", $key)->get()->result_array();
                                $refund_row = [
                                    'completed_time'        => SetAndNotEmpty($refData, 'gmtCompleted') ? $refData['gmtCompleted']: '0000-00-00 00:0000',
                                    'refund_status'         => SetAndNotEmpty($refData, 'status') ? $refData['status']: '',
                                    'buyer_user'            => SetAndNotEmpty($refData, 'buyerMemberId') ? $refData['buyerMemberId']: '',
                                    'goods_status'          => SetAndNotEmpty($refData, 'goodsStatus') ? $refData['goodsStatus']: 0,
                                    'apply_reason_text'     => SetAndNotEmpty($refData, 'extInfo') && SetAndNotEmpty($refData['extInfo'], 'apply_reason_text') ? $refData['extInfo']['apply_reason_text']: '',
                                    'ali_payment_id'        => SetAndNotEmpty($refData, 'alipayPaymentId') ? $refData['alipayPaymentId']: '',
                                    'refund_carriage'       => SetAndNotEmpty($refData, 'applyCarriage', 'n') ? $refData['applyCarriage'] / 100:0,
                                    'refund_payment'        => SetAndNotEmpty($refData, 'refundPayment', 'n') ? $refData['refundPayment'] / 100:0,
                                    'refund_type'           => 1,
                                ];
                                if($has_refund && !empty($has_refund)){
                                    $this->purchase_db->where("pai_number=", $key)->update('ali_order_refund', $refund_row);
                                }else{
                                    $refund_row['apply_uid'] = 0;
                                    $refund_row['apply_carriage'] = SetAndNotEmpty($refData, 'applyCarriage', 'n') ? $refData['applyCarriage'] / 100:0;
                                    $refund_row['apply_payment'] = SetAndNotEmpty($refData, 'applyPayment', 'n') ? $refData['applyPayment'] / 100:0;
                                    $refund_row['apply_amount'] = SetAndNotEmpty($refData, 'refundPayment', 'n') ? $refData['refundPayment'] / 100:0;
                                    $refund_row['apply_reason'] = SetAndNotEmpty($refData, 'applyReason') ? $refData['applyReason']: '';
                                    $refund_row['apply_user'] = '系统拉取';
                                    $refund_row['refund_number'] = SetAndNotEmpty($refData, 'refundId') ? $refData['refundId']: false;
                                    if($refund_row['refund_number'])$this->purchase_db->insert('ali_order_refund', $refund_row);
                                }
                            }
                            $pay_t_row['apply_amount'] = $bInfo['refundPayment'];
                        }

                        if (in_array($bInfo['status'], ['交易取消', '交易终止', 'cancel', 'terminated']) && in_array($pur_status,[
                                //PURCHASE_ORDER_STATUS_WAITING_QUOTE, 该状态不能刷新，会导致订单无法推送到仓库
                                PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                                PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,
                                PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                                PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                                PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                                PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT,
                            ])){
                            $pur_row['is_ali_abnormal'] = 1;
                        }else{
                            $pur_row['is_ali_abnormal'] = 0;
                        }
                    }

                    $ali_order_upd[] = $ali_row;
                    $pur_upd[] = $pur_row;
                    if(isset($pay_t_row['accout_period_time']))$pay_t_upd[] = $pay_t_row;
                }
            }

            // 批量刷新1688订单信息
            if(!empty($ali_order_upd))$this->purchase_db->update_batch($this->table_name, $ali_order_upd, 'order_id');

            // 批量刷新采购单表信息
            if(!empty($pur_upd))$this->purchase_db->update_batch('pur_purchase_order', $pur_upd, 'purchase_number');

            // 批量刷新请款单表
            if(!empty($pay_t_upd)){
                $this->purchase_db->update_batch('pur_purchase_order_pay_type', $pay_t_upd, 'purchase_number');

                // 更新应付款时间
                foreach($pay_t_upd as $pay_t_value){
                    if(isset($pay_t_value['accout_period_time']) and isset($pay_t_value['purchase_number'])){
                        $this->purchase_order_pay_type_model->update_ali_accout_period_time($pay_t_value['purchase_number'],null,$pay_t_value['accout_period_time']);
                    }
                }
            }

            $this->purchase_db->trans_commit();
            if($this->purchase_db->trans_status() === false){
                $this->purchase_db->trans_rollback();
                $res['errorMess'] = '提交失败，请联系IT处理！';
            }else{
                $res['status'] = 1;
                $res['errorMess'] = '更新成功';
            }
        }catch (\Exception $e) {
            $res['errorMess'] = '提交失败：'.$e->getMessage();
            $this->purchase_db->trans_rollback();
        }
        return $res;
    }

}


