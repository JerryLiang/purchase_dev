<?php
/**
 * Created by PhpStorm.
 * 采购需求数据库模型类
 * User: Jolon
 * Date: 2018/12/27 0027 11:23
 */

class Purchase_suggest_model extends Purchase_model {

    protected $table_name   = 'purchase_suggest';// 数据表名称
    protected $table_name_map   = 'purchase_suggest_map';// 数据表名称
    protected $table_product_name = 'product'; // 产品表

    public function __construct(){
        parent::__construct();
        $this->load->model('purchase_suggest_map_model','',false,'purchase_suggest');
        $this->load->model('purchase_order_model','',false,'purchase');
        $this->load->model('purchase_order_sum_model');
        $this->load->model('purchase/Reduced_edition_model','reduced_edition');
        $this->load->model('supplier/Supplier_purchase_amount');
        $this->load->model('purchase_suggest/suggest_lock_model');
        $this->load->model('abnormal/Product_scree_model');
        $this->load->model('product/product_model');
        $this->load->model('supplier/Supplier_payment_info_model');
        $this->load->model('supplier/Supplier_model');
        $this->load->model('system/Reason_config_model');

    }

    /**
     * 更新需求单数据
     * @params $where  array  更新条件
     **/
    public function update_demand($where,$updata,$type=false){

        if( false == $type) {
            return $this->purchase_db->where($where)->update('purchase_demand', $updata);
        }else{
            //print_r($where);die();s
            $query = $this->purchase_db;
            foreach($where as $where_key=>$where_value){

                if(is_array($where_value)){

                    $query->where_in($where_key,$where_value);
                }else{
                    $query->where($where_key,$where_value);
                }
            }
            return  $query->update('purchase_demand', $updata);
        }
    }

    /**
     * 获取SKU 的产品信息
     *
     **/
    private function get_product($skus,$purchase_type){
        // 重新获取一次需求单产品信息
        foreach($purchase_type as $key=>$value){

            if($value == PURCHASE_TYPE_FBA_BIG){
                $purchase_type[$key] = PURCHASE_TYPE_OVERSEA;
            }

            if($value == PURCHASE_TYPE_PFB){

                $purchase_type[$key] = PURCHASE_TYPE_INLAND;
            }

            if($value == PURCHASE_TYPE_PFH){

                $purchase_type[$key] = PURCHASE_TYPE_FBA;
            }
        }
        $sku_info = $this->purchase_db->select('b.supplier_code,b.supplier_name,a.is_purchasing,a.sku,c.buyer_type,a.product_img_url,a.product_name,a.product_line_id,a.supplier_code,a.supplier_name,
                                a.purchase_price,a.create_id,a.create_user_name,a.is_drawback,b.is_cross_border,b.supplier_settlement,c.buyer_id,c.buyer_name,
                                d.linelist_cn_name,a.product_status,a.state_type')->from('product a')
            ->join('supplier b', 'a.supplier_code=b.supplier_code', 'left')
            ->join('supplier_buyer c', "b.supplier_code=c.supplier_code and c.status=1 and c.buyer_type IN (".implode(",",$purchase_type).")", 'left')
            ->join('product_line d', 'a.product_line_id=d.product_line_id', 'left')
            ->where_in("a.sku",$skus)
            ->get()
            ->result_array();
        if(empty($sku_info)){

            return NULL;
        }
        $returnData = [];
        foreach($sku_info as $sku_info=>$sku_value){

            $key = $sku_value['sku']."-".$sku_value['buyer_type'];
            if(!isset($returnData[$key])){

                $returnData[$key] = $sku_value;
            }
        }
        //print_r($returnData);die();

        return $returnData;
    }

    /**
     * 合并需求单逻辑
     * @params $mereSuggest    array   符合条件的需求单
     * @author:luxu
     * @time:2021年3月3号
     **/

    public function mereSuggest($mereSuggest){

        $mereDatas = []; // 定义合单数据
        $skusData = array_column($mereSuggest,'sku'); // 获取合并需求单的SKU
        $purchase_type = array_unique(array_column($mereSuggest,'purchase_type_id')); // 需求单业务线ID获取
        $productDatas = $this->get_product($skusData,$purchase_type);
        $datas = array_column($mereSuggest,'demand_data');
        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');
        $pertain_wms_list = $this->Warehouse_model->get_pertain_wms_list();
        $pertain_wms_list = array_column($pertain_wms_list,'pertain_wms_name','warehouse_code');
        //echo "count=".count($mereSuggest);die();
        foreach($mereSuggest as $mereKey=>$mereValue){

            $mereKeys = $mereValue['sku']."-".$mereValue['is_drawback']."-".$mereValue['shipment_type']."-".$mereValue['gwarehouse_code'];
            if( !isset($mereDatas[$mereKeys])){
                $mereDatas[$mereKeys] = $mereValue;
                $mereDatas[$mereKeys]['demand_data'] =0;
                $mereDatas[$mereKeys]['demand_datas_number'] =[];// 需求单
                $mereDatas[$mereKeys]['demand_name'] = []; // 需求单类型
                $mereDatas[$mereKeys]['demand_name_id'] = []; // 需求单类型ID
                $mereDatas[$mereKeys]['is_distribution'] = [];
                $mereDatas[$mereKeys]['peration_wms_code'] = $mereDatas[$mereKeys]['peration_wms_name'] = NULL;
                $mereDatas[$mereKeys]['purchase_type_id'] = [];
            }

            $key = $mereValue['sku']."-".$mereValue['purchase_type_id'];
            $mereDatas[$mereKeys]['is_merege'] = true;
            $mereDatas[$mereKeys]['supplier_code'] = isset($productDatas[$key]['supplier_code'])?$productDatas[$key]['supplier_code']:'';
            $mereDatas[$mereKeys]['supplier_name'] = isset($productDatas[$key]['supplier_name'])?$productDatas[$key]['supplier_name']:'';
            $mereDatas[$mereKeys]['buyer_id'] = isset($productDatas[$key]['buyer_id'])?$productDatas[$key]['buyer_id']:$mereValue['buyer_id'];
            $mereDatas[$mereKeys]['buyer_name'] = isset($productDatas[$key]['buyer_name'])?$productDatas[$key]['buyer_name']:$mereValue['buyer_name'];
            $mereDatas[$mereKeys]['supplier_settlement'] = isset($productDatas[$key]['supplier_settlement'])?$productDatas[$key]['supplier_settlement']:'';
            $mereDatas[$mereKeys]['product_status'] = isset($productDatas[$key]['product_status'])?$productDatas[$key]['product_status']:'';
            $mereDatas[$mereKeys]['product_line_id'] = isset($productDatas[$key]['product_line_id'])?$productDatas[$key]['product_line_id']:'';
            $mereDatas[$mereKeys]['product_line_name'] = isset($productDatas[$key]['linelist_cn_name'])?$productDatas[$key]['linelist_cn_name']:'';
            $mereDatas[$mereKeys]['sku_state_type'] = isset($productDatas[$key]['state_type'])?$productDatas[$key]['state_type']:'';
            $mereDatas[$mereKeys]['is_purchasing'] = isset($productDatas[$key]['is_purchasing'])?$productDatas[$key]['is_purchasing']:'';
            $mereDatas[$mereKeys]['demand_data'] += $mereValue['demand_data'];
            $mereDatas[$mereKeys]['demand_datas_number'][] = $mereValue['demand_number'];
            $mereDatas[$mereKeys]['demand_name'][] = $mereValue['demand_name'];
            $mereDatas[$mereKeys]['demand_name_id'][] = $mereValue['demand_name_id']; // 需求类型ID
            $mereDatas[$mereKeys]['is_distribution'][] = $mereValue['is_distribution']; // 是否分销
            $mereDatas[$mereKeys]['peration_wms_code'] = $mereValue['gwarehouse_code'];
            $mereDatas[$mereKeys]['is_new'] = $mereValue['is_new'];

            $mereDatas[$mereKeys]['peration_wms_name'] = isset($pertain_wms_list[$mereValue['gwarehouse_code']])?$pertain_wms_list[$mereValue['gwarehouse_code']]:'';
            $mereDatas[$mereKeys]['purchase_type_id'][] = $mereValue['purchase_type_id'];
            if($mereValue['is_expedited'] == 1){

                $mereDatas[$mereKeys]['is_expedited'] =1;
            }else{
                $mereDatas[$mereKeys]['is_expedited'] =2;
            }
        }
        $results = $this->transferToStandbyOrder($mereDatas);
        return $results;
    }

    /**
     * 通过SKU 获取需求单信息
     * @params  $skus    array   SKU
     * @author:luxu
     * @time:2021年3月3号
     **/
    public function get_sku_demand($skus,$select=NULL,$where=[]){

        $query = $this->purchase_db->from("purchase_demand")->where_in("sku",$skus);
        if(!empty($where)){
            foreach($where as $where_key=>$where_value){

                if(!is_array($where_value) && !empty($where_value) && $where_value!=NULL){

                    $query->where($where_key,$where_value);
                }else{
                    $query->where_in($where_key,$where_value);
                }
            }
            //$query->where($where);
        }
        if(NULL != $select){
            $query->select($select);
        }

        $result = $query->get()->result_array();
        return $result;
    }

    /**
     * 时时判断SKU 需求单是否重复
     * @params $sku  array   SKU 信息
     *         $purchase_type_id int  业务线
     * @author:luxu
     * @time:2021年3月2号
     *
     **/

    public  function get_judge_sku_repeat($sku=NULL,$purchase_type_id=NULL,$demand_id=NULL,$update_type = NULL){

        if( NULL == $sku || $purchase_type_id == NULL){

            return NULL;
        }
        $this->purchase_db->from("purchase_demand")->where("sku",$sku)
            ->where_in("demand_status",[DEMAND_SKU_STATUS_CONFIR,SUGGEST_STATUS_NOT_FINISH])
            ->select("id,demand_number,sku,purchase_type_id");

        if(NULL != $demand_id){

            $this->purchase_db->where("id!=",$demand_id);
        }

        $demandDatas = $this->purchase_db->get()->result_array();

        // 如果SKU 没有查询到需求单记录
        if(empty($demandDatas)){

            return "no_repetition";
        }

        //print_r($demandDatas);die();

        // 判断SKU 需求单业务线类别
        $searchData = [];
        if( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH])){

            $searchData = [PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH];
        }else if(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){
            $searchData = [PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG];
        }

        $qualified = [];
        foreach($demandDatas as $key=>$value){

            if(in_array($value['purchase_type_id'],$searchData) && in_array($purchase_type_id,$searchData)){

                $qualified[] = $value;
            }
        }
        $ids = array_column($qualified,"id");
        //  业务线一直所有需求单为重复
        if( !empty($qualified)){

            if( NULL !== $update_type && $update_type == "prev"){

                $this->purchase_db->where_in("id",$ids)->where("demand_repeat",2)->update("purchase_demand",['demand_repeat'=>1]);
            }
            return "repetition";
        }

        return "no_repetition";

    }


    /**
     * 判断SKU 在需求单里面是否重复
     * 需求：30695 一键合单(1)(需求单)备货单审核页面重构
     * .SKU是否重复:枚举值=重复,未重复,当页面有数据新增时,需要对当前页面存在的所有数据,重新判断一次,SKU是否重复
    SKU是否重复的判断条件:
    (1). 当同个SKU,同时存在多个需求单,且所有需求单的业务线=国内/FBA/PFB/平台头程,所有需求单的SKU是否重复=重复
    (2). 当同个SKU,同时存在多个需求单,且所有需求单的业务线=海外/FBA大货时;所有需求单的SKU是否重复=重复
    (3). 未满足上述条件时;SKU是否重复=未重复
    (4). 上线时,原"待生成备货单页面",原备货单完结状态=未完结的,所有未完结的数据全部转移到当前需求单页面,且重新判断一次SKU是否重复
     *
     *   例子
    SKU：OX001
    业务线为国内，如果存在多个需求单时，例如存在3个需求单。并且3个需求单的业务线为 国内/FBA/PFB/平台头程 时。SKU：OX001 和存在的3个需求单为重复
    业务线为海外时。如果存在多个需求单。例如存在3个需求单，并且3个需求单业务线为 海外/FBA大货 时。SKU OX001 和存在的3个需求单为重复
    反之为未重复

     *
     *      defined('PURCHASE_TYPE_INLAND')     OR define('PURCHASE_TYPE_INLAND',1);// 国内仓
    defined('PURCHASE_TYPE_OVERSEA')    OR define('PURCHASE_TYPE_OVERSEA',2);// 海外仓
    defined('PURCHASE_TYPE_FBA')        OR define('PURCHASE_TYPE_FBA',3);// FBA
    defined('PURCHASE_TYPE_PFB')        OR define('PURCHASE_TYPE_PFB',4);// PFB
    defined('PURCHASE_TYPE_PFH')        OR define('PURCHASE_TYPE_PFH',5);// 平台头程
    defined('PURCHASE_TYPE_FBA_BIG')    OR define('PURCHASE_TYPE_FBA_BIG',6);// FBA大货
     * @params $sku  array   SKU 信息
     *         $purchase_type_id int  业务线
     * @author:luxu
     * @time:2021年3月2号
     **/

    private function judge_sku_repeat($sku=NULL,$purchase_type_id=NULL,$demand_number=NULL){

        if( NULL == $sku || $purchase_type_id == NULL){

            return NULL;
        }
        $demandDatas = $this->purchase_db->from("purchase_demand")->where("sku",$sku)
            ->where_in("demand_status",[DEMAND_SKU_STATUS_CONFIR,SUGGEST_STATUS_NOT_FINISH])
            ->select("id,demand_number,sku,purchase_type_id")->get()
            ->result_array();

        // 如果SKU 没有查询到需求单记录
        if(empty($demandDatas)){

            return "no_repetition";
        }

        //print_r($demandDatas);die();

        // 判断SKU 需求单业务线类别
        $searchData = [];
        if( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH])){

            $searchData = [PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH];
        }else if(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){
            $searchData = [PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG];
        }

        $qualified = [];
        foreach($demandDatas as $key=>$value){

            if(in_array($value['purchase_type_id'],$searchData) && in_array($purchase_type_id,$searchData)){

                $qualified[] = $value;
            }
        }
        $ids = array_column($qualified,"id");
        //  业务线一直所有需求单为重复
        if( !empty($qualified)){
            $updata =[

                'demand_repeat' =>1
            ];
            $this->purchase_db->where_in("id",$ids)->update("purchase_demand",$updata);
            return "repetition";
        }

        /*$updata =[

            'demand_repeat' =>2
        ];
        $this->purchase_db->where("demand_number",$demand_number)->update("purchase_demand",$updata);
        */
        return "no_repetition";

    }

    /**
     * 获取需求单类型数据
     * @author:luxu
     * @time:2021年2月27号
     **/
    public function push_demand_type($datas){

        if(empty($datas)){

            return False;
        }

        $updateData = $insertData = [];

        foreach($datas as $key=>$value){

            if($value['event'] == 'update'){

                $updateData = [

                    'demand_type_id' =>$value['demand_type_id'],
                    'demand_type_name' => $value['demand_type_name'],
                    'create_time' => date('Y-m-d H:i:s',time())
                ];

                $this->purchase_db->where("demand_type_id",$value['demand_type_id'])->update('demand_type',$updateData);
            }else if($value['event'] == 'insert'){

                $updateData = [

                    'demand_type_id' =>$value['demand_type_id'],
                    'demand_type_name' => $value['demand_type_name'],
                    'create_time' => date('Y-md-d H:i:s',time())
                ];
                $this->purchase_db->insert('demand_type',$updateData);
            }
        }

        return True;
    }

    /**
     * 获取需求单类型数据
     * @author:luxu
     * @time:2021年3月1号
     **/
    public function get_demand_type(){

        $result = $this->purchase_db->from("demand_type")->select("demand_type_id,demand_type_name")->get()->result_array();
        if(empty($result)){

            return [];
        }

        $result = array_column( $result,NULL,"demand_type_id");
        $resultDatas = [];
        foreach( $result as $key=>$value){

            $resultDatas[$value['demand_type_id']] = $value['demand_type_name'];
        }
        return $resultDatas;
    }

    /**
     * 修改需求单状态
     * @params $demandids    array  需求单ID
     *         $status       array  需求单状态
     * @author:luxu
     * @time:2021年3月3号
     **/
    public function updateDemandStatus($demandids,$status){

        $results = $this->purchase_db->where_in("id",$demandids)->update('purchase_demand',$status);
        return $results;
    }

    /**
     * 获取需求单枚举值对应关系
     * @author:luxu
     * @time:2021年2月27
     * DEMAND_STATUS_NOT_FINISH：为完结
    DEMAND_TO_SUGGEST：需求单已生成备货单
    DEMAND_SKU_STATUS_CONFIR： 需求状态单重新确认标识
    DEMAND_STATUS_FINISHED：已经完结
    DEMAND_STATUS_CANCEL：已经作废
     **/
    private function get_demand_enumeration($type,$valuedata){
        $enum =[

            'extra_handle,demand_lock,demand_repeat,is_new' => [1=>'是',2=>'否'],
            'is_drawback' => [1=>'是',0=>'否'], // 是否退税
            //'demand_status' => [1=>'未完结',2=>'完结'], //需求单完结状态
            'is_boutique' => [1=>'是',0=>'否'],
            'transformation' => [0=>'是',6=>'否'],
            'shipment_type' => [1=>'工厂发运',2=>'中转仓发运'],
            'is_overseas_first_order' => [0=>'否',6=>'是'],
            'is_expedited' => [1=>'否',2=>'是'],
            //完结状态 1未完结 2已生成备货单,3待重新下单,4已完结,5已作废'
            'demand_status' => [DEMAND_STATUS_NOT_FINISH=>'未完结',DEMAND_TO_SUGGEST=>'已生成备货单',DEMAND_SKU_STATUS_CONFIR=>'待重新下单',DEMAND_STATUS_FINISHED=>'已完结',DEMAND_STATUS_CANCEL=>'已作废'],
            'purchase_type_id'=>[PURCHASE_TYPE_INLAND=>'国内仓',PURCHASE_TYPE_OVERSEA=>'海外仓',PURCHASE_TYPE_FBA=>'FBA',PURCHASE_TYPE_PFB=>'PFB'
                ,PURCHASE_TYPE_PFH=>'平台头程',PURCHASE_TYPE_FBA_BIG=>'FBA大货'],
            'is_distribution' => [1=>'是',2=>'否'],
        ];

        $returndata = [];
        foreach($enum as $key=>$value){

            $keyDatas = explode(",",$key);
            if(in_array($type,$keyDatas)){

                $returndata = $value;
                break;
            }
        }
        if(!empty($returndata)){
            return isset($returndata[$valuedata])?$returndata[$valuedata]:'';
        }

        return "未知";
    }

    /**
     * 批量获取供应商信息方法
     * @author:luxu
     * @time:2021年8月16号
     **/

    private function get_supplier_message_data($supplier_codes){

       $message = $this->purchase_db->from("supplier")->where_in("supplier_code",$supplier_codes)->select("supplier_code,supplier_source")
           ->get()->result_array();

       if(empty($message)){

           return NULL;
       }

       return  array_column($message,NULL,"supplier_code");
    }

    /**
     * 批量获取SKU 商品信息
     * @author:luxu
     * @time:2021年8月16号
     **/

    private function get_product_message_data($skus){

        $message = $this->purchase_db->from("product")->where_in("sku",$skus)->select("is_purchasing,product_line_id,sku,supplier_code,supplier_name")
            ->get()->result_array();

        if(empty($message)){

            return NULL;
        }

        return array_column($message,NULL,"sku");
    }

    /**
     * 30696 一键合单(2)需求单页面新增功能"一键转为备货单","一键合单"
     * 1.一键转为备货单
    1).无勾选时,根据条件搜索结果进行操作;有勾选时,只对勾选项进行操作,需要弹出确认窗口
    2).点击"确认"后,需求单变为备货单维度进入"备货单"页面,生成新的备货单号,新的备货单号生成规则=需求单号+2位顺序码,例:RD79028600
    ,备货单业务线=需求单业务线,备货数量=需求数量;页面其他字段都按现有逻辑不变,备货单的"合单状态"变为"正常"
    3).同时"全部需求单"页面,需求单状态变为"已生成备货单",
    4).进度加入"消息-数据处理进度"页面展示,
     * @author:luxu
     * @time:2021年3月3号
     **/
    public function transferToStandbyOrder($datas){

        try{
            // 备货单合单数据未空
            if( empty($datas)){

                throw new Exception("备货单合单数据为空");
            }
            $create_list = ["1"=>[], "2" =>[]];
            $insertData = $demand_to_suggest = $updataDatas =[];
            $i=0;

            $this->load->model('product/Product_line_model');

            // 缓存所有非一级产品线对应的一级产品线
            $pro_line_cache = $this->product_line_model->cache_product_line();
            $product_line = SetAndNotEmpty($pro_line_cache, 'line') ?  $pro_line_cache['line']: [];
            $product_line_title = SetAndNotEmpty($pro_line_cache, 'master') ?  $pro_line_cache['master']: [];
            $skusDatas = array_column($datas,"sku");
            // 统一批量获取SKU信息
            $skuMessageDatas = $this->get_product_message_data($skusDatas);
            $supplierCodes = array_column($datas,"supplier_code");// 统一获取供应商信息
            $supplierMessageDatas = $this->get_supplier_message_data($supplierCodes);

            $pay_type_list_cache = [];
            
            foreach($datas as $key=>$value){
                ++$i;
                $add_data = [
                    'demand_number' => $value['demand_number'].rand(1,100), // 备货单号
                    'sku' => $value['sku'], // SKU
                    'purchase_type_id' => $value['purchase_type_id'], // 业务线
                    'product_img_url' => erp_sku_img_sku($value['product_img_url']),
                    'product_name' => $value['product_name'], // 商品名称
                    'demand_type_id' => $value['demand_name_id'], // 需求单类型
                    'product_line_id' => $value['product_line_id'], // 产品业务线
                    'product_line_name' =>$value['product_line_name'], // 产品业务线名称
                    'two_product_line_id' => $value['two_product_line_id'], // 二级产品线ID
                    'two_product_line_name' =>$value['two_product_line_name'], // 二级产品线名称
                    'supplier_code' => $value['supplier_code'], // 供应商CODE
                    'supplier_name' => $value['supplier_name'], // 供应商名称
                    'is_cross_border' => $value['is_cross_border'], //是否是跨境宝(默认0.否,1.跨境宝)
                    'purchase_amount' => $value['demand_data'], // 需求单数量
                    'purchase_unit_price' => $value['purchase_unit_price'], // 单价
                    'purchase_total_price' => $value['purchase_total_price'], // 总金额
                    'es_shipment_time'  =>$value['es_shipment_time'], // 预计发货时间
                    'buyer_id' => $value['buyer_id'], // 采购员ID
                    'buyer_name' => $value['buyer_name'], // 采购员名称
                    'account_type' =>$value['account_type'], // 结算方式
                    //'pay_type' =>$value['pay_type'], //支付方式(从供应商拉取)
                    'developer_id' => $value['developer_id'], // 开发员ID
                    'developer_name' =>$value['developer_name'], // 开发员名称
                    'sales_note' =>$value['sales_note'], // 销售备注
                    'transfer_warehouse' => $value['transfer_warehouse'], // 中转仓名称
                    'is_drawback' => $value['is_drawback'], // 是否退税
                    'purchase_name' => $value['purchase_name'], // 采购主体
                    'warehouse_code' => $value['warehouse_code'], //仓库编码
                    'left_stock' =>$value['left_stock'], // 缺货数量
                    'warehouse_name' => $value['warehouse_name'], // 仓库名称
                    'plan_product_arrive_time' => $value['plan_product_arrive_time'], //预计到货时间
                    'expiration_time' => '2099-12-31 23:59:59', //过期时间
                    'create_user_id' => $value['create_user_id'], // 添加人ID
                    'create_user_name' => $value['create_user_name'], // 添加人名称
                    'gid' =>$value['gid'], // 数据唯一标识
                    'cancel_reason_category' => $value['cancel_reason_category'], // 作废类别
                    'cancel_reason' => $value['cancel_reason'], // 作废原因
                    'is_mrp' =>$value['is_mrp'], //是否是MRP（1.是,2.否
                    'is_expedited' =>$value['is_expedited'], // 是否加急
                    'destination_warehouse' =>$value['destination_warehouse'], //小家电
                    'sales_group' =>$value['sales_group'], // 销售组别
                    'sales_name' => $value['sales_name'], // 销售名称
                    'sales_account' => $value['sales_account'], // 销售账号
                    'site' =>$value['site'], // 站点
                    'logistics_type' => $value['logistics_type'], // 物流类型
                    'platform' =>$value['platform'], // 平台
                    'sales_note2' => $value['sales_note2'],
                    'country' => $value['country'], //国家
                    'is_boutique' =>$value['is_boutique'], // 是否精品
                    'shipment_type' => $value['shipment_type'], // 发运类型
                    'is_overseas_first_order' => $value['is_overseas_first_order'],// 海外仓首单
                    'fba_purchase_qty' => $value['fba_purchase_qty'], // FBA 备货数量
                    'inland_purchase_qty' =>$value['inland_purchase_qty'], // 国内仓备货数量
                    'pfh_purchase_qty' => $value['pfh_purchase_qty'], // 平台头程备货数量
                    'extra_handle' => $value['extra_handle'], //额外处理：0默认无，1需要熏蒸
                    'source' => $value['source'], // 采购类型：1合同，2网采
                    'suggest_status' =>1,
                    //'demand_type_id' => $value['demand_name_id'], // 需求单类型ID
                    'audit_status' =>1,
                    'create_time' => date("Y-m-d H:i:s"),
                    'source_from' =>$value['source_from'],
                    'temp_container' =>$value['temp_container'],
                    'is_new' => $value['is_new'],
                    'is_distribution' => $value['is_distribution']
                ];

                $productMessage = isset($skuMessageDatas[$value['sku']])?$skuMessageDatas[$value['sku']]:[];
                $add_data['is_purchasing'] = isset($productMessage['is_purchasing'])?$productMessage['is_purchasing']:0;

                // 获取一级产品线
                $p_line_id = $value['two_product_line_id'];
                $p_line_one = isset($product_line[$p_line_id]) ? $product_line[$p_line_id] : 0;
                if(empty($p_line_one) or $p_line_one == 0){
                    $p_line_id = !empty($productMessage['product_line_id'])?$productMessage['product_line_id']:0;
                    $product_line_data = $this->product_line_model->get_product_top_line_data($p_line_id);
                    $p_line_one = isset($product_line_data['product_line_id'])?$product_line_data['product_line_id']:0;
                }

                $add_data['product_line_id'] = $p_line_one;
                $add_data['product_line_name'] = isset($product_line_title[$p_line_one]) ? $product_line_title[$p_line_one] : '';

                // Start缓存 供应商结算方式，避免重复查询
                $pay_type_list_cache_key = $value['supplier_code'].'-'.$value['is_drawback'].'-'.$value['purchase_type_id'];
                if(isset($pay_type_list_cache[$pay_type_list_cache_key])){
                    $supplier_payment_info = $pay_type_list_cache[$pay_type_list_cache_key];
                }else{
                    $supplier_payment_info = $this->Supplier_payment_info_model->check_payment_info($value['supplier_code'], $value['is_drawback'], $value['purchase_type_id']);
                    $pay_type_list_cache[$pay_type_list_cache_key] = $supplier_payment_info;
                }
                // End缓存 供应商结算方式，避免重复查询

                $pay_type = isset($supplier_payment_info['payment_method']) ? $supplier_payment_info['payment_method'] :0;


                $add_data['pay_type'] = $pay_type;

                $add_data['supplier_source'] = isset($supplierMessageDatas[$value['supplier_code']])?$supplierMessageDatas[$value['supplier_code']]['supplier_source']:'';
                // 如果不是合单，需求单转为备货单

                if( !isset($value['is_merege'])){// 如果不是合单
                    $add_data['demand_type_id'] = is_array($value['demand_name_id'])?implode(",",$value['demand_name_id']):$value['demand_name_id'];
                }else{
                    $add_data['demand_type_id'] = 1;// 字段默认值
                }

                if(empty($add_data['supplier_code']) || $add_data['supplier_code'] == '') {
                    $add_data['supplier_code'] = isset($productMessage['supplier_code'])?$productMessage['supplier_code']:'';
                    $add_data['supplier_name'] = isset($productMessage['supplier_name'])?$productMessage['supplier_name']:'';
                }



                // 如果是合单
                if( isset($value['is_merege']) && $value['is_merege'] == true){
                    /**
                    是否分销字段来源计划系统推送需求时一起推送,业务线=国内,FBA,平台头程,PFB时,必填,
                     * 当多个需求单合并为一个备货单时,只要有一个需求单的是否分销=是,则合并后备货单的是否分销=是
                     **/
                    if(in_array(1,$value['is_distribution'])){

                        $add_data['is_distribution'] = 1;
                    }else{
                        $add_data['is_distribution'] = 2;
                    }
                    if( count($value['demand_name_id']) == 1) {
                        $add_data['demand_type_id'] = is_array($value['demand_name_id']) ? implode(",", $value['demand_name_id']) : $value['demand_name_id'];
                    }else{

                        // 开始统计需求单类型ID 出现的次数
                        $countDemandTypeData = array_count_values($value['demand_name_id']);
                        arsort( $countDemandTypeData);
                        $add_data['demand_type_id'] = key($countDemandTypeData);
                    }

                    //如果是合单，业务线包含了PFB。合单后业务线就是 PFB
                    if(count($value['purchase_type_id'])>1 && in_array(4,$value['purchase_type_id'])){

                        $add_data['purchase_type_id'] = 4;
                    }else{

                        $value['purchase_type_id'] = array_unique($value['purchase_type_id']);
                        if(count($value['purchase_type_id'])>1){
                            $add_data['purchase_type_id'] = 4;
                        }else {
                            $add_data['purchase_type_id'] = $value['purchase_type_id'][0];
                        }
                    }
                    // 如果是合单仓库获取公共仓库
                    $add_data['warehouse_name'] = $value['peration_wms_name'];
                    $add_data['warehouse_code'] = $value['peration_wms_code'];
                    $add_data['is_merge']       = 1;
                }else{
                    // 如果不是合单，是需求单转备货单
                    $add_data['is_erp']             = $value['is_erp'];
                    $add_data['demand_number']      = $value['demand_number'];
                    $add_data['is_merge']           = 0;// 字段默认值
                }
                // 如果业务线是海外，或者FBA大货。备货单就等于需求单
                if( in_array($value['purchase_type_id'],[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){

                    $add_data['demand_number'] = $value['demand_number'];
                }else if( isset($value['is_merege']) && $value['is_merege'] == true
                    && !in_array($value['purchase_type_id'],[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])) {

                    // 获取备货单最大的ID号
                    $suggestIds = $this->purchase_db->from("purchase_suggest")->select("id")->order_by("id DESC")->limit(1)->get()->row_array();
                    $add_data['demand_number'] = "HD".$suggestIds['id'].rand(1,1000).$i;
                }

                if(isset($value['is_merege']) && True == $value['is_merege']){

                    $add_data['purchase_total_price'] = $value['demand_data'] * $value['purchase_unit_price'];
                    $demand_to_suggest_merge = isset($value['demand_datas_number'])?$value['demand_datas_number']:[];
                    if(!empty($demand_to_suggest_merge)){
                        foreach($demand_to_suggest_merge as $merge_demands){
                            $demand_to_suggest[] = [
                                'demand_number' => $merge_demands, // 需求单号
                                //'demand_id' => $value['id'], // 需求单ID
                                'suggest_demand' => $add_data['demand_number'], // 需求单号
                                'is_merge_push_plan' => 1, //推送计划系统标识
                                'demand_status' => DEMAND_TO_SUGGEST // 已经生成备货单
                            ];
                        }
                    }
                }else{
                    // 获取备货单号和需求单号的对应关系
                    $demand_to_suggest[] = [

                        'demand_number' => $value['demand_number'], // 需求单号
                        //'demand_id' => $value['id'], // 需求单ID
                        'suggest_demand' => $add_data['demand_number'], // 需求单号
                        'demand_status' => DEMAND_TO_SUGGEST
                    ];
                }

                //一键转换为备货单 PURCHASE_TYPE_OVERSEA
                if( !isset($value['is_merege']) && $add_data['purchase_type_id'] == PURCHASE_TYPE_OVERSEA && $add_data['demand_type_id'] == DEMAND_SELLING_PRODUCTS_ID){
                    $add_data['is_overseas_boutique'] = 1; // 海外线精品=是
                }else{
                    $add_data['is_overseas_boutique'] = 0; // 海外线精品=否
                }

                if(isset($value['demand_name'])){
                    $add_data['demand_name'] = is_array($value['demand_name'])?implode(",",array_unique($value['demand_name'])):$value['demand_name'];
                }else{
                    $add_data['demand_name'] = '';
                }

                if(isset($value['source']) && in_array($value['source'], [1, 2])){
                    $create_list[$value['source']][] = $add_data['demand_number'];
                }

                // 如果需求单状态是需求状态单重新确认标识 DEMAND_SKU_STATUS_CONFIR,并且是业务线为海外，或者海外大货

                if( $value['demand_status'] == DEMAND_SKU_STATUS_CONFIR ){

                    $updataDatas[] = $add_data;
                }else {
                    $insertData[] = $add_data;
                }
            }

            $result = $suggestResult = True;
            try {
                $this->purchase_db->trans_begin();
                if (!empty($insertData)) {
                    //print_r($insertData);die();
                    //foreach($insertData as $insert_value=>$insertDatas){
                    //    $result = $this->purchase_db->insert('purchase_suggest', $insertDatas);
                    //}
                    $result = $this->purchase_db->insert_batch('purchase_suggest', $insertData);
                }
                if (!empty($updataDatas)) {

                    $suggestDemandNumbers = array_column($updataDatas,"demand_number");

                    $updataDemandNumbers =    $this->purchase_db->from("purchase_suggest")
                        ->where_in("demand_number",$suggestDemandNumbers)->select("id,demand_number")->get()->result_array();

                    $updateFlag = [];
                    if(!empty($updataDemandNumbers)){
                        $updateFlag = array_column( $updataDemandNumbers,"demand_number");
                    }

                    foreach($updataDatas as $updateDatas_key=>$updataData_value){
                        if(!in_array($updataData_value['demand_number'],$updateFlag)){
                            $result = $this->purchase_db->insert('purchase_suggest', $updataData_value);
                        }else{

                            $result = $this->purchase_db->update('purchase_suggest',$updataData_value,['demand_number'=>$updataData_value['demand_number']]);

                        }
                    }
                }
                if(!empty($demand_to_suggest)){
                    //print_r($demand_to_suggest);die();
                    // $demand_to_suggest = array_unique($demand_to_suggest);
                    $suggestResult = $this->purchase_db->update_batch('purchase_demand',$demand_to_suggest,'demand_number');
                }
                // 投递生成采购单
                $autoCreateOrder = $this->purchase_db->from("purchase_demand_config")->where(["status" => 1, "module_flag" => "purchase"])->get()->result_array();
                $autoCreate = $autoCreateOrder && count($autoCreateOrder) > 0? true : false;
                $this->load->model('sync_supplier_model');
                foreach ($create_list as $key=>$val){
                    if($autoCreate && !empty($val) && isset($val['purchase_type_id']) && !in_array($val['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) {
//                        $this->create_order_by_suggest($val, $key);
                        $this->sync_supplier_model->set_create_suggest($val, $key);
                    }
                }
                if($result || $suggestResult){
                    if ($this->purchase_db->trans_status() === false) {
                        $this->purchase_db->trans_rollback();
                        throw new Exception("需求单转为备货单失败");
                    } else {
                        $this->purchase_db->trans_commit();
                        $demandNumbers = array_column($insertData,"demand_number");
                        $mq = new Rabbitmq();
                        //设置参数
                        $mq->setQueueName('SUGGEST_LOCK');
                        $mq->setExchangeName('SUGGEST');
                        $mq->setRouteKey('SUGGEST_LOCK_KEY');
                        $mq->setType(AMQP_EX_TYPE_DIRECT);
                        //构造存入数据 +
                        $push_data = [
                            'data' => $demandNumbers
                        ];

                        //存入消息队列
                        $mqresult = $mq->sendMessage($push_data);
                        return true;
                    }
                }
            }catch ( Exception $exp ){
                $this->purchase_db->trans_rollback();
                throw new Exception("需求单转为备货单失败");
            }
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 根据合单后的备货单，自动生成采购单
     */
    public function create_order_by_suggest($suggest=[], $type=0)
    {
        if(empty($suggest) && $type == 0)return true;
        try{
            $this->load->model('ali/Ali_order_advanced_new_model', 'advanced_new_model');
            $data = $this->purchase_db->from('purchase_suggest')
                ->select('id')
                ->where_in('demand_number', $suggest)
                ->where(['is_create_order' => 0])
                ->get()
                ->result_array();
            $ids = $data && !empty($data)? array_column($data, 'id'):[];
            if(!empty($ids)){
                $action_handle = $type == 1?"一键生成采购单":"1688一键下单";
                $action =  $type == 1?3:1; // 3.一键生成采购单,1.1688一键下单
                $date = date("Y-m-d H:i:s");
                $uid = 0;
                $user = '合单后系统自动生成！';
                $handle_query = [
                    "list"  => $ids,
                    "uid"   => $uid,
                    "user"  => $user,
                    "action"=> $action,
                ];
                $create_tsak = [
                    "user_id"       => $uid,
                    "user_name"     => $user,
                    "handle_status" => 0,
                    "handle_action" => $action_handle,
                    "handle_msg"    => "等待处理",
                    "handle_all"    => count($ids),
                    "handle_query"  => json_encode($handle_query),
                    "success_num"   => 0,
                    "error_num"     => 0,
                    "create_at"     => $date,
                ];
                $id = $this->advanced_new_model->callback_create_error($create_tsak, [], "create");

                $log = [
                    "action_name"   => "advanced_one_key_create_order",
                    "create_at"     => $this->get_microtime(),
                ];

                $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
                if ($client->connect(SWOOLE_SERVER, 9509, 0.5)) {
                    $data = [
                        "id"    => $id,
                        "list"  => $ids,
                        "uid"   => $uid,
                        "user"  => $user,
                        "action"=> $action,
                    ];
                    $data = json_encode($data);
                    $client->send($data);
                    $log['request_data'] = $data;
                    $client->recv();
                    $client->close();
                }
            }
        }catch (\Throwable $e){}
        return true;
    }

    /**
     * 获取需求单信息接口 需求：30695 一键合单(1)(需求单)备货单审核页面重构
     * @param array  $clientDatas  查询条件
     * @param string $action 操作类型(select.查询数据并转换中文，sum.计算总数，system_merge.系统合单获取数据不转换中文)
     * @author:luxu
     * @time:2021年2月27号
     **/
    public function get_demand_datas($clientDatas = array(),$action = 'select'){

        $querys = $this->purchase_db->from("purchase_demand as demand")->join('product as pd','demand.sku=pd.sku','left');

        // 是否传入SKU 查询，数组
        if(isset($clientDatas['sku']) && !empty($clientDatas['sku']) ){

            $querys->where_in('demand.sku',$clientDatas['sku']);
        }

        // 判断SKU 货源状态 是否为正常

        if( isset($clientDatas['is_mereges']) && $clientDatas['is_mereges'] == true){

            $querys->where_in("pd.supply_status",1)->where("demand.is_lock",0);
        }

        // 需求单ID

        if( isset($clientDatas['ids']) && !empty($clientDatas['ids'])){

            $querys->where_in("demand.id",$clientDatas['ids']);
        }

        if( isset($clientDatas['supply_status']) && !empty($clientDatas['supply_status'])){

            $querys->where_in("pd.supply_status",$clientDatas['supply_status']);
        }

        // temp_container 虚拟柜号

        if( isset($clientDatas['temp_container']) && !empty($clientDatas['temp_container'])){

            $clientDatas['temp_container'] = explode(" ",$clientDatas['temp_container']);
            $querys->where_in("demand.temp_container",$clientDatas['temp_container']);
        }

        // 是否传入需求单号, 数组
        if( isset($clientDatas['demand_number']) && !empty($clientDatas['demand_number'])){

            $querys->where_in('demand.demand_number',$clientDatas['demand_number']);
        }

        // 是否退税
        if( isset($clientDatas['is_drawback']) && !empty($clientDatas['is_drawback'])){

            if($clientDatas['is_drawback'] == 2){

                $querys->where('demand.is_drawback',0);
            }else {
                $querys->where('demand.is_drawback', $clientDatas['is_drawback']);
            }
        }

        if( isset($clientDatas['is_distribution']) && !empty($clientDatas['is_distribution'])){

            $querys->where("demand.is_distribution",$clientDatas['is_distribution']);
        }

        // 产品线

        if( isset($clientDatas['product_line']) && !empty($clientDatas['product_line'])){

            $querys->where_in("demand.product_line_id",$clientDatas['product_line']);
        }

        // 创建时间

        if( isset($clientDatas['create_time_start']) && !empty($clientDatas['create_time_start'])){

            $querys->where('demand.create_time>=',$clientDatas['create_time_start']);
        }

        if( isset($clientDatas['create_time_end']) && !empty($clientDatas['create_time_end'])){

            $querys->where('demand.create_time<=',$clientDatas['create_time_end']);
        }

        // 是否新品

        if( isset($clientDatas['is_new']) && !empty($clientDatas['is_new'])){

            if($clientDatas['is_new'] == 2){
                $querys->where('pd.is_new', 0);
            }else {
                $querys->where('pd.is_new', $clientDatas['is_new']);
            }
        }

        // 需求单业务线

        if( isset($clientDatas['purchase_type_id']) && !empty($clientDatas['purchase_type_id'])){

            $querys->where_in('demand.purchase_type_id',$clientDatas['purchase_type_id']);
        }

        // 申请人

        if( isset($clientDatas['applicant']) && !empty($clientDatas['applicant'])){

            $querys->where_in('demand.create_user_id',$clientDatas['applicant']);
        }
        // 是否加急

        if( isset($clientDatas['is_expedited']) && !empty($clientDatas['is_expedited'])){

            $querys->where('demand.is_expedited',$clientDatas['is_expedited']);
        }

        // 预计断货时间

        if( isset($clientDatas['earliest_exhaust_date_start']) && !empty($clientDatas['earliest_exhaust_date_start'])){

            $querys->where('demand.earliest_exhaust_date>=',$clientDatas['earliest_exhaust_date_start']);
        }
        if( isset($clientDatas['earliest_exhaust_date_end']) && !empty($clientDatas['earliest_exhaust_date_end'])){

            $querys->where('demand.earliest_exhaust_date<=',$clientDatas['earliest_exhaust_date_end']);
        }
        // 物流类型
        if( isset($clientDatas['logistics_type']) && !empty($clientDatas['logistics_type'])){

            $querys->where_in('demand.logistics_type',$clientDatas['logistics_type']);
        }
        // 目的仓
        if( isset($clientDatas['destination_warehouse']) && !empty($clientDatas['destination_warehouse'])){

            $querys->where_in('demand.destination_warehouse',$clientDatas['destination_warehouse']);
        }

        // 产品状态
        if( isset($clientDatas['product_status']) && !empty($clientDatas['product_status'])){

            $querys->where_in('pd.product_status',$clientDatas['product_status']);
        }
        // 采购仓库
        if( isset($clientDatas['warehouse_code']) &&  !empty($clientDatas['warehouse_code'])){

            $querys->where_in('demand.warehouse_code',$clientDatas['warehouse_code']);
        }

        // 采购组别

        if(isset($clientDatas['group_ids']) && !empty($clientDatas['group_ids'])){

            $querys->where_in('demand.buyer_id',$clientDatas['groupdatas']);
        }

        // 是否精品
        if( isset($clientDatas['is_boutique']) && !empty($clientDatas['is_boutique'])){
            if( $clientDatas['is_boutique'] == 2){

                $querys->where('demand.is_boutique',1);
            }else {
                $querys->where('demand.is_boutique',0);
            }
        }

        if( isset($clientDatas['is_abnormal_lock'])){
            $querys->where('demand.is_abnormal_lock',0);
        }
        // 公共仓库

        if( isset($clientDatas['gwarehouse_code']) && !empty($clientDatas['gwarehouse_code'])){

            if(is_array($clientDatas['gwarehouse_code'])){
                $pertain_wms_list = implode("','",$clientDatas['gwarehouse_code']);
            }else{
                $pertain_wms_list = implode("','",explode(',',$clientDatas['gwarehouse_code']));
            }
            $querys->where("demand.warehouse_code IN(SELECT warehouse_code FROM pur_warehouse WHERE pertain_wms IN('{$pertain_wms_list}'))");
        }

        // 开发类型

        if( isset($clientDatas['development_type_ch']) && !empty($clientDatas['development_type_ch'])){

            if(is_array($clientDatas['development_type_ch'])){
                $querys->where_in('pd.state_type',$clientDatas['development_type_ch']);
            }else{
                $querys->where('pd.state_type',$clientDatas['development_type_ch']);
            }
        }

        // 需求单完结状态

        if( isset($clientDatas['demand_status']) && !empty($clientDatas['demand_status'])){

            $querys->where_in('demand.demand_status',$clientDatas['demand_status']);
        }

        // 需求单完结状态兼容前端同学传值

        if( isset($clientDatas['demand_status_ch']) && !empty($clientDatas['demand_status_ch'])){

            $querys->where_in('demand.demand_status',$clientDatas['demand_status_ch']);
        }

        // 发运类型
        if( isset($clientDatas['shipment_type']) && !empty($clientDatas['shipment_type'])){

            $querys->where('demand.shipment_type',$clientDatas['shipment_type']);
        }
        // 过滤手工单
        if( isset($clientDatas['erp_id']) && $clientDatas['erp_id'] == false){
            $querys->where('demand.erp_id',0);
        }

        // 是否海外仓首单

        if( isset($clientDatas['is_overseas_first_order_ch']) && !empty($clientDatas['is_overseas_first_order_ch'])){

            if( $clientDatas['is_overseas_first_order_ch'] == 2){

                $querys->where("demand.is_overseas_first_order",0);
            }else{
                $querys->where("demand.is_overseas_first_order",$clientDatas['is_overseas_first_order_ch']);
            }

        }

        // 作废原因

        if (isset($clientDatas['tovoid_reason']) and !empty($clientDatas['tovoid_reason'])){

            $querys->where_in('demand.cancel_reason_category',$clientDatas['tovoid_reason']);
        }

        // 物流类型

        if( isset($clientDatas['logit_type']) && !empty($clientDatas['logit_type'])){

            $querys->where_in('demand.logistics_type',$clientDatas['logit_type']);
        }
        // 是否国内仓转海外
        if( isset($clientDatas['transformation']) && !empty($clientDatas['transformation'])){

            $querys->where('demand.transformation',$clientDatas['transformation']);
        }
        // 预计供货时间

        if( isset($clientDatas['estimate_time_start']) && !empty($clientDatas['estimate_time_start'])){

            $scree_query = "( SELECT sku,MAX(estimate_time) as estimate_time FROM pur_product_scree AS screet WHERE screet.status=50 AND apply_remark IN(4,10) GROUP BY sku ORDER BY estimate_time DESC ) as screed";
            $querys->join($scree_query, 'screed.sku=demand.sku', 'left');
            $querys->where("screed.estimate_time>=",$clientDatas['estimate_time_start'])->where("screed.estimate_time<=",$clientDatas['estimate_time_end']);

            //$querys->where("demand.estimate_time>=",$clientDatas['estimate_time_start'])->where("demand.estimate_time<=",$clientDatas['estimate_time_start']);
        }

        // 目的仓

        if( isset($clientDatas['m_warehouse_code']) && !empty($clientDatas['m_warehouse_code'])) {

            $querys->where_in('demand.destination_warehouse',$clientDatas['m_warehouse_code']);
        }

        // 产品状态
        if( isset($clientDatas['product_status']) && !empty($clientDatas['product_status'])){

            $querys->where_in('pd.product_status',$clientDatas['product_status']);
        }

        // 需求类型
//        print_r($clientDatas['demand_type']);die();
        if( isset($clientDatas['demand_type']) && !empty($clientDatas['demand_type'])){

            $querys->where_in('demand.demand_name_id',$clientDatas['demand_type']);
        }
        // 解锁时间
        if( isset($clientDatas['over_lock_time_start']) && !empty($clientDatas['over_lock_time_start'])){

            $querys->where('demand.over_lock_time>=',$clientDatas['over_lock_time_start']);
        }

        if( isset($clientDatas['over_lock_time_end']) && !empty($clientDatas['over_lock_time_end'])){

            $querys->where('demand.over_lock_time<=',$clientDatas['over_lock_time_end']);
        }

        // 需求锁定
        if( isset($clientDatas['demand_lock']) && !empty($clientDatas['demand_lock'])){

            $querys->where('demand.demand_lock',$clientDatas['demand_lock']);
        }

        // SKU 是否重复
        if( isset($clientDatas['demand_repeat']) && !empty($clientDatas['demand_repeat'])){
            $querys->where('demand.demand_repeat',$clientDatas['demand_repeat']);
        }

        // 查询供应商
        if( isset($clientDatas['supplier_code']) && !empty($clientDatas['supplier_code'])){
            $querys->where('demand.supplier_code',$clientDatas['supplier_code']);
        }

        // 查询采购员
        if(!empty($clientDatas['buyer_id']) && is_array($clientDatas['buyer_id'])){
            $querys->where_in('demand.buyer_id',$clientDatas['buyer_id']);
        }

        // 需求单列表只展示，重新确认，未完结
        $querys->where_in("demand.demand_status",[DEMAND_SKU_STATUS_CONFIR,SUGGEST_STATUS_NOT_FINISH]);

        if( isset($clientDatas['backstage']) && $clientDatas['backstage'] == 1){
            $querys->group_by("demand.sku");
        }

        $countquery = clone $querys;
        if(isset($clientDatas['limit']) && isset($clientDatas['offset'])){
            //print_r($clientDatas);die();
            $querys->limit($clientDatas['limit'],$clientDatas['offset']);
        }

        $result = $querys->select('demand.*,pd.product_thumb_url  AS product_img_url,
        pd.product_status,pd.supply_status,pd.starting_qty,pd.state_type,pd.product_name,pd.sku_change_data')->order_by("demand.id DESC")->get()->result_array();

        //echo $this->purchase_db->last_query();die();
        $total = $countquery->select('demand.id')->count_all_results(); // 总条数

        if($action == 'sum'){// 计算总数时立即返回结果
            return $total;
        }

        if($action == 'select' and !empty($result)){
            $this->load->model('warehouse/Warehouse_model');
            //$warehouse_list = $this->Warehouse_model->warehouse_code_to_name();
            $warehouse_list_tmp = $this->Warehouse_model->get_warehouse_list();
            $warehouse_list = array_column($warehouse_list_tmp,'warehouse_name','warehouse_code');
            $pertain_wms_list = array_column($warehouse_list_tmp,'pertain_wms','warehouse_code');

            $this->load->model('warehouse/Logistics_type_model');
            $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
            $logistics_type_list = array_column($logistics_type_list,'type_name','type_code');
            $page_purchase_amount = $page_sku = 0;

            $page_sku = count(array_unique(array_column($result,'sku'))); // 统计当前页面有多少个SKU
            $page_purchase_amount = array_sum(array_column($result,'demand_data')); // 统计PCS 数据

            $this->load->model('system/Reason_config_model');
            $reasonIds = array_unique(array_column($result,'cancel_reason_category'));
            $reasonList = $this->Reason_config_model->get_reason_datas($reasonIds);
            $reasonList = array_column($reasonList,NULL,"id");
            foreach($result as $key=>&$value){
                //demand_name_ch
                $value['is_boutique_ch'] = $this->get_demand_enumeration('is_boutique',$value['is_boutique']);
                $value['demand_repeat_ch'] = $this->get_demand_enumeration('demand_repeat',$value['demand_repeat']);
                //demand_name_id
                $value['demand_name_ch'] = $value['demand_name'];
                $value['is_drawback_ch'] = $this->get_demand_enumeration('is_drawback',$value['is_drawback']);
                $value['is_overseas_first_order_ch'] = $this->get_demand_enumeration('is_overseas_first_order',$value['is_overseas_first_order']);
                $value['transformation_ch'] = $this->get_demand_enumeration('transformation',$value['transformation']);
                $value['extra_handle_ch'] = $this->get_demand_enumeration('extra_handle',$value['extra_handle']);
                $value['demand_repeat_ch'] = $this->get_demand_enumeration('demand_repeat',$value['demand_repeat']);
                $value['is_new_ch'] = $this->get_demand_enumeration('is_new',$value['is_new']);
                $value['destination_warehouse_ch'] = (isset($warehouse_list[$value['destination_warehouse']]))?$warehouse_list[$value['destination_warehouse']]:'-';
                $value['product_status_ch'] =  getProductStatus($value['product_status']);
                $value['is_expedited_ch'] = $this->get_demand_enumeration('is_expedited',$value['is_expedited']);
                $value['product_line_ch'] = $value['product_line_name'];
                $value['is_distribution_ch'] = $this->get_demand_enumeration('is_distribution',$value['is_distribution']);
                $value['gwarehouse_code'] = isset($pertain_wms_list[$value['warehouse_code']])?$pertain_wms_list[$value['warehouse_code']]:'';
                $catereason = isset($reasonList[$value['cancel_reason_category']])?$reasonList[$value['cancel_reason_category']]['reason_name']:'';
                $value['tovoid_reason'] = $catereason.$value['cancel_reason'];
                $value['demand_lock_ch'] = $this->get_demand_enumeration('demand_lock',$value['demand_lock']);
                $value['shipment_type_ch'] = $this->get_demand_enumeration('shipment_type',$value['shipment_type']);
                $value['logistics_type_ch'] = isset($logistics_type_list[$value['logistics_type']])?$logistics_type_list[$value['logistics_type']]:'-';
                $value['development_type_ch'] = getProductStateType($value['state_type']);
                $value['demand_status_ch'] = $this->get_demand_enumeration('demand_status',$value['demand_status']);
                $value['purchase_type_ch'] = $this->get_demand_enumeration('purchase_type_id',$value['purchase_type_id']);
                //get_scree_estimate_time
                $value['estime_time'] = $this->get_scree_estimate_time($value['sku']);

                $value['supply_status_ch'] = '';
                //货源状态(1.正常,2.停产,3.断货,10:停产找货中)
                if($value['supply_status'] == 1){

                    $value['supply_status_ch'] = "正常";
                }

                if($value['supply_status'] == 2){

                    $value['supply_status_ch'] = "停产";
                }
                if($value['supply_status'] == 3){

                    $value['supply_status_ch'] = "断货";
                }
                if($value['supply_status'] == 10){

                    $value['supply_status_ch'] = "停产找货中";
                }


                // 判断需求单是否重复
                /*$repeat = $this->get_judge_sku_repeat($value['sku'],$value['purchase_type_id'],$value['id']);
                if($repeat == "repetition"){

                    $value['demand_repeat_ch']= "是";
                    $value['demand_repeat'] = DEMAND_SKU_REPEAT;
                }else{
                    $value['demand_repeat_ch'] = "否";
                    $value['demand_repeat'] = DEMAND_SKU_NO_REPEAT;
                }*/

                $value['product_img_url'] = erp_sku_img_sku($value['product_img_url']);
            }
        }

        return [

            'values' => $result,
            'page' =>[

                'total' => $total,
                'limit' => isset($clientDatas['limit'])?$clientDatas['limit']:0, // 每一页多少条数据
                'page'  => isset($clientDatas['nowoffset'])?$clientDatas['nowoffset']:'1' // 第几页
            ],
            'aggregate_data' =>[

                'page_sku' => isset($page_sku)?$page_sku:0,
                'page_purchase_amount'=>isset($page_purchase_amount)?$page_purchase_amount:0
            ]

        ];
    }


    /**
     * 接受计划系统推送的需求单信息
     * @params: demandDatas array 需求单信息
     * @author:luxu
     * @time:2021年2月27号
     **/
    public function receive_demand_data_from_plan($demandDatas=array()){

        try{

            if(empty($demandDatas)){

                throw new Exception("计划系统传入数据为空");
            }
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 获取指定的 一个采购需求
     * @author Jolo
     * @param int    $suggest_id    采购建议ID
     * @param string $demand_number 备货单号
     * @return array|bool
     */
    public function get_one($suggest_id = 0,$demand_number = ''){
        if(empty($suggest_id) and empty($demand_number)) return false;
        $query_builder      = $this->purchase_db;
        if($suggest_id){
            if(is_array($suggest_id)){
                $query_builder->where_in('id',$suggest_id);
                $results            = $query_builder->get($this->table_name)->result_array();
            }else{
                $query_builder->where('id',$suggest_id);
                $results            = $query_builder->get($this->table_name)->row_array();
            }
        }else{
            if(is_array($demand_number)){
                $query_builder->where_in('demand_number',$demand_number);
                $results            = $query_builder->get($this->table_name)->result_array();
            }else{
                $query_builder->where('demand_number',$demand_number);
                $results            = $query_builder->get($this->table_name)->row_array();
            }
        }
        return $results;
    }

    /**
     * 获取采购需求导出
     * @author Jolon
     * @param      $params
     * @param int  $offset
     * @param int  $limit
     * @param int  $page
     * @return array
     */
    public function get_list_export($params,$offset = 0,$limit = 0,$page = 1,$export = false){
        if(!is_null($limit))
            $limit = query_limit_range($limit,false);
        $userid=jurisdiction(); //当前登录用户ID
        $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
        $role=get_user_role();//当前登录角色
        $data_role = getRole();//数据权限配置
        $res_arr = array_intersect($role, $data_role);
        $params                 = $this->table_query_filter($params);// 过滤为空的元素
        $query_builder          = $this->purchase_db;
        $query_builder->from('purchase_suggest as ps');
        $query_builder = $query_builder->join('product as pd','pd.sku=ps.sku','left');

        if( !(!empty($res_arr) OR $userid === true )){
            $query_builder->where_in('ps.buyer_id',$userid);
        }
        if(isset($params['user_groups_types'])){
            $user_groups_types = $params['user_groups_types'];
        }
        if(is_array($user_groups_types)){
            $query_builder->where_in('ps.purchase_type_id', $user_groups_types);
        }


        /*if(isset($params['sku']) && trim($params['sku'])){
            $sku_arr = array_filter(explode(' ', trim($params['sku'])));
            $query_builder->where_in('ps.sku',$sku_arr);
            unset($params['sku']);
        }*/

        if (!empty($params['sku'])) {
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索
                $this->purchase_db->like('ps.sku', $params['sku'], 'both');
            } else {
                $this->purchase_db->where_in('ps.sku', $sku);
            }
        }

        if(isset($params['purchase_order_status'])){
            $query_builder->join('purchase_suggest_map as psm', 'psm.demand_number=ps.demand_number', 'left');
            $query_builder->join('purchase_order as po', 'po.purchase_number=psm.purchase_number', 'left');
            if(gettype($params['purchase_order_status']) == "array"){
                $query_builder->where_in('po.purchase_order_status',$params['purchase_order_status']);
            }else{
                $query_builder->where('po.purchase_order_status',$params['purchase_order_status']);
            }
            unset($params['purchase_order_status']);
        }

        if(isset($params['is_ticketed_point']) && !empty($params['is_ticketed_point']))
        {
            // 票点为空
            if( $params['is_ticketed_point'] == 1)
            {
                $query_builder->where("pd.ticketed_point",'0.000');
            }else{

                $query_builder->where("pd.ticketed_point>0.000");
            }
        }

        if( isset( $params['is_scree']) ) {

            $query_builder->join("product_scree AS scree","ps.sku=scree.sku","LEFT");
            if( $params['is_scree'] == 1) {
                $query_builder->where_in("scree.status",[PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM,PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM]);
            }

            if( $params['is_scree'] ==2 ) {
                $query_builder->where_not_in("(scree.status",[PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM,PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM]);
                $query_builder->or_where_not_in("ps.sku","SELECT sku FROM pur_product_scree GROUP BY sku")->where("1=1 )");
            }
        }
        if(isset($params['plan_product_arrive_time_start']) and $params['plan_product_arrive_time_start']){
            $params['plan_product_arrive_time_start'] and $query_builder->where('ps.earliest_exhaust_date >=',$params['plan_product_arrive_time_start']);
            unset($params['plan_product_arrive_time_start']);
        }
        if(isset($params['plan_product_arrive_time_end']) and $params['plan_product_arrive_time_end']){
            $params['plan_product_arrive_time_end'] and $query_builder->where('ps.earliest_exhaust_date<',$params['plan_product_arrive_time_end']);
            unset($params['plan_product_arrive_time_end']);
        }

        if(isset($params['id']) && $params['id']!=""){
            if( is_string($params['id'])) {
                $ids = explode(',', $params['id']);
            }
            $query_builder->where_in('ps.id',$ids);
            unset($params['id']);
        }
        if(isset($params['is_create_order']) && is_numeric($params['is_create_order'])){
            $query_builder->where('ps.is_create_order',(int)$params['is_create_order']);
            unset($params['is_create_order']);
        }
        if(isset($params['is_left_stock']) && is_numeric($params['is_left_stock'])){
            if(intval($params['is_left_stock']) == 1){
                $query_builder->where('ps.left_stock <',0);
            }else{
                $query_builder->where('ps.left_stock >=',0);
            }
            unset($params['is_left_stock']);
        }

        if (isset($params['demand_type_id']) and $params['demand_type_id']){
            $query_builder->where_in('ps.demand_type_id',$params['demand_type_id']);
            unset($params['demand_type_id']);
        }

        if (isset($params['buyer_id']) and $params['buyer_id']){

            if(is_array($params['buyer_id'])){
                $query_builder->where_in('ps.buyer_id', $params['buyer_id']);
            }else{
                $buyers = explode(',', $params['buyer_id']);
                $query_builder->where_in('ps.buyer_id',$buyers);
            }
            unset($params['buyer_id']);
        }
        if (isset($params['product_line_id']) and $params['product_line_id']){

            if( is_array($params['product_line_id'])) {
                $query_builder->where_in('ps.product_line_id', $params['product_line_id']);
            }else{
                $query_builder->where('ps.product_line_id', $params['product_line_id']);
            }
            unset($params['product_line_id']);
        }

        if (isset($params['supplier_code']) and $params['supplier_code']){
            $query_builder->where('ps.supplier_code',$params['supplier_code']);
            unset($params['supplier_code']);
        }

        if (isset($params['is_drawback']) and $params['is_drawback']!=''){
            $query_builder->where('ps.is_drawback',$params['is_drawback']);
            unset($params['is_drawback']);
        }
        //产品状态
        if (isset($params['product_status']) and $params['product_status']!=''){
            if(is_array($params['product_status'])){
                $query_builder->where_in('pd.product_status', $params['product_status']);
            }else{
                $query_builder->where('pd.product_status', $params['product_status']);
            }
            unset($params['product_status']);
        }

        if (isset($params['suggest_status']) and $params['suggest_status']){
            if(!is_array($params['suggest_status'])) {
                $query_builder->where('ps.suggest_status', $params['suggest_status']);
            }else{
                $query_builder->where_in('ps.suggest_status',$params['suggest_status']);
            }
            unset($params['suggest_status']);
        }else{
            //锁单类型
            if (isset($params['lock_type']) and $params['lock_type']){

            }else{
                $query_builder->where('ps.suggest_status != ',SUGGEST_STATUS_EXPIRED);
            }
        }

        if (isset($params['demand_number']) and trim($params['demand_number'])){
            $demand_number_arr = array_filter(explode(' ',trim($params['demand_number'])));
            $query_builder->where_in('ps.demand_number',$demand_number_arr);
            unset($params['demand_number']);
        }
        if (isset($params['is_new']) and $params['is_new']!=''){
            if ($params['is_new']==1){
                $query_builder->where('ps.is_new',1);//是新品
            }else{
                $query_builder->where('ps.is_new',0);//不是新品
            }
            unset($params['is_new']);
        }

        if (isset($params['purchase_type_id']) and $params['purchase_type_id']){
            if(is_array($params['purchase_type_id'])){
                $query_builder->where_in('ps.purchase_type_id',$params['purchase_type_id']);
            }else{
                $query_builder->where('ps.purchase_type_id',$params['purchase_type_id']);
            }
            unset($params['purchase_type_id']);
        }

        if (isset($params['destination_warehouse']) and $params['destination_warehouse']){
            $query_builder->where('ps.destination_warehouse',$params['destination_warehouse']);
            unset($params['destination_warehouse']);
        }

        if (isset($params['logistics_type']) and $params['logistics_type']){
            $query_builder->where('ps.logistics_type=binary("'.$params['logistics_type'].'")');
            unset($params['logistics_type']);
        }

        if (isset($params['warehouse_code']) and $params['warehouse_code']){
            if(is_array($params['warehouse_code'])){
                $query_builder->where_in('ps.warehouse_code', $params['warehouse_code']);
            }else{
                $query_builder->where('ps.warehouse_code', $params['warehouse_code']);
            }
            unset($params['warehouse_code']);
        }

        if (isset($params['pertain_wms']) and $params['pertain_wms']){
            if(is_array($params['pertain_wms'])){
                $pertain_wms_list = implode("','",$params['pertain_wms']);
            }else{
                $pertain_wms_list = implode("','",explode(',',$params['pertain_wms']));
            }
            $query_builder->where("ps.warehouse_code IN(SELECT warehouse_code FROM pur_warehouse WHERE pertain_wms IN('{$pertain_wms_list}'))");
            unset($params['pertain_wms']);
        }

        if (isset($params['is_expedited']) and $params['is_expedited']){
            $query_builder->where('ps.is_expedited',$params['is_expedited']);
            unset($params['is_expedited']);
        }

        if (isset($params['create_user_id']) and $params['create_user_id']){
            $query_builder->where('ps.create_user_id',$params['create_user_id']);
            unset($params['create_user_id']);
        }

        if (isset($params['supply_status']) and $params['supply_status']){
            $query_builder->where_in('pd.supply_status',$params['supply_status']);
            unset($params['supply_status']);
        }

        if(isset($params['create_time_start']) and $params['create_time_start']){
            $params['create_time_start'] and $query_builder->where('ps.create_time >=',$params['create_time_start']);
            unset($params['create_time_start']);
        }
        if(isset($params['create_time_end']) and $params['create_time_end']){
            $params['create_time_end'] and $query_builder->where('ps.create_time <=',$params['create_time_end']);
            unset($params['create_time_end']);
        }

        ///缺货数量排序
        if (isset($params['left_stock_order']) && $params['left_stock_order'] && in_array($params['left_stock_order'],['asc','desc'])){
            $query_builder->order_by('ps.left_stock',$params['left_stock_order']);
            unset($params['left_stock_order']);
        }

        //供应商排序
        if (isset($params['supplier_order']) && $params['supplier_order'] && in_array($params['supplier_order'],['asc','desc'])){
            $query_builder->order_by('CONVERT(pd.supplier_name USING GBK)',$params['supplier_order']);
            unset($params['supplier_order']);
        }

        //是否精品
        if (isset($params['is_boutique']) and $params['is_boutique']!=''){
            $query_builder->where('ps.is_boutique',$params['is_boutique']);
            unset($params['is_boutique']);
        }

        //锁单类型
        if (isset($params['lock_type']) and $params['lock_type']){
            $query_builder->where('ps.lock_type',$params['lock_type']);
            unset($params['supply_status']);
        }

        //是否实单锁单
        if (isset($params['entities_lock_status']) and !empty($params['entities_lock_status'])){
            if ($params['entities_lock_status']==2){
                $query_builder->where('ps.lock_type',LOCK_SUGGEST_ENTITIES);
            }else{
                $query_builder->where('ps.lock_type',0);
            }

            unset($params['entities_lock_status']);
        }

        //作废原因
        if (isset($params['cancel_reason']) and !empty($params['cancel_reason'])){
            /*$count = count($params['cancel_reason']);

            for ($i=0;$i<$count;$i++){
                if($i==$count) break;
                if ($i==0){
                    $query_builder->group_start();
                    $query_builder->like('ps.cancel_reason',$params['cancel_reason'][$i],'after');

                }else{
                    $query_builder->or_group_start();
                    $query_builder->like('ps.cancel_reason',$params['cancel_reason'][$i],'after');
                    $query_builder->group_end();
                }

            }
            $query_builder->group_end();*/
            $query_builder->where_in('ps.cancel_reason_category',$params['cancel_reason']);

            unset($params['cancel_reason']);
        }

        //关联采购单是否已作废
        if (isset($params['connect_order_cancel']) and $params['connect_order_cancel']!=''){
            $query_builder->where('ps.connect_order_cancel',$params['connect_order_cancel']);
            unset($params['connect_order_cancel']);
        }

        // 预计到货时间
        if( isset($params['estimate_time_start']) && isset($params['estimate_time_end']) )
        {
            $query_builder->where('ps.create_time<ps.estimate_time')->where('ps.estimate_time>=',$params['estimate_time_start'])->where('ps.estimate_time<=',$params['estimate_time_end']);
        }

        if (isset($params['order_by']) and $params['order_by'] and isset($params['order']) and $params['order']){
            switch ($params['order_by']){
                case 1://供应商
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.supplier_name',$params['order']);
                    break;
                case 2://采购员
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.buyer_id',$params['order']);
                    break;
                case 3://产品名称
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.product_name',$params['order']);
                    break;
                case 4://是否退税
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.is_drawback',$params['order']);
                    break;
                case 5://预计到货时间
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.plan_product_arrive_time',$params['order']);
                    break;
                case 6://创建时间
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.id',$params['order']);
                    break;
                case 7://一级产品线
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.product_line_id',$params['order']);
                    break;
                case 8://二级产品线
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.two_product_line_id',$params['order']);
                    break;
                case 9://总金额
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.purchase_total_price',$params['order']);
                    break;
                case 10://审核时间
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.audit_time',$params['order']);
                    break;
                default:
            }

            unset($params['order_by']);
            unset($params['order']);
        }

        unset($params['export']);
        unset($params['order_by']);
        unset($params['order']);

        //锁单类型
        if (isset($params['lock_type']) and $params['lock_type']) {
            //锁单列表页面展示所有审核状态的备货单
            unset($params['lock_type']);
        }else{
            $query_builder->where('ps.audit_status',SUGGEST_AUDITED_PASS);
        }

        //  $query_builder_count    = clone $query_builder;// 克隆一个查询 用来计数
        $query_builder          = $query_builder->select('ps.*,pd.ticketed_point,pd.supply_status,pd.tax_rate');
        $query_builder_tmp      = clone $query_builder;// 克隆一个查询,用来返回查询的 SQL 语句
        //数据汇总
        //$total_count            = $query_builder_count->count_all_results();
        $query_sql              = $query_builder_tmp->get_compiled_select();// 获取查询的 SQL
        $results                = $query_builder->get()->result_array();
        $this->session->set_tempdata('purchase_suggest-get_list', $query_sql,500);//设置缓存和有效时间
        //判断改登录用户是否是销售 如果是就屏蔽敏感字段


        $return_data = [
            'data_list'   => $results,
            'page_data' => [
                'total'     => count($results),
                'offset'    => $page,
                'limit'     => $limit
            ]
        ];

        return $return_data;
    }


    /**
     * 获取采购统计
     * @author Jolon
     * @param      $params
     * @param int  $offset
     * @param int  $limit
     * @param int  $page
     * @return array
     */
    public function get_list_sum($params,$offset = 0,$limit = 0,$page = 1,$export = false){

        if(!is_null($limit)) {
            $limit = query_limit_range($limit, false);
        }
        $userid=jurisdiction(); //当前登录用户ID
        $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
        $role=get_user_role();//当前登录角色
        $data_role = getRole();//数据权限配置
        $res_arr = array_intersect($role, $data_role);
        $params                 = $this->table_query_filter($params);// 过滤为空的元素
        $query_builder          = $this->purchase_db;
        $query_builder->distinct()->from('purchase_suggest as ps ');
        $query_builder = $query_builder->join('product as pd','pd.sku=ps.sku','left');
        $query_builder->join('purchase_suggest_map as psm', 'psm.demand_number=ps.demand_number', 'left');

        $scree_query = "( SELECT sku,MAX(estimate_time) as estimate_time FROM pur_product_scree AS screet WHERE screet.status=50 AND apply_remark IN(4,10) GROUP BY sku ORDER BY estimate_time DESC ) as screed";
        if( isset( $params['is_scree']) ) {
            if( $params['is_scree'] == 1) {
                $scree_query = "SELECT sku FROM pur_product_scree AS screet WHERE screet.status IN (".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT.",".PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM.",".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM.")  GROUP BY sku ORDER BY estimate_time DESC ";
                $query_builder->where('ps.sku IN ('.$scree_query.')');
            }

            if( $params['is_scree'] == 2 ) {
                $scree_query = " SELECT sku FROM pur_product_scree AS screet WHERE screet.status NOT IN (".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT.",".PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM.",".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM.") GROUP BY sku ORDER BY id DESC";
                //$query_builder->join($scree_query,'screed.sku=ps.sku','left');

                $query_builder->where('ps.sku NOT IN ('.$scree_query.')');
            }
        }else{

            if( isset($params['delivery_time_start']) && !empty($params['delivery_time_start']) &&
                isset($params['delivery_time_end']) && !empty($params['delivery_time_end'])){

                $query_builder->join($scree_query, 'screed.sku=ps.sku', 'left');
            }
        }
        //$query_builder->join("( SELECT sku,estimate_time FROM pur_product_scree AS screet WHERE screet.status=50 AND apply_remark IN(4,10) GROUP BY sku ORDER BY estimate_time DESC ) as scree","ps.sku=scree.sku","LEFT");


        /*$scree_query = "( SELECT sku,MAX(estimate_time) AS estimate_time FROM pur_product_scree AS screet WHERE screet.status=50 AND apply_remark IN(4,10) GROUP BY sku ORDER BY estimate_time DESC ) as screed";

        if( isset( $params['is_scree']) ) {
            if( $params['is_scree'] == 1) {
                $scree_query = "( SELECT sku, MAX(estimate_time) AS estimate_time FROM pur_product_scree AS screet WHERE screet.status IN (".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT.",".PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM.",".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM.")  GROUP BY sku ORDER BY estimate_time DESC ) as screed";
                $query_builder->join($scree_query,'screed.sku=ps.sku','right');
            }

            if( $params['is_scree'] == 2 ) {

                $scree_query = "( SELECT sku,MAX(estimate_time) AS estimate_time FROM pur_product_scree AS screet WHERE screet.status NOT IN (".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT.",".PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM.",".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM.") GROUP BY sku ORDER BY estimate_time DESC ) as screed";
                $query_builder->join($scree_query,'screed.sku=ps.sku','left');
            }


        }else{

            if(
                isset($params['delivery_time_start']) && !empty($params['delivery_time_start']) &&
                isset($params['delivery_time_end']) && !empty($params['delivery_time_end'])
            ) {
                $query_builder->join($scree_query, 'screed.sku=ps.sku', 'left');
            }
        }*/

        if( isset($params['temp_container']) && !empty($params['temp_container'])){

            $params['temp_container'] = explode(" ",$params['temp_container']);
            $query_builder->where_in("ps.temp_container",$params['temp_container']);
        }
        if( isset($params['delivery_time_start']) && !empty($params['delivery_time_start'])){

            $query_builder->where("screed.estimate_time>=",$params['delivery_time_start']);
        }

        if( isset($params['delivery_time_end']) && !empty($params['delivery_time_end'])){

            $query_builder->where("screed.estimate_time<=",$params['delivery_time_end']);
        }
        $keys = $this->purchase_order_sum_model->get_key($params,"suggest");
//        if( !isset($params['new']) || empty($params['new'])) {
//            $result = $this->purchase_order_sum_model->get_sum_cache($keys);
//
//            if( !empty($result) ) {
//                return $result;
//            }
//        }
        if( !(!empty($res_arr) OR $userid === true )){
            $query_builder->where_in('ps.buyer_id',$userid);
        }

        if( isset($params['payment_method_source']) && !empty($params['payment_method_source']))
        {

            if( $params['payment_method_source'] == 1){

                // 合同 1.线上支付宝,2.线下境内（对公支付），3.线下境外（对私支付）
                //'支付方式 1.线上支付宝,2.线下境内（对公支付），3.线下境外（对私支付）'
                // 根据业务线、供应商结算方式判断，线下为合同，线上为网采，数据为实时判断，不存储；

                $query_builder->where_in("ps.source",[1]);
            }else{
                $query_builder->where_in("ps.source",[2]);
            }
        }

        if( isset($params['is_thousand']) && !empty($params['is_thousand'])){

            if( $params['is_thousand'] == 1) {
                // 未关联
                $query_builder->where("pd.is_relate_ali", 0);
            }else{
                // 已关联
                $query_builder->where("pd.is_relate_ali", 1);
            }
        }

        if(isset($params['group_ids']) && !empty($params['group_ids'])){

            $query_builder->where_in('ps.buyer_id',$params['groupdatas']);
        }

        if(isset($params['user_groups_types'])){
            $user_groups_types = $params['user_groups_types'];
        }
        if(is_array($user_groups_types)){
            $query_builder->where_in('ps.purchase_type_id', $user_groups_types);
        }
        // is_purchasing
        if( isset($params['is_purchasing']) && !empty($params['is_purchasing'])){

            $query_builder->where('pd.is_purchasing',$params['is_purchasing']);
        }

        if (!empty($params['sku'])) {
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索
                $this->purchase_db->like('ps.sku', $params['sku'], 'both');
            } else {
                $this->purchase_db->where_in('ps.sku', $sku);
            }
        }

        if(isset($params['transformation']) && !empty($params['transformation'])){

            if( $params['transformation'] == 1){
                $query_builder->where('ps.sku_state_type!=',6);
            }else {

                $query_builder->where('ps.sku_state_type', $params['transformation']);
            }
        }


        if( isset($params['is_overseas_first_order']) && $params['is_overseas_first_order'] != NULL ){

            $query_builder->where("ps.is_overseas_first_order",$params['is_overseas_first_order']);
        }
        // 发运类型

        if( isset($params['shipment_type']) && !empty($params['shipment_type'])){
            $query_builder->where("ps.shipment_type",$params['shipment_type']);
        }

        //缺货数量(新)
        if( (isset($params['new_lack_qty_start']) && is_numeric($params['new_lack_qty_start'])) || (isset($params['new_lack_qty_end']) && is_numeric($params['new_lack_qty_end'])))
        {
            $query_builder->join('think_lack_info tli','tli.sku = ps.sku', 'left');
            if(isset($params['new_lack_qty_start']) && $params['new_lack_qty_start'] != ''){
                $query_builder->where("tli.lack_sum >=",$params['new_lack_qty_start']);
            }
            if(isset($params['new_lack_qty_end']) && $params['new_lack_qty_end'] != ''){
                $query_builder->where("tli.lack_sum <=",$params['new_lack_qty_end']);
            }
        }


//        if( isset( $params['is_scree']) ) {
//
//            $query_builder->join("product_scree AS scree","ps.sku=scree.sku","LEFT");
//            if( $params['is_scree'] == 1) {
//                $query_builder->where_in("scree.status",[PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM,PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM]);
//            }
//
//            if( $params['is_scree'] ==2 ) {
//
//                $query_builder->where_not_in("scree.status",[PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM,PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM]);
//                $query_builder->or_where_not_in("ps.sku","SELECT sku FROM pur_product_scree GROUP BY sku");
//            }
//        }
        if( isset($params['is_ticketed_point']) && !empty($params['is_ticketed_point']))
        {
            if( $params['is_ticketed_point'] == 1)
            {
                $query_builder->where("pd.maintain_ticketed_point",0);
            }else{

                $query_builder->where("pd.maintain_ticketed_point",1);
            }
        }

        if( isset($params['transformation']) && !empty($params['transformation']))
        {
            if( $params['transformation'] == 1){

                $params['transformation'] =0;
                $query_builder->where("ps.sku_state_type!=6");
            }else {
                $query_builder->where("ps.sku_state_type", $params['transformation']);
            }
        }



        if(isset($params['purchase_order_status'])){
            $query_builder->join('purchase_suggest_map as psm', 'psm.demand_number=ps.demand_number', 'left');
            $query_builder->join('purchase_order as po', 'po.purchase_number=psm.purchase_number', 'left');
            if(gettype($params['purchase_order_status']) == "array"){
                $query_builder->where_in('po.purchase_order_status',$params['purchase_order_status']);
            }else{
                $query_builder->where('po.purchase_order_status',$params['purchase_order_status']);
            }
            unset($params['purchase_order_status']);
        }
        if(isset($params['plan_product_arrive_time_start']) and $params['plan_product_arrive_time_start']){
            $params['plan_product_arrive_time_start'] and $query_builder->where('ps.earliest_exhaust_date >=',$params['plan_product_arrive_time_start']);
            unset($params['plan_product_arrive_time_start']);
        }
        if(isset($params['plan_product_arrive_time_end']) and $params['plan_product_arrive_time_end']){
            $params['plan_product_arrive_time_end'] and $query_builder->where('ps.earliest_exhaust_date<',$params['plan_product_arrive_time_end']);
            unset($params['plan_product_arrive_time_end']);
        }

        if(isset($params['id']) && $params['id']!=""){
            if( is_string($params['id']) ) {
                $ids = explode(',', $params['id']);
            }
            $query_builder->where_in('ps.id',$ids);
            unset($params['id']);
        }
        if(isset($params['is_create_order']) && is_numeric($params['is_create_order'])){
            $query_builder->where('ps.is_create_order',(int)$params['is_create_order']);
            unset($params['is_create_order']);
        }
        if(isset($params['is_left_stock']) && is_numeric($params['is_left_stock'])){
            if(intval($params['is_left_stock']) == 1){
                $query_builder->where('ps.left_stock <',0);
            }else{
                $query_builder->where('ps.left_stock >=',0);
            }
            unset($params['is_left_stock']);
        }

        if (isset($params['demand_type_id']) and $params['demand_type_id']){
            $query_builder->where_in('ps.demand_type_id',$params['demand_type_id']);
            unset($params['demand_type_id']);
        }

        if (isset($params['buyer_id']) and $params['buyer_id']){

            if(is_array($params['buyer_id'])){
                $query_builder->where_in('ps.buyer_id', $params['buyer_id']);
            }else{
                $buyers = explode(',', $params['buyer_id']);
                $query_builder->where_in('ps.buyer_id',$buyers);
            }
            unset($params['buyer_id']);
        }
        if (isset($params['product_line_id']) and $params['product_line_id']){
            if(is_array($params['product_line_id'])){
                $query_builder->where_in('ps.product_line_id', $params['product_line_id']);
            }else{
                $query_builder->where('ps.product_line_id', $params['product_line_id']);
            }
            unset($params['product_line_id']);
        }

        if (isset($params['supplier_code']) and $params['supplier_code']){
            $query_builder->where('ps.supplier_code',$params['supplier_code']);
            unset($params['supplier_code']);
        }

        if (isset($params['is_drawback']) and $params['is_drawback']!=''){
            $query_builder->where('ps.is_drawback',$params['is_drawback']);
            unset($params['is_drawback']);
        }
        //产品状态
        if (isset($params['product_status']) and $params['product_status']!=''){
            if(is_array($params['product_status'])){
                $query_builder->where_in('pd.product_status',$params['product_status']);
            }else{
                $query_builder->where('pd.product_status', $params['product_status']);
            }
            unset($params['product_status']);
        }

        if (isset($params['suggest_status']) and $params['suggest_status']){
            if(!is_array($params['suggest_status'])) {
                $query_builder->where('ps.suggest_status', $params['suggest_status']);
            }else{
                $query_builder->where_in('ps.suggest_status', $params['suggest_status']);
            }
            unset($params['suggest_status']);
        }else{
            if (isset($params['lock_type']) and $params['lock_type']){

            }else{
                $query_builder->where('ps.suggest_status != ',SUGGEST_STATUS_EXPIRED);
            }
        }

        // 是否新品
        if( isset($params['is_new']) && $params['is_new']!=''){
            $query_builder->where("ps.is_new",$params['is_new']);
        }

        if (isset($params['demand_number']) and trim($params['demand_number'])){
            $demand_number_arr = array_filter(explode(' ',trim($params['demand_number'])));
            $query_builder->where_in('ps.demand_number',$demand_number_arr);
            unset($params['demand_number']);
        }

        if (isset($params['purchase_type_id']) and $params['purchase_type_id']){
            if(is_array($params['purchase_type_id'])){
                $query_builder->where_in('ps.purchase_type_id',$params['purchase_type_id']);
            }else{
                $query_builder->where('ps.purchase_type_id',$params['purchase_type_id']);
            }
            unset($params['purchase_type_id']);
        }

        if (isset($params['pertain_wms']) and $params['pertain_wms']){
            if(is_array($params['pertain_wms'])){
                $pertain_wms_list = implode("','",$params['pertain_wms']);
            }else{
                $pertain_wms_list = implode("','",explode(',',$params['pertain_wms']));
            }
            $query_builder->where("ps.warehouse_code IN(SELECT warehouse_code FROM pur_warehouse WHERE pertain_wms IN('{$pertain_wms_list}'))");
            unset($params['pertain_wms']);
        }

        if (isset($params['destination_warehouse']) and $params['destination_warehouse']){
            $query_builder->where('ps.destination_warehouse',$params['destination_warehouse']);
            unset($params['destination_warehouse']);
        }

        if (isset($params['logistics_type']) and $params['logistics_type']){
            $query_builder->where('ps.logistics_type=binary("'.$params['logistics_type'].'")');
            unset($params['logistics_type']);
        }

        if (isset($params['warehouse_code']) and $params['warehouse_code']){
            if(is_array($params['warehouse_code'])){
                $query_builder->where_in('ps.warehouse_code', $params['warehouse_code']);
            }else{
                $query_builder->where('ps.warehouse_code', $params['warehouse_code']);
            }
            unset($params['warehouse_code']);
        }

        if (isset($params['is_expedited']) and $params['is_expedited']){
            $query_builder->where('ps.is_expedited',$params['is_expedited']);
            unset($params['is_expedited']);
        }

        if (isset($params['create_user_id']) and $params['create_user_id']){
            $query_builder->where('ps.create_user_id',$params['create_user_id']);
            unset($params['create_user_id']);
        }

        if (isset($params['supply_status']) and $params['supply_status']){
            $query_builder->where_in('pd.supply_status',$params['supply_status']);
            unset($params['supply_status']);
        }

        if(isset($params['create_time_start']) and $params['create_time_start']){
            $params['create_time_start'] and $query_builder->where('ps.create_time >=',$params['create_time_start']);
            unset($params['create_time_start']);
        }
        if(isset($params['create_time_end']) and $params['create_time_end']){
            $params['create_time_end'] and $query_builder->where('ps.create_time <=',$params['create_time_end']);
            unset($params['create_time_end']);
        }

        ///缺货数量排序
        if (isset($params['left_stock_order']) && $params['left_stock_order'] && in_array($params['left_stock_order'],['asc','desc'])){
            $query_builder->order_by('ps.left_stock',$params['left_stock_order']);
            unset($params['left_stock_order']);
        }

        //供应商排序
        if (isset($params['supplier_order']) && $params['supplier_order'] && in_array($params['supplier_order'],['asc','desc'])){
            $query_builder->order_by('CONVERT(pd.supplier_name USING GBK)',$params['supplier_order']);
            unset($params['supplier_order']);
        }

        //是否精品
        if (isset($params['is_boutique']) and $params['is_boutique']!=''){
            $query_builder->where('ps.is_boutique',$params['is_boutique']);
            unset($params['is_boutique']);
        }

        //锁单类型
        if (isset($params['lock_type']) and $params['lock_type']){
            $query_builder->where('ps.lock_type',$params['lock_type']);
            unset($params['supply_status']);
        }

        //开发类型
        if(isset($params['state_type']) && $params['state_type']){
            if(is_array($params['state_type'])){
                $query_builder->where_in('pd.state_type',$params['state_type']);
            }else{
                $query_builder->where('pd.state_type',$params['state_type']);
            }
        }

        //是否实单锁单
        if (isset($params['entities_lock_status']) and !empty($params['entities_lock_status'])){
            if ($params['entities_lock_status']==2){
                $query_builder->where('ps.lock_type',LOCK_SUGGEST_ENTITIES);
            }else{
                $query_builder->where('ps.lock_type',0);
            }

            unset($params['entities_lock_status']);
        }

        //作废原因
        if (isset($params['cancel_reason']) and !empty($params['cancel_reason'])){
            /*$count = count($params['cancel_reason']);

            for ($i=0;$i<$count;$i++){
                if($i==$count) break;
                if ($i==0){
                    $query_builder->group_start();
                    $query_builder->like('ps.cancel_reason',$params['cancel_reason'][$i],'after');

                }else{
                    $query_builder->or_group_start();
                    $query_builder->like('ps.cancel_reason',$params['cancel_reason'][$i],'after');
                    $query_builder->group_end();
                }

            }
            $query_builder->group_end();*/
            $query_builder->where_in('ps.cancel_reason_category',$params['cancel_reason']);

            unset($params['cancel_reason']);
        }

        //关联采购单是否已作废
        if (isset($params['connect_order_cancel']) and $params['connect_order_cancel']!=''){
            $query_builder->where('ps.connect_order_cancel',$params['connect_order_cancel']);
            unset($params['connect_order_cancel']);
        }

        // 29777
        if(isset($params['is_oversea_boutique']) && is_numeric($params['is_oversea_boutique'])){
            $query_builder->where(['ps.is_overseas_boutique' => $params['is_oversea_boutique']]);
        }

        unset($params['export']);
        unset($params['order_by']);
        unset($params['order']);

        //锁单类型
        if (isset($params['lock_type']) and $params['lock_type']) {
            //锁单列表页面展示所有审核状态的备货单
            unset($params['lock_type']);
        }else{
            $query_builder->where('ps.audit_status',SUGGEST_AUDITED_PASS);
        }

        $query_builder_count    = clone $query_builder;// 克隆一个查询 用来计数
        $query_builder_sum      = clone $query_builder;// 克隆一个查询 用来做数据汇总
        $query_builder_tmp      = clone $query_builder;// 克隆一个查询,用来返回查询的 SQL 语句
        //数据汇总
        $huizong_arr            = $query_builder_sum->select('count(ps.id) AS total_count,sum(ps.purchase_amount) as purchase_amount_all, 
         sum(ps.purchase_total_price) as purchase_unit_price_all,count(distinct ps.sku) as sku_all,count(distinct ps.supplier_code) as supplier')->get()->row_array();
        // $total_count            = $query_builder_count->count_all_results();
        $query_sql              = $query_builder_tmp->get_compiled_select();// 获取查询的 SQL
        $this->session->set_tempdata('purchase_suggest-get_list', $query_sql,50);//设置缓存和有效时间
        //判断改登录用户是否是销售 如果是就屏蔽敏感字段


        $return_data = [
            'page_data' => [
                'total'     => $huizong_arr['total_count'],
                'offset'    => $page,
                'limit'     => $limit
            ],
            'aggregate_data'  => $huizong_arr,
        ];
        $this->purchase_order_sum_model->set_sum_cache($keys,$return_data);

        return $return_data;
    }

    private function get_params_where($params,$where_no) {


        if( !empty($params)) {

            $where = " 1=1 ";
            foreach( $params as $key=>$value ) {
                if( !in_array($key,$where_no)) {
                    if( $key !='is_new') {
                        if (is_string($value)) {

                            if (in_array($key, array('create_time_start', 'create_time_end'))) {

                                if ($key == "create_time_start") {

                                    $where .= " AND create_time>='" . $value . "'";
                                }

                                if ($key == "create_time_end") {

                                    $where .= " AND create_time<'" . $value . "'";
                                }
                            } else {

                                $where .= " AND {$key}='" . $value . "'";
                            }
                        }
                        if (is_array($value)) {

                            $where .= " AND {$key} IN (" . implode(",", $value) . ")";
                        }
                        if (is_integer($value)) {
                            $where .= " AND {$key} =" . $value;
                        }
                    }

                    if( $key == 'is_new' ) {
                        if ($value ==PURCHASE_PRODUCT_IS_NEW_Y){
                            $where .= " AND sale_state=".SKU_STATE_IS_NEW;
                        }else{
                            $where .= " AND sale_state !=".SKU_STATE_IS_NEW;
                        }
                    }
                }

            }
            $where .= " AND audit_status=".SUGGEST_AUDITED_PASS;
            return $where;
        }

        return NULL;
    }

    public function get_list1($params,$offset = 0,$limit = 0,$page = 1,$export = false){

        if(!is_null($limit)) {
            $limit = query_limit_range($limit, false);
        }

        $params                 = $this->table_query_filter($params);// 过滤为空的元素
        //$query_builder->where('ps.suggest_status != ',SUGGEST_STATUS_EXPIRED);

        if(isset($params['id']) && $params['id']!=""){
            $ids= explode(',', $params['id']);
            $params['id'] = $ids;
        }

        $query_builder          = $this->purchase_db;
        $where_no = array('purchase_order_status','product_status','supply_status','left_stock_order','suggest_status',
            'lock_type','state_type');
        $where = $this->get_params_where($params,$where_no);
        if( isset($params['suggest_status']) && !empty($params['suggest_status']) ) {

            $where.= " AND suggest_status=".$params['suggest_status'];
        }else{

            //$params['suggest_status'] = array("!=",SUGGEST_STATUS_EXPIRED);
            $where.=" AND suggest_status!=".SUGGEST_STATUS_EXPIRED;
        }

        //锁单类型
        if (isset($params['lock_type']) and $params['lock_type']){

            $where.= " AND lock_type=".$params['lock_type'];
        }else{
            $where.= " AND lock_type=0";
        }

        $sql = " SELECT * FROM pur_purchase_suggest WHERE ".$where;

        $query_builder->from('product as pd ');
        $query_builder->join("(".$sql.') AS ps','ps.sku=pd.sku');

        if(isset($params['purchase_order_status'])){
            $query_builder->join('purchase_suggest_map as psm', 'psm.demand_number=ps.demand_number', 'left');
            $query_builder->join('purchase_order as po', 'po.purchase_number=psm.purchase_number', 'left');
            if(gettype($params['purchase_order_status']) == "array"){
                $query_builder->where_in('po.purchase_order_status',$params['purchase_order_status']);
            }else{
                $query_builder->where('po.purchase_order_status',$params['purchase_order_status']);
            }
            unset($params['purchase_order_status']);
        }

        //产品状态
        if (isset($params['product_status']) and $params['product_status']!=''){
            if(is_array($params['product_status'])){
                $query_builder->where_in('pd.product_status',$params['product_status']);
            }else{
                $query_builder->where('pd.product_status',$params['product_status']);
            }
            unset($params['product_status']);
        }

        if (isset($params['supply_status']) and $params['supply_status']){
            $query_builder->where('pd.supply_status',$params['supply_status']);
            unset($params['supply_status']);
        }

        // 29777
        if(isset($params['is_oversea_boutique']) && is_numeric($params['is_oversea_boutique'])){
            $query_builder->where(['ps.is_overseas_boutique' => $params['is_oversea_boutique']]);
        }

        //开发类型
        if (isset($params['state_type']) && !empty($params['state_type'])){
            if(is_array($params['state_type'])){
                $query_builder->where_in('pd.state_type',$params['state_type']);
            }else{
                $query_builder->where('pd.state_type',$params['state_type']);
            }

            unset($params['state_type']);
        }

        ///缺货数量排序
        if (isset($params['left_stock_order']) && $params['left_stock_order'] && in_array($params['left_stock_order'],['asc','desc'])){
            $query_builder->order_by('ps.left_stock',$params['left_stock_order']);
            unset($params['left_stock_order']);
        }

        //供应商排序
        if (isset($params['supplier_order']) && $params['supplier_order'] && in_array($params['supplier_order'],['asc','desc'])){
            $query_builder->order_by('CONVERT(pd.supplier_name USING GBK)',$params['supplier_order']);
            unset($params['supplier_order']);
        }

        if (isset($params['order_by']) and $params['order_by'] and isset($params['order']) and $params['order']){
            switch ($params['order_by']){
                case 1://供应商
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.supplier_name',$params['order']);
                    break;
                case 2://采购员
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.buyer_id',$params['order']);
                    break;
                case 3://产品名称
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.product_name',$params['order']);
                    break;
                case 4://是否退税
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.is_drawback',$params['order']);
                    break;
                case 5://预计到货时间
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.plan_product_arrive_time',$params['order']);
                    break;
                case 6://创建时间
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.id',$params['order']);
                    break;
                case 7://一级产品线
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.product_line_id',$params['order']);
                    break;
                case 8://二级产品线
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.two_product_line_id',$params['order']);
                    break;
                case 9://总金额
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.purchase_total_price',$params['order']);
                    break;
                case 10://审核时间
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.audit_time',$params['order']);
                    break;
                default:
            }

            unset($params['order_by']);
            unset($params['order']);
        }
        $query_builder          = $query_builder->select('ps.*,pd.ticketed_point,pd.supply_status,pd.state_type');
        $query_builder_tmp      = clone $query_builder;// 克隆一个查询,用来返回查询的 SQL 语句
        $huizong_arr             = array();
        $total_count             = 0;
        $query_sql              = $query_builder_tmp->get_compiled_select();// 获取查询的 SQL
        if($export){//导出查询，不需要传分页
            $results                = $query_builder->get()->result_array();
        }else{//列表查询

            $query_builder->order_by('ps.id','desc');
            $results                = $query_builder->get('',$limit,$offset)->result_array();

        }
        $this->session->set_tempdata('purchase_suggest-get_list', $query_sql,500);//设置缓存和有效时间
        //判断改登录用户是否是销售 如果是就屏蔽敏感字段


        $return_data = [
            'data_list'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'offset'    => $page,
                'limit'     => $limit
            ],
            'aggregate_data'  => $huizong_arr,
        ];

        return $return_data;



//        $result = $query_builder->get()->result_array();
//        echo $query_builder->last_query();die();
    }


    private function get_product_scree( $skus,$sku ) {

        if( empty($skus) || !in_array( $sku,$skus)) {

            return 0;
        }

        return 1;
    }

    /**
     * 锁单优化接口
     *   只需要保留SKU 、采购单号、备货单号、供应商名称、创建日期，是否新品、是否海外首单，其他全部可以删掉
     **/
    public function get_entities_lock_list($params,$offset = 0,$limit = 0,$page = 1,$export = false){

        if(!is_null($limit)) {
            $limit = query_limit_range($limit, false);
        }
        $userid=jurisdiction(); //当前登录用户ID
        $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
        $role=get_user_role();//当前登录角色
        $data_role = getRole();//数据权限配置
        $res_arr = array_intersect($role, $data_role);
        $params                 = $this->table_query_filter($params);// 过滤为空的元素
        $query_builder          = $this->purchase_db;

        $inisdQuery = "SELECT ps.* FROM pur_purchase_suggest AS ps ";

        if( isset($params['purchase_number']) && !empty($params['purchase_number'])){

            $inisdQuery .= " LEFT JOIN pur_purchase_suggest_map AS map ON ps.demand_number=map.demand_number AND ps.sku=map.sku ";
        }

        $inisdQuery .= " WHERE 1=1 ";
        if (!(!empty($res_arr) or $userid === true)) {
            $inisdQuery .= "  AND ps.buyer_id IN (".implode(",",$userid).")";
        }
        if(isset($params['group_ids']) && !empty($params['group_ids'])){
            $inisdQuery .= " AND ps.buyer_id IN (".implode(",",$params['groupdatas']).")";
        }

        if(isset($params['user_groups_types'])){
            $user_groups_types = $params['user_groups_types'];
        }
        if(is_array($user_groups_types)){
            $inisdQuery .= " AND ps.purchase_type_id in (".implode(",", $user_groups_types).")";
        }

        if( isset($params['payment_method_source']) && !empty($params['payment_method_source']))
        {

            if( $params['payment_method_source'] == 1){

                // 合同 1.线上支付宝,2.线下境内（对公支付），3.线下境外（对私支付）
                //'支付方式 1.线上支付宝,2.线下境内（对公支付），3.线下境外（对私支付）'
                // 根据业务线、供应商结算方式判断，线下为合同，线上为网采，数据为实时判断，不存储；
                $inisdQuery .= " AND ps.source=1";
            }else{
                $inisdQuery .= " AND ps.source=2";
            }
        }

        if (!empty($params['sku'])) {
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索

                $inisdQuery .= " AND ps.sku LIKE '%".$params['sku']."%'";
            } else {

                $skus = array_map(function($sku){

                    return sprintf("'%s'",$sku);
                },$sku);

                $inisdQuery .= " AND ps.sku IN (".implode(",",$skus).")";
            }
        }

        if (isset($params['demand_type_id']) and $params['demand_type_id']){
            $inisdQuery .= " AND ps.demand_type_id IN (".$params['demand_type_id'].")";
            unset($params['demand_type_id']);
        }

        if (isset($params['supplier_code']) and $params['supplier_code']){

            if( !is_array($params['supplier_code'])){

                $inisdQuery .= " AND ps.supplier_code='".$params['supplier_code']."'";
            }
            unset($params['supplier_code']);
        }

        if( isset($params['purchase_number']) && !empty($params['purchase_number'])){

            if( !is_array($params['purchase_number'])) {
                $inisdQuery .= " AND map.purchase_number='".$params['purchase_number']."'";
            }else{

                $purchaseNumbers = array_map(function($numbers){

                    return sprintf("'%s'",$numbers);
                },$params['purchase_number']);

                $inisdQuery .= " AND map.purchase_number IN (".implode(",",$purchaseNumbers).")";
            }
        }

        if (isset($params['is_boutique']) and $params['is_boutique']!=''){

            $inisdQuery .= " AND ps.is_boutique=".$params['is_boutique'];
            unset($params['is_boutique']);
        }

        if( isset($params['create_time_start']) && !empty($params['create_time_start'])){

            $inisdQuery .= " AND ps.create_time>='".$params['create_time_start']."'";
        }

        if( isset($params['create_time_end']) && !empty($params['create_time_end'])){

            $inisdQuery .= " AND ps.create_time<='".$params['create_time_end']."'";
        }

        if( isset($params['is_overseas_first_order']) && $params['is_overseas_first_order'] != NULL ){

            $inisdQuery .= " AND ps.is_overseas_first_order=".$params['is_overseas_first_order'];
        }

        if (isset($params['product_line_id']) and $params['product_line_id']){
            if(is_array($params['product_line_id'])){
                $inisdQuery .= " AND ps.product_line_id IN (".implode(",",$params['product_line_id']).")";
            }else{

                $inisdQuery .= " AND ps.product_line_id=".$params['product_line_id'].")";
            }
            unset($params['product_line_id']);
        }

        if( isset($params['demand_number']) && !empty($params['demand_number'])){


            $demandNumbersdata = explode(" ",$params['demand_number']);
            $demandNumbers = array_map(function($numbers){

                return sprintf("'%s'",$numbers);
            },$demandNumbersdata);

            $inisdQuery .= " AND ps.demand_number IN (".implode(",",$demandNumbers).")";

        }

        if (isset($params['is_new']) and $params['is_new']!=''){
            if ($params['is_new']==1){

                $inisdQuery .= " AND ps.is_new=1";
            }else{
                $inisdQuery .= " AND ps.is_new=0";
            }
            unset($params['is_new']);
        }

        if (isset($params['buyer_id']) and $params['buyer_id']){

            if(is_array($params['buyer_id'])){

                $inisdQuery .=" AND ps.buyer_id IN (".implode(",",$params['buyer_id']).")";

            }else{
                $inisdQuery .= " AND ps.buyer_id=".$params['buyer_id'];
            }
            unset($params['buyer_id']);
        }

        if(isset($params['transformation']) && !empty($params['transformation'])){

            if( $params['transformation'] == 1){

                $inisdQuery .= " AND ps.sku_state_type!=6";
            }else {
                $inisdQuery .= " AND ps.sku_state_type=".$params['transformation'];
            }
        }

        if (isset($params['suggest_status']) and $params['suggest_status']){
            if(is_array($params['suggest_status'])){

                $inisdQuery .= " AND ps.suggest_status IN (".implode(",",$params['suggest_status']).")";
            }else{
                $inisdQuery .= " AND ps.suggest_status=".$params['suggest_status'];
            }

            unset($params['suggest_status']);
        }else{
//            $inisdQuery .= " AND ps.suggest_status!=".SUGGEST_STATUS_EXPIRED;
        }
        $inisdQuery .= " AND ps.lock_type=2";
        if(isset($params['order_by']) ){

            if($params['order_by'] == 9) {
                $inisdQuery .= " ORDER BY ps.purchase_total_price " . $params['order'];
            }

            if( $params['order_by'] == 11){

                $inisdQuery .= " ORDER BY ps.purchase_amount ".$params['order'];
            }
        }else{
            $inisdQuery .= " ORDER BY ps.id DESC ";
        }
        $inisdQuery .= "  LIMIT {$offset},{$limit}";
        $query = " 
                   SELECT suggest.*,pd.supply_status,
                   pd.is_purchasing as tis_purchasing,pd.maintain_ticketed_point,
                   pd.ticketed_point,pd.supply_status,pd.state_type,pd.starting_qty,
                   pd.starting_qty_unit,pd.tax_rate,pd.declare_unit,pd.product_status,pd.product_thumb_url
                   FROM pur_product AS pd
                   RIGHT JOIN (".$inisdQuery.") AS suggest ON suggest.sku=pd.sku ";




        $results = $this->purchase_db->query($query)->result_array();

        $return_data = [
            'data_list'   => $results,
            'page_data' => [
                'total'     => 0,
                'offset'    => (int)$page,
                'limit'     => $limit
            ],
            'aggregate_data'  => [],
        ];

        return $return_data;


    }

    /**
     * 获取采购需求列表
     * @author Jolon
     * @param      $params
     * @param int  $offset
     * @param int  $limit
     * @param int  $page
     * @return array
     */
    public function get_list($params,$offset = 0,$limit = 0,$page = 1,$export = false){
        if(!is_null($limit)) {
            $limit = query_limit_range($limit, false);
        }
        $userid=jurisdiction(); //当前登录用户ID
        $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
        $role=get_user_role();//当前登录角色
        $data_role = getRole();//数据权限配置
        $res_arr = array_intersect($role, $data_role);
        $params                 = $this->table_query_filter($params);// 过滤为空的元素
        $query_builder          = $this->purchase_db;
        $query_builder->distinct()->from('purchase_suggest as ps ');
        $query_builder = $query_builder->join('product as pd','pd.sku=ps.sku','left');

        $scree_query = "( SELECT sku,MAX(estimate_time) as estimate_time FROM pur_product_scree AS screet WHERE screet.status=50 AND apply_remark IN(4,10) GROUP BY sku ORDER BY estimate_time DESC ) as screed";
        if( isset( $params['is_scree']) ) {
            if( $params['is_scree'] == 1) {
                $scree_query = "SELECT sku FROM pur_product_scree AS screet WHERE screet.status IN (".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT.",".PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM.",".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM.")  GROUP BY sku ORDER BY estimate_time DESC ";
                $query_builder->where('ps.sku IN ('.$scree_query.')');
            }

            if( $params['is_scree'] == 2 ) {
                $scree_query = " SELECT sku FROM pur_product_scree AS screet WHERE screet.status NOT IN (".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT.",".PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM.",".PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM.") GROUP BY sku ORDER BY id DESC";
                //$query_builder->join($scree_query,'screed.sku=ps.sku','left');

                $query_builder->where('ps.sku NOT IN ('.$scree_query.')');
            }
        }else{

            if( isset($params['delivery_time_start']) && !empty($params['delivery_time_start']) &&
                isset($params['delivery_time_end']) && !empty($params['delivery_time_end'])){

                $query_builder->join($scree_query, 'screed.sku=ps.sku', 'left');
            }
        }

        $scree_join = '';
        /*$base_scree_query = 'SELECT sku,MAX(estimate_time) as estimate_time, sku as is_scree FROM pur_product_scree AS screet WHERE';
        $scree_query = "( {$base_scree_query} screet.status=50 AND apply_remark IN(4,10) GROUP BY sku ORDER BY estimate_time DESC ) as screed";

        $scree_status = [PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT, PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM, PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM];
        if( isset( $params['is_scree']) ) {
            $scree_order = '';
            if($params['is_scree'] == 1) {
                $scree_join = ',screed.is_scree';
                $scree_order = 'ORDER BY estimate_time DESC';
            }
            if($params['is_scree'] == 2 ) {
                $scree_order = 'ORDER BY id DESC';
            }
            $scree_query = "( {$base_scree_query} screet.status IN (".implode(',', $scree_status).")  GROUP BY sku {$scree_order} ) as screed";
            $query_builder->join($scree_query,'screed.sku=ps.sku','right');
        }else{

            if( isset($params['delivery_time_start']) && !empty($params['delivery_time_start']) &&
                isset($params['delivery_time_end']) && !empty($params['delivery_time_end'])){
                $query_builder->join($scree_query, 'screed.sku=ps.sku', 'left');
            }
        }*/

        // 29777
        if(isset($params['is_oversea_boutique']) && $params['is_oversea_boutique'] != NULL ){
            $query_builder->where(['ps.is_overseas_boutique' => $params['is_oversea_boutique']]);
        }
        // $query_builder->join(","ps.sku=screed.sku","LEFT");

        if(isset($params['temp_container']) && !empty($params['temp_container'])){

            $params['temp_container'] = explode(" ",$params['temp_container']);
            $query_builder->where('ps.temp_container',$params['temp_container']);
        }
        if( isset($params['delivery_time_start']) && !empty($params['delivery_time_start'])){

            $query_builder->where("screed.estimate_time>=",$params['delivery_time_start']);
        }

        if( isset($params['delivery_time_end']) && !empty($params['delivery_time_end'])){

            $query_builder->where("screed.estimate_time<=",$params['delivery_time_end']);
        }

        if(!isset($params['buyer_id_flag'])) {

            if ( !(!empty($res_arr) or $userid === true) ) {

                $query_builder->where_in('ps.buyer_id', $userid);
            }
        }else if(isset($params['buyer_id_flag']) && isset($params['buyer_id'])  && $params['buyer_id_flag']!=0){

            $query_builder->where_in('ps.buyer_id', $params['buyer_id']);
        }
        if(isset($params['group_ids']) && !empty($params['group_ids'])){

            $query_builder->where_in('ps.buyer_id',$params['groupdatas']);
        }
        if(isset($params['user_groups_types'])){
            $user_groups_types = $params['user_groups_types'];
        }
        if(is_array($user_groups_types)){
            $query_builder->where_in('ps.purchase_type_id', $user_groups_types);
        }

        if( isset($params['payment_method_source']) && !empty($params['payment_method_source']))
        {

            if( $params['payment_method_source'] == 1){

                // 合同 1.线上支付宝,2.线下境内（对公支付），3.线下境外（对私支付）
                //'支付方式 1.线上支付宝,2.线下境内（对公支付），3.线下境外（对私支付）'
                // 根据业务线、供应商结算方式判断，线下为合同，线上为网采，数据为实时判断，不存储；

                $query_builder->where_in("ps.source",[1]);
            }else{
                $query_builder->where_in("ps.source",[2]);
            }
        }
        if(isset($params['is_fumigation']) && !empty($params['is_fumigation'])){
            $is_fumigation = $params['is_fumigation'] == 1 ? "=": "!=";
            $query_builder->where("ps.extra_handle {$is_fumigation}", 1);
        }

        if( isset($params['is_thousand']) && !empty($params['is_thousand'])){

            if( $params['is_thousand'] == 1) {
                // 未关联
                $query_builder->where("pd.is_relate_ali", 0);
            }else{
                // 已关联
                $query_builder->where("pd.is_relate_ali", 1);
            }
        }

        if (!empty($params['sku'])) {
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索
                $this->purchase_db->like('ps.sku', $params['sku'], 'both');
            } else {
                $this->purchase_db->where_in('ps.sku', $sku);
            }
        }

        if( isset($params['is_purchasing']) && !empty($params['is_purchasing'])){

            $query_builder->where('pd.is_purchasing',$params['is_purchasing']);
        }

        if(isset($params['purchase_order_status']) ){

            $query_builder->join('purchase_suggest_map as psm', 'psm.demand_number=ps.demand_number', 'left');
            $query_builder->join('purchase_order as po', 'po.purchase_number=psm.purchase_number', 'left');
            if(is_array($params['purchase_order_status'])){
                $query_builder->where_in('po.purchase_order_status',$params['purchase_order_status']);
            }else{
                $query_builder->where('po.purchase_order_status',$params['purchase_order_status']);
            }

            unset($params['purchase_order_status']);
        }

        if(isset($params['transformation']) && !empty($params['transformation'])){

            if( $params['transformation'] == 1){
                $query_builder->where('ps.sku_state_type!=6');
            }else {

                $query_builder->where('ps.sku_state_type', $params['transformation']);
            }
        }

        if( isset($params['is_overseas_first_order']) && $params['is_overseas_first_order'] != NULL ){

            $query_builder->where("ps.is_overseas_first_order",$params['is_overseas_first_order']);
        }
        // 发运类型
        if( isset($params['shipment_type']) && !empty($params['shipment_type'])){

            $query_builder->where("ps.shipment_type",$params['shipment_type']);
        }

        // 预计到货时间
        if( isset($params['estimate_time_start']) && isset($params['estimate_time_end']))
        {
            $query_builder->where('ps.estimate_time>=',$params['estimate_time_start'])->where('ps.estimate_time<=',$params['estimate_time_end']);
        }

        if(isset($params['plan_product_arrive_time_start']) and $params['plan_product_arrive_time_start']){
            $params['plan_product_arrive_time_start'] and $query_builder->where('ps.earliest_exhaust_date >=',$params['plan_product_arrive_time_start']);
            unset($params['plan_product_arrive_time_start']);
        }
        if(isset($params['plan_product_arrive_time_end']) and $params['plan_product_arrive_time_end']){
            $params['plan_product_arrive_time_end'] and $query_builder->where('ps.earliest_exhaust_date<',$params['plan_product_arrive_time_end']);
            unset($params['plan_product_arrive_time_end']);
        }

        if(isset($params['id']) && $params['id']!=""){
            $ids= explode(',', $params['id']);
            $query_builder->where_in('ps.id',$ids);
            unset($params['id']);
        }
        if(isset($params['is_create_order']) && is_numeric($params['is_create_order'])){
            $query_builder->where('ps.is_create_order',(int)$params['is_create_order']);
            unset($params['is_create_order']);
        }
        if(isset($params['is_left_stock']) && is_numeric($params['is_left_stock'])){
            if(intval($params['is_left_stock']) == 1){
                $query_builder->where('ps.left_stock <',0);
            }else{
                $query_builder->where('ps.left_stock >=',0);
            }
            unset($params['is_left_stock']);
        }

        if (isset($params['demand_type_id']) and $params['demand_type_id']){
            $query_builder->where_in('ps.demand_type_id',$params['demand_type_id']);
            unset($params['demand_type_id']);
        }

        if (isset($params['buyer_id']) and $params['buyer_id']){

            if(is_array($params['buyer_id'])){
                $query_builder->where_in('ps.buyer_id', $params['buyer_id']);
            }else{
                $buyers = explode(',', $params['buyer_id']);
                $query_builder->where_in('ps.buyer_id',$buyers);
            }
            unset($params['buyer_id']);
        }
        if (isset($params['product_line_id']) and $params['product_line_id']){
            if(is_array($params['product_line_id'])){
                $query_builder->where_in('ps.product_line_id', $params['product_line_id']);
            }else{
                $query_builder->where('ps.product_line_id', $params['product_line_id']);
            }
            unset($params['product_line_id']);
        }

        if (isset($params['supplier_code']) and $params['supplier_code']){
            $query_builder->where('ps.supplier_code',$params['supplier_code']);
            unset($params['supplier_code']);
        }

        if (isset($params['is_drawback']) and $params['is_drawback']!=''){
            $query_builder->where('ps.is_drawback',$params['is_drawback']);
            unset($params['is_drawback']);
        }
        //产品状态
        if (isset($params['product_status']) and $params['product_status']!=''){
            if(is_array($params['product_status'])){
                $query_builder->where_in('pd.product_status',$params['product_status']);
            }else{
                $query_builder->where('pd.product_status',$params['product_status']);
            }
            unset($params['product_status']);
        }

        if( isset($params['is_ticketed_point']) && !empty($params['is_ticketed_point']))
        {
            if( $params['is_ticketed_point'] == 1)
            {
                $query_builder->where("pd.maintain_ticketed_point",0);
            }else{

                $query_builder->where("pd.maintain_ticketed_point",1);
            }
        }

        if (isset($params['suggest_status']) and $params['suggest_status']){
            if(!is_array($params['suggest_status'])) {
                $query_builder->where('ps.suggest_status', $params['suggest_status']);
            }

            if(is_array($params['suggest_status'])){
                $query_builder->where_in('ps.suggest_status', $params['suggest_status']);
            }
            unset($params['suggest_status']);
        }else{
            //锁单类型
            if (isset($params['lock_type']) and $params['lock_type']) {

            }else{
                //实单锁单列表,除作废,展示所有状态的备货单
                $query_builder->where('ps.suggest_status != ',SUGGEST_STATUS_EXPIRED);
            }
        }

        if (isset($params['demand_number']) and trim($params['demand_number'])){
            $demand_number_arr = array_filter(explode(' ',trim($params['demand_number'])));
            $query_builder->where_in('ps.demand_number',$demand_number_arr);
            unset($params['demand_number']);
        }
        if (isset($params['is_new']) and $params['is_new']!=''){
            if ($params['is_new']==1){
                $query_builder->where('ps.is_new',1);//是新品
            }else{
                $query_builder->where('ps.is_new',0);//不是新品
            }
            unset($params['is_new']);
        }

        if (isset($params['purchase_type_id']) and $params['purchase_type_id']){
            if(is_array($params['purchase_type_id'])){
                $query_builder->where_in('ps.purchase_type_id',$params['purchase_type_id']);
            }else{
                $query_builder->where('ps.purchase_type_id',$params['purchase_type_id']);
            }
            unset($params['purchase_type_id']);
        }

        if (isset($params['destination_warehouse']) and $params['destination_warehouse']){
            $query_builder->where('ps.destination_warehouse',$params['destination_warehouse']);
            unset($params['destination_warehouse']);
        }

        if (isset($params['logistics_type']) and $params['logistics_type']){
            $query_builder->where('ps.logistics_type=binary("'.$params['logistics_type'].'")');
            unset($params['logistics_type']);
        }

        if (isset($params['warehouse_code']) and $params['warehouse_code']){
            if(is_array($params['warehouse_code'])){
                $query_builder->where_in('ps.warehouse_code', $params['warehouse_code']);
            }else{
                $query_builder->where('ps.warehouse_code', $params['warehouse_code']);
            }
            unset($params['warehouse_code']);
        }
        if (isset($params['pertain_wms']) and $params['pertain_wms']){
            if(is_array($params['pertain_wms'])){
                $pertain_wms_list = implode("','",$params['pertain_wms']);
            }else{
                $pertain_wms_list = implode("','",explode(',',$params['pertain_wms']));
            }
            $query_builder->where("ps.warehouse_code IN(SELECT warehouse_code FROM pur_warehouse WHERE pertain_wms IN('{$pertain_wms_list}'))");
            unset($params['pertain_wms']);
        }

        if (isset($params['is_expedited']) and $params['is_expedited']){
            $query_builder->where('ps.is_expedited',$params['is_expedited']);
            unset($params['is_expedited']);
        }

        if (isset($params['create_user_id']) and $params['create_user_id']){
            $query_builder->where('ps.create_user_id',$params['create_user_id']);
            unset($params['create_user_id']);
        }

        if (isset($params['supply_status']) and $params['supply_status']){
            $query_builder->where_in('pd.supply_status',$params['supply_status']);
            unset($params['supply_status']);
        }

        if(isset($params['create_time_start']) and $params['create_time_start']){
            $params['create_time_start'] and $query_builder->where('ps.create_time >=',$params['create_time_start']);
            unset($params['create_time_start']);
        }
        if(isset($params['create_time_end']) and $params['create_time_end']){
            $params['create_time_end'] and $query_builder->where('ps.create_time <=',$params['create_time_end']);
            unset($params['create_time_end']);
        }

        ///缺货数量排序
        if (isset($params['left_stock_order']) && $params['left_stock_order'] && in_array($params['left_stock_order'],['asc','desc'])){
            $query_builder->order_by('ps.left_stock',$params['left_stock_order']);
            unset($params['left_stock_order']);
        }

        //缺货数量(新)
        if( (isset($params['new_lack_qty_start']) && is_numeric($params['new_lack_qty_start'])) || (isset($params['new_lack_qty_end']) && is_numeric($params['new_lack_qty_end'])))
        {
            $query_builder->join('think_lack_info tli','tli.sku = ps.sku', 'left');
            if(isset($params['new_lack_qty_start']) && $params['new_lack_qty_start'] != ''){
                $query_builder->where("tli.lack_sum >=",$params['new_lack_qty_start']);
            }
            if(isset($params['new_lack_qty_end']) && $params['new_lack_qty_end'] != ''){
                $query_builder->where("tli.lack_sum <=",$params['new_lack_qty_end']);
            }
        }

        //供应商排序
        if (isset($params['supplier_order']) && $params['supplier_order'] && in_array($params['supplier_order'],['asc','desc'])){
            $query_builder->order_by('CONVERT(pd.supplier_name USING GBK)',$params['supplier_order']);
            unset($params['supplier_order']);
        }

        //是否精品
        if (isset($params['is_boutique']) and $params['is_boutique']!=''){
            $query_builder->where('ps.is_boutique',$params['is_boutique']);
            unset($params['is_boutique']);
        }

        //开发类型
        if (isset($params['state_type']) && !empty($params['state_type'])){
            if(is_array($params['state_type'])){
                $query_builder->where_in('pd.state_type',$params['state_type']);
            }else{
                $query_builder->where('pd.state_type',$params['state_type']);
            }

            unset($params['state_type']);
        }

        //是否实单锁单
        if (isset($params['entities_lock_status']) and !empty($params['entities_lock_status'])){
            if ($params['entities_lock_status']==2){
                $query_builder->where('ps.lock_type',LOCK_SUGGEST_ENTITIES);
            }else{
                $query_builder->where('ps.lock_type',0);
            }

            unset($params['entities_lock_status']);
        }

        //作废原因
        if (isset($params['cancel_reason']) and !empty($params['cancel_reason'])){
            $query_builder->where_in('ps.cancel_reason_category',$params['cancel_reason']);
            unset($params['cancel_reason']);
        }

        //关联采购单是否已作废
        if (isset($params['connect_order_cancel']) and $params['connect_order_cancel']!=''){
            $query_builder->where('ps.connect_order_cancel',$params['connect_order_cancel']);
            unset($params['connect_order_cancel']);
        }

        if (isset($params['order_by']) and $params['order_by'] and isset($params['order']) and $params['order']){
            switch ($params['order_by']){
                case 1://供应商
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.supplier_name',$params['order']);
                    break;
                case 2://采购员
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.buyer_id',$params['order']);
                    break;
                case 3://产品名称
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.product_name',$params['order']);
                    break;
                case 4://是否退税
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.is_drawback',$params['order']);
                    break;
                case 5://预计到货时间
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.plan_product_arrive_time',$params['order']);
                    break;
                case 6://创建时间
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.id',$params['order']);
                    break;
                case 7://一级产品线
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.product_line_id',$params['order']);
                    break;
                case 8://二级产品线
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.two_product_line_id',$params['order']);
                    break;
                case 9://总金额
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.purchase_total_price',$params['order']);
                    break;
                case 10://审核时间
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.audit_time',$params['order']);
                    break;
                default:
            }

            unset($params['order_by']);
            unset($params['order']);
        }

        unset($params['export']);
        unset($params['order_by']);
        unset($params['order']);

        //锁单类型
        if (isset($params['lock_type']) and $params['lock_type']) {
            //锁单列表页面展示所有审核状态的备货单
        }else{
            $query_builder->where('ps.audit_status',SUGGEST_AUDITED_PASS);
        }

        //锁单类型
        if (isset($params['lock_type']) and $params['lock_type']){
            $query_builder->where('ps.lock_type',$params['lock_type']);
            unset($params['lock_type']);
        }

        $query_builder_count    = clone $query_builder;// 克隆一个查询 用来计数
        $query_builder_sum      = clone $query_builder;// 克隆一个查询 用来做数据汇总  screed.estimate_time as delivery_time_estimate_time,
        $query_builder          = $query_builder->select(" ps.*,pd.supply_status{$scree_join},pd.is_purchasing as tis_purchasing,pd.maintain_ticketed_point,
        pd.ticketed_point,pd.supply_status,pd.state_type,pd.starting_qty,pd.starting_qty_unit,pd.tax_rate,pd.declare_unit,pd.product_status,pd.product_thumb_url");

        $query_builder_tmp      = clone $query_builder;// 克隆一个查询,用来返回查询的 SQL 语句
        //数据汇总
        // $huizong_arr            = $query_builder_sum->select('sum(ps.purchase_amount) as purchase_amount_all, sum(ps.purchase_total_price) as purchase_unit_price_all,count(distinct ps.sku) as sku_all')->get()->row_array();
        $huizong_arr             = array();
        $total_count             = 0;
        $query_sql              = $query_builder_tmp->get_compiled_select();// 获取查询的 SQL
        if($export){//导出查询，不需要传分页
            $results                = $query_builder->get()->result_array();
        }else{//列表查询

            $query_builder->order_by('ps.id','desc');
            $results                = $query_builder->get('',$limit,$offset)->result_array();

        }

        $this->rediss->setData(md5(getActiveUserId().'_purchase_suggest_get_list'),base64_encode($query_sql),1800);// 缓存查询SQL，便于执行其他操作，如1688批量刷新
        $this->session->set_tempdata('purchase_suggest-get_list', $query_sql,500);//设置缓存和有效时间
        //判断改登录用户是否是销售 如果是就屏蔽敏感字段

        if(!empty($results) && !isset($params['exclude_scree'])){

            $skusdata = array_unique(array_column($results,'sku'));
            //( SELECT sku,MAX(estimate_time) as estimate_time FROM pur_product_scree AS screet WHERE screet.status=50 AND apply_remark IN(4,10)
            // GROUP BY sku ORDER BY estimate_time DESC ) as screed

            $screeSkuData = $this->purchase_db->from("product_scree as screet")->where("status",50)->where_in("apply_remark",[4,10])
                ->where_in("sku",$skusdata)->select("sku,MAX(estimate_time) as estimate_time")->group_by("sku")->order_by("estimate_time DESC")
                ->get()->result_array();

            if(!empty($screeSkuData)){

                $screeSkuDatas = array_column($screeSkuData,NULL,"sku");
            }

            foreach($results as $key=>&$value){

                $value['delivery_time_estimate_time'] = isset($screeSkuDatas[$value['sku']])?$screeSkuDatas[$value['sku']]['estimate_time']:'';
            }
        }

        $return_data = [
            'data_list'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'offset'    => (int)$page,
                'limit'     => $limit
            ],
            'aggregate_data'  => $huizong_arr,
        ];

        return $return_data;
    }

    /**
     * 采购需求 添加备注
     * @author Jolon
     * @param $suggest_id
     * @param $remark
     * @return bool
     */
    public function add_sales_note($suggest_id,$remark){
        $suggest = $this->get_one_suggest($suggest_id);
        if(empty($suggest)) return false;
        $old_remark = $suggest['sales_note'];
        $remark     = empty($old_remark) ? $remark.' '.date("Y-m-d H:i:s",time()) : $old_remark."\n".$remark.' '.date("Y-m-d H:i:s",time());

        $result = $this->update_suggest(['sales_note' => $remark],$suggest_id);
        if(empty($result)){
            return false;
        }else{
            operatorLogInsert(
                ['id'      => $suggest['demand_number'],
                    'type'    => $this->table_name,
                    'content' => '添加备注',
                    'detail'  => $remark
                ]);
            return true;
        }
    }

    /**
     * 根据 采购需求ID 获取需求信息
     * @param $suggest_id
     * @return mixed
     */
    public function get_one_suggest($suggest_id){
        $suggest = $this->purchase_db->where('id',$suggest_id)->get($this->table_name)->row_array();
        return $suggest;
    }

    /**
     * 根据 id数组 获取需求信息
     * @param $where
     * @return mixed
     */
    public function get_suggest_by_ids($ids){
        $suggest = $this->purchase_db->where_in('id',$ids)->get($this->table_name)->result_array();
        return $suggest;
    }

    /**
     * 根据 采购需求单号 获取需求信息
     * @param $demand_number
     * @return mixed
     */
    public function get_one_suggest_by_demand_number($demand_number){
        $suggest = $this->purchase_db->where('demand_number',$demand_number)->get($this->table_name)->row_array();
        return $suggest;
    }

    /**
     * 验证过期时间是否一致
     * @param $suggest_list
     * @return mixed
     */
    public function validate_expiration_time($suggest_list){
        if(!empty($suggest_list)){
            foreach($suggest_list as $key => $val){
                $check_expiration_time[mb_substr($val['expiration_time'],0,10,'utf-8')][]=$val;
            }
            if(count($check_expiration_time) ==1){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 验证是否已生成采购单
     */
    public function validate_create_purchase_order($suggest_list){
        if(!empty($suggest_list)){
            $flag=0;
            foreach($suggest_list as $key => $val){
                $row=$this->purchase_db->select('id')->from('purchase_suggest_map')
                    ->where('demand_number',$val['demand_number'])
                    ->where('sku',$val['sku'])
                    ->get()->row_array();
                if($row){
                    $flag++;
                }
            }
            if($flag){
                return false;
            }else{
                return true;
            }
        }
    }

    /**
     * 更新 采购需求信息
     * @param     $update_data
     * @param int $suggest_id
     * @return bool
     */
    public function update_suggest($update_data,$suggest_id = 0){
        $suggest_id = !empty($suggest_id)?$suggest_id:(isset($update_data['id'])?$update_data['id']:0);
        if(empty($suggest_id) or empty($update_data)) return false;

        // 更新数据
        if(is_array($suggest_id)){
            $this->purchase_db->where_in('id', $suggest_id);
        }else{
            $this->purchase_db->where('id', $suggest_id);
        }
        $this->purchase_db->update($this->table_name, $update_data);
        if($this->purchase_db->affected_rows() == -1){
            return false;
        }else{
            if(!is_array($suggest_id)){ $suggest_id = [$suggest_id];}

            // 保存操作记录
            foreach($suggest_id as $id){
                $log_data = [
                    'record_number' => $id,
                    'table_name'    => 'pur_purchase_suggest',
                    'change_type'   => '1',
                    'content'       => $update_data,
                ];
                tableChangeLogInsert($log_data);
            }

            return true;
        }
    }


    /**
     * 自动更新 采购需求的状态
     * @param      $suggest_id
     * @param string $demand_number
     * @return bool
     */
    public function change_status($suggest_id = null,$demand_number = null){
        if($suggest_id)     $suggest = $this->get_one_suggest($suggest_id);
        if($demand_number)  $suggest = $this->get_one_suggest_by_demand_number($demand_number);
        if(empty($suggest)) return false;

        $suggest_id = $suggest['id'];

        $new_status = null;
        $new_is_abnormal = SUGGEST_ABNORMAL_FALSE;
        $demand_purchase_order = $this->purchase_suggest_map_model->get_purchase_order_info($suggest['demand_number']);

        if(!isset($demand_purchase_order['map']) or !isset($demand_purchase_order['purchase_order'])){//没有生成采购单的需求单
            /*if(time() > strtotime($suggest['expiration_time'])){
                $new_status = SUGGEST_STATUS_EXPIRED;// 未生成采购单-设置过期
            }else{*/
            $new_status = SUGGEST_STATUS_REBORN;// 未完结
//            }
        }else{
            $confirm_number = isset($demand_purchase_order['map']['confirm_number'])?$demand_purchase_order['map']['confirm_number']:0;
            if($confirm_number >= $suggest['purchase_amount']){
                $new_status = SUGGEST_STATUS_FINISHED;// 判断采购数量是否 等于 备货数量
            }elseif($confirm_number < $suggest['purchase_amount']){
                //判断采购数量是否 小于 备货数量
                $new_status = SUGGEST_STATUS_FINISHED;// 判断采购数量是否 等于 备货数量
                $new_is_abnormal = SUGGEST_ABNORMAL_TRUE;// 修改备货单异常状态为异常
            }
        }

        if($new_status != $suggest['suggest_status']){// 状态是否变更
            if ($new_status==SUGGEST_STATUS_EXPIRED or $new_status==SUGGEST_STATUS_NOT_FINISH ){
                $update_data = ['suggest_status' => $new_status,'is_create_order'=>0];
            }else{
                $update_data = ['suggest_status' => $new_status,'is_create_order'=>1];
            }
            $result = $this->update_suggest($update_data,$suggest_id);

            // 更新需求单是否已经生成采购单（同备货单）
            $this->purchase_db->where('suggest_demand',$suggest['demand_number'])
                ->update('purchase_demand',['is_create_order' => $update_data['is_create_order']]);

            if($result){
                operatorLogInsert(
                    ['id'      => $suggest['demand_number'],
                        'type'    => $this->table_name,
                        'content' => '修改需求状态',
                        'detail'  => '修改状态，从【'.getSuggestStatus($suggest['suggest_status']).'】改到【'.getSuggestStatus($new_status).'】'
                    ]);
            }else{
                return false;
            }
        }

        if($new_is_abnormal != $suggest['is_abnormal']){//异常状态变更
            $update_abnormal = ['is_abnormal' => $new_is_abnormal];
            $result2 = $this->update_suggest($update_abnormal,$suggest_id);

            if($result2){
                operatorLogInsert(
                    ['id'      => $suggest['demand_number'],
                        'type'    => $this->table_name,
                        'content' => '修改需求单异常状态',
                        'detail'  => '修改需求单异常状态，从【'.$suggest['is_abnormal'].'】改到【'.$new_is_abnormal.'】'
                    ]);
            }else{
                return false;
            }
        }

        return true;
    }

    /**
     * 改变需求单状态
     */
    public function change_suggest_status($suggest_id = null){
        if($suggest_id)     $suggest = $this->get_one_suggest($suggest_id);
        if(empty($suggest)) return false;
        $new_status = SUGGEST_STATUS_FINISHED;
        //从sku产品列表里获取是否代采信息，以便待采购询价时查询
        $product_info = $this->product_model->get_product_info($suggest['sku']);
        $is_purchasing = !empty($product_info['is_purchasing'])? $product_info['is_purchasing']:0;

        if($new_status){
            $supplier_source = $this->purchase_db->from("supplier")->select("supplier_source")->where("supplier_code='".$suggest['supplier_code']."'")->get()->row_array();

            $update_data = ['suggest_status' => $new_status,'is_create_order'=>1,'supplier_source'=>$supplier_source['supplier_source'],'connect_order_cancel'=>1,'is_purchasing'=>$is_purchasing];//关联采购单是否已作废（1.否;2.是）
            $result      = $this->update_suggest($update_data,$suggest_id);

            // 更新需求单是否已经生成采购单（同备货单）
            $this->purchase_db->where('suggest_demand',$suggest['demand_number'])
                ->update('purchase_demand',['is_create_order' => 1]);

            if($result){
                operatorLogInsert(
                    ['id'      => $suggest['demand_number'],
                        'type'    => $this->table_name,
                        'content' => '修改需求状态',
                        'detail'  => '修改状态，从【'.getSuggestStatus($suggest['suggest_status']).'】改到【'.getSuggestStatus($new_status).'】'
                    ]);
            }else{
                return false;
            }
        }
        return true;
    }



    /**
     * 采购需求 - 规则拦截 - 产品信息不全
     * @param $suggest_id
     * @return mixed
     */
    public function suggest_intercept($suggest_id){
        $this->load->model('Supplier_model', '', false, 'product');
        // 产品信息不全  验证默认供应商、报价、采购链接、  退税的验证税点、开票信息、退税率

        $suggest = $this->get_one_suggest($suggest_id);
        $sku_info = $this->product_model->get_product_info($suggest['sku']);

        $result = ['flag' => false,'intercept_note' => []];

        if(empty($sku_info['supplier_code'])){
            $result['flag'] = true;
            $result['intercept_note'][] = '供应商缺失';
        }
        if(empty($sku_info['product_cn_link']) and empty($sku_info['product_en_link'])){
            $result['flag'] = true;
            $result['intercept_note'][] = '链接缺失';
        }
        if($sku_info['is_drawback'] = 1 and empty($sku_info['ticketed_point'])){
            $result['flag'] = true;
            $result['intercept_note'][] = '税点缺失';
        }
        if(empty($sku_info['tax_rate']) or empty($sku_info['declare_cname'])
            or empty($sku_info['declare_unit']) or empty($sku_info['export_declare_model'])){
            $result['flag'] = true;
            $result['intercept_note'][] = '开票信息缺失';
        }

        if($result['flag']){
            $intercept_note = implode(',',$result['intercept_note']);

            $insert_data = [
                'id'      => $suggest_id,
                'type'    => $this->table_name,
                'content' => '采购需求规则拦截',
                'detail'  => '产品信息不全：'.$intercept_note,
            ];
            operatorLogInsert($insert_data);

//            $this->update_suggest(['intercept_status' => 1,'intercept_note' => $intercept_note],$suggest_id);
        }else{
//            $this->update_suggest(['intercept_status' => 0,'intercept_note' => ''],$suggest_id);
        }

        return $result['flag'];
    }

    /**
     * 更新 采购需求 的采购单状态
     * @param   int  $suggest_order_status
     * @param array $suggest_ids
     * @param array $demand_numbers
     * @return bool
     */
    public function update_purchase_order_status($suggest_order_status,$suggest_ids = [],$demand_numbers = []){
        if(empty($suggest_ids) and empty($demand_numbers)) return false;

        $update_data = ['suggest_order_status' => $suggest_order_status];
        // 更新数据
        if($suggest_ids){
            $this->purchase_db->where_in('id', $suggest_ids);
        }else{
            $this->purchase_db->where_in('demand_number', $demand_numbers);
        }
        $res = $this->purchase_db->update($this->table_name, $update_data);
        return $res;
    }


    /**
     * 更新 采购需求的采购员（供应商修改采购员后变更）
     * @author Jolon
     * @param  int     $purchase_type_id
     * @param   string $supplier_code
     * @param    int   $buyer_id
     * @param string   $buyer_name
     * @param array $demand_number_list
     * @return bool
     */
    public function update_suggest_buyer($purchase_type_id,$supplier_code,$buyer_id,$buyer_name = '',array $demand_number_list = []){
        $this->load->helper('status_order');
        if(empty($buyer_name)) $buyer_name = get_buyer_name($buyer_id);
        if(empty($buyer_name) or is_array($buyer_name)) $buyer_name = '';
        $this->purchase_db->where('purchase_type_id',$purchase_type_id)
            ->where('suggest_status',SUGGEST_STATUS_NOT_FINISH)
            ->where('supplier_code',$supplier_code)
            ->update($this->table_name,['buyer_id' => $buyer_id,'buyer_name' => $buyer_name]);

        /* 2019-07-26 只更新未完结的需要单的采购员 Jaden
        if(!empty($demand_number_list)){
           $this->purchase_db->where('purchase_type_id',$purchase_type_id)
            ->where('supplier_code',$supplier_code)
            ->where_in('demand_number',$demand_number_list)
            ->update($this->table_name,['buyer_id' => $buyer_id,'buyer_name' => $buyer_name]);
        }
        */

        operatorLogInsert(
            [
                'id'      => $purchase_type_id.'-'.$supplier_code,
                'type'    => $this->table_name,
                'content' => '变更需求的采购员',
                'detail'  => "采购需求的采购员[$purchase_type_id][$supplier_code][$buyer_id][$buyer_name]",
            ]);

        return true;
    }

    /**
     * 获取计划系统推送到采购系统的备货，并且没有计算过是否锁单的数据（统计）
     * @param
     * @author:luxu
     * @time:2021年1月7号
     **/

    public function planis_lock_demand_count(){

        $where = [
            'source_from' =>1, // 数据来源计划系统
            'is_lock_demand' =>1 // 没有计算过是否锁单数据
        ];

        $count = $this->purchase_db->from("purchase_suggest")->where($where)->count_all_results();
        return $count;
    }

    /**
     * 获取计划系统推送到采购系统的备货，并且没有计算过是否锁单的数据（获取数据）
     * @param
     * @author:luxu
     * @time:2021年1月7号
     **/

    public function get_plan_demand_data($offset,$limit){

        $where = [
            'source_from' =>1, // 数据来源计划系统
            'is_lock_demand' =>1 // 没有计算过是否锁单数据
        ];
        $result = $this->purchase_db->from("purchase_suggest")->select("id")->where($where)->limit($limit,$offset)->get()->result_array();
        return $result;
    }

    /**
     * 更新备货单是否锁单信息
     * @param
     * @author:luxu
     * @time:2021年1月7号
     **/
    public function update_plan_demand_data($ids){

        $this->purchase_db->where_in('id',$ids)->update('purchase_suggest',['is_lock_demand'=>2]);


    }

    /**
     * 获取SKU 作废信息
     * @param  $skus  array  SKU
     * @author:luxu
     * @time:2021年7月12号
     *  //查询该sku的最新一条已作废需求单(如果存在)的作废原因
    $cancel_info = $this->purchase_db->select('cancel_reason')
    ->where('sku',$val['sku'])
    ->where('suggest_status',SUGGEST_STATUS_CANCEL)
    ->order_by('create_time','desc')
    ->get('purchase_suggest')
    ->row_array();

     **/

    private function get_cancel_info($skus){

        $query = $this->purchase_db->from("purchase_suggest")->select('cancel_reason,sku')->where('suggest_status',SUGGEST_STATUS_CANCEL);
        if(count($skus)<2000){

            $query->where_in('sku',$skus);
        }else{
            $skusDatas = array_chunk($skus,10);
            $this->purchase_db->group_start();
            foreach($skusDatas as $skus_value){
                $query->or_where_in("sku",$skus_value);
            }
            $this->purchase_db->group_end();
        }

        $result = $query->order_by('create_time','desc')->get()->result_array();
        if(empty($result)){

            return [];
        }

        return array_column($result,NULL,'sku');
    }

    private function set_datas_logs($logs){

        $ci = get_instance();
        //获取redis配置
        $ci->load->config('mongodb');
        $host = $ci->config->item('mongo_host');
        $port = $ci->config->item('mongo_port');
        $user = $ci->config->item('mongo_user');
        $password = $ci->config->item('mongo_pass');
        $author_db = $ci->config->item('mongo_db');
        $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
        $author_db = $author_db;
        $bulk = new MongoDB\Driver\BulkWrite();
        $mongodb_result = $bulk->insert($logs);
        $result = $mongodb->executeBulkWrite("{$author_db}.receive_logs", $bulk);



    }
    /**
     * 解锁因为SKU 屏蔽恢复货源状态为正常的需求单数据
     * @author:luxu
     * @time:2021年8月26号
     **/
    private function update_is_abnormal_lock(){

        $this->purchase_db->where_in("demand_status",[SUGGEST_STATUS_NOT_FINISH,DEMAND_SKU_STATUS_CONFIR])
            ->where("demand_lock",DEMAND_SKU_STATUS_NO_LOCK)->where("is_abnormal_lock",1)
            ->update('purchase_demand',['is_abnormal_lock'=>0]);
    }



    /**
     * 接收计划系统推送的备货单数据
     */
    public function receive_demand_data($data){
        /*
         *  判断第一次是否存在SKU 屏蔽释放的需求单锁单数据
         *  当计划系统第一次推送数据到采购系统时，设置标识
        */
        $is_abnormal_lock = $this->rediss->getData("is_abnormal_lock");
        if(empty($is_abnormal_lock)){
            // 缓存3个小时
            $this->rediss->setData("is_abnormal_lock","is_abnormal_lock_true",10800);
            $this->update_is_abnormal_lock();
        }

        $error_list = $success_list = null;
        //计划系统物流属性id => 物流属性编码
        $logistics_type_map = [
            1 => 'HYZG',  //海运整柜
            2 => 'HYSH',  //海运散货
            3 => 'TJP2',  //铁运整柜
            4 => 'TJP1',  //铁运散货
            5 => 'LYSH',  //陆运
            6 => 'KJP1',  //空运
            7 => 'KD',  //红单 => 快递
            8 => 'KD',  //蓝单 => 快递
        ];
        //仓库code => name
        $this->load->model('warehouse/warehouse_model');
        $this->load->model('product/Product_line_model');

        $warehouse_map = $this->Warehouse_model->warehouse_code_to_name();
        $mongodbLogs = [];
        if(!empty($data)){
            $receiveSkus = array_column($data,"sku");// 批量获取计划系统推送的SKU
            $isNewData = $isOverseasFirstOrder=[];
            if( !empty($receiveSkus )){
                // 标记时间
                $mongodbLogs['get_product']['start'] = microtime();
                $receiveSkusData = $this->purchase_db->from($this->table_product_name)
                    ->where_in("sku",$receiveSkus)
                    ->select("sku,is_new,is_overseas_first_order,supplier_code,supplier_name,product_line_id")
                    ->get()
                    ->result_array();
                $mongodbLogs['get_product']['end'] = microtime();

                //标记时间
                if( !empty($receiveSkusData)){
                    $isNewData = array_map(function($data){

                        if( $data['is_new'] == 1){
                            return $data['sku'];
                        }
                    },$receiveSkusData);
                    $isNewData = array_filter($isNewData);
                    $isOverseasFirstOrder = array_map(function($data){

                        if( $data['is_overseas_first_order'] == 1){

                            return $data['sku'];
                        }
                    },$receiveSkusData);
                    $isOverseasFirstOrder = array_filter($isOverseasFirstOrder);
                }

            }


            $productMessDatas = array_column($receiveSkusData,NULL,'sku');
            // 标记时间
            $mongodbLogs['get_product_line']['start'] = microtime();

            //加载采购候补人模块

            $this->load->model('system/Product_line_buyer_config_model');

            $swooleIds = $insertData = $push_data =[];
            //处理数据

            $cancel_info_skus = $this->get_cancel_info($receiveSkus);
            $skus_scree_datas = $this->get_scree_estimate_time_datas($receiveSkus);
            //标记时间

            $pro_line_cache = $this->product_line_model->cache_product_line();
            $mongodbLogs['get_product_line']['end'] = microtime();

            //标记时间
            $product_line = SetAndNotEmpty($pro_line_cache, 'line') ?  $pro_line_cache['line']: [];
            $product_line_title = SetAndNotEmpty($pro_line_cache, 'master') ?  $pro_line_cache['master']: [];
            $skuSupplierDatas = array_column($receiveSkusData,'supplier_code');

            /*$supplier_payment_info = $this->purchase_db->select('*')
                ->from('pur_supplier_payment_info')
                ->where_in('supplier_code',$skuSupplierDatas)
                ->where('is_del',0)
                ->get()->result_array();
            $supplier_payment_info_datas = [];
            if(!empty($supplier_payment_info)) {

                foreach ($supplier_payment_info as $key => $item) {
                    //供应商 是否含税 业务线
//                $item['is_tax'] = empty($item['is_tax'])?0:$item['is_tax'];
//                $item['purchase_type_id'] = empty($item['purchase_type_id'])?0:$item['purchase_type_id'];
                    $supplier_payment_info_datas[$item['supplier_code']][$item['is_tax']][$item['purchase_type_id']] = $item;

                }
                //$supplier_payment_info = array_column($supplier_payment_info,NULL,'supplier_code');
            }*/

            // 获取备货单号
            $pur_sn_datas = array_column($data,'pur_sn');
            $is_order_datas=$this->purchase_db->select('demand_number')->from("purchase_demand")->where_in('demand_number',$pur_sn_datas)
                ->get()->result_array();
            $is_order_datas = array_column($is_order_datas,'demand_number');
            // 标记时间
            $mongodbLogs['foreach']['start'] = microtime();

            foreach($data as $key => $val){
                if( !in_array($val['pur_sn'],$is_order_datas)  ){

                    $val['bussiness_line'] = $val['bussiness_line']??'';
                    $bussiness_line = PURCHASE_TYPE_INLAND;
                    //计划系统定义的purchase_type_id 业务线和我们的不一样,需要转换
                    if($val['bussiness_line']==PURCHASE_TYPE_INLAND){//国内
                        $bussiness_line = PURCHASE_TYPE_FBA;
                    }elseif ($val['bussiness_line']==PURCHASE_TYPE_FBA) {//FBA
                        $bussiness_line = PURCHASE_TYPE_INLAND;
                    }elseif ($val['bussiness_line'] == PURCHASE_TYPE_OVERSEA){ //海外仓
                        $bussiness_line = PURCHASE_TYPE_OVERSEA;
                        if(!isset($val['destination_warehouse']) || empty($val['destination_warehouse'])){//目的仓不能为空
                            $error_list[$val['pur_sn']] = '目的仓为空';
                            continue;
                        }elseif(!isset($warehouse_map[$val['destination_warehouse']])){
                            $error_list[$val['pur_sn']] = '目的仓匹配不到仓库信息';
                            continue;
                        }
                    }elseif ($val['bussiness_line']==PURCHASE_TYPE_PFB) {//PFB
                        $bussiness_line = PURCHASE_TYPE_PFB;
                    }elseif ($val['bussiness_line']==PURCHASE_TYPE_FBA_BIG) {// FBA大货
                        $bussiness_line = PURCHASE_TYPE_FBA_BIG;
                    }elseif ($val['bussiness_line']==PURCHASE_TYPE_PFH) {//PFH
                        $bussiness_line = PURCHASE_TYPE_PFH;
                    }else{
                        $error_list[$val['pur_sn']] = '业务线异常';
                        continue;
                    }

                    $purchase_type_id = $bussiness_line;// 备货单业务线
                    if($bussiness_line == PURCHASE_TYPE_PFB){
                        $bussiness_line = PURCHASE_TYPE_INLAND;// 查找数据时 根据国内仓的去找采购员
                    }elseif($bussiness_line == PURCHASE_TYPE_PFH){
                        $bussiness_line = PURCHASE_TYPE_FBA;// 查找数据时 根据FBA的去找采购员
                    }
                    $demand_repeat = $this->judge_sku_repeat($val['sku'],$purchase_type_id,$val['pur_sn']);

                    $sku_repeatflag = 1; // 标记为重复
                    if($demand_repeat == "no_repetition"){
                        $sku_repeatflag =2; // 标记为重复
                    }

                    $add_data=[
                        'sku' => $val['sku'],
                        'demand_repeat' => $sku_repeatflag,
                        'demand_number' => $val['pur_sn'],//备货单号
                        'purchase_type_id' => $purchase_type_id,//业务线
                        'demand_data' => $val['purchase_qty']??'',//需求单数量
                        'demand_status' => 1,//备货单状态 默认未完结
                        'earliest_exhaust_date' => empty($val['earliest_exhaust_date'])?'':date('Y-m-d H:i:s',(int)substr($val['earliest_exhaust_date'], 0, -3)),//最早缺货时间(预计断货时间)
                        'expiration_time' => empty($val['expiration_time'])?'':date('Y-m-d H:i:s',(int)substr($val['expiration_time'], 0, -3)),//过期时间
                        'warehouse_code' => $val['warehouse_code']??'',//采购仓库
                        'warehouse_name' => $warehouse_map[$val['warehouse_code']??'']??'',//采购仓库名称
                        'gid' => $val['gid']??'',//计划系统数据唯一标识
                        'plan_product_arrive_time' => empty($val['plan_product_arrive_time'])?'':date('Y-m-d H:i:s',(int)substr($val['plan_product_arrive_time'], 0, -3)),//预计到货时间
                        //'sale_state' => $val['sale_state']??'',//计划系统的sku状态
                        'create_time' => date("Y-m-d H:i:s",time()),
                        'source_from' => 1,//数据来源计划系统
                        'create_user_name' => '计划系统',
                        'destination_warehouse' => $val['destination_warehouse']??'',//目的仓
                        'logistics_type' => $logistics_type_map[$val['logistics_type']??'']??'',//物流类型
                        'is_drawback' => !empty($val['is_drawback']) && $val['is_drawback'] == 1 ? 1 : 0,//plan:1退税 2不退税 pur:1退税 0不退税,所有业务线都以计划系统推送过来是否退税为准
                        'country' => $val['country']??'',//目的国
                        'shipment_type' => $val['shipment_type']??'',//发运类型,
                        'is_new' => in_array($val['sku'],$isNewData)?1:0, // 备货单是否为新品
                        'is_overseas_first_order' => in_array($val['sku'],$isOverseasFirstOrder)?1:0, // 是否海外仓首单
                        'is_expedited' => $val['is_expedited']??2,//是否加急
                        'es_shipment_time' => empty($val['es_shipment_time'])?'':date('Y-m-d H:i:s',(int)substr($val['es_shipment_time'], 0, -3)),//预计发货时间
                        'site' => $val['site']??'',//站点code(地域仓)
                        'site_name' => $val['site_name']??'',//站点name(地域仓)
                        'fba_purchase_qty' => $val['fba_purchase_qty']??'0',//FBA备货数量
                        'inland_purchase_qty' => $val['inland_purchase_qty']??'0',//国内备货数量
                        'pfh_purchase_qty' => $val['pfh_purchase_qty']??'0',//平台头程备货数量
                        'demand_name' => isset($val['demand_name'])?$val['demand_name']:'', // 需求类型
                        'demand_name_id' => isset($val['demand_name_id'])?$val['demand_name_id']:0, // 需求单类型ID
                        'is_overseas_boutique' => isset($val['is_overseas_boutique'])?$val['is_overseas_boutique']:0, // 是否海外精品
                        'is_distribution' =>isset($val['is_distribution'])?$val['is_distribution']:1,
                    ];

                    if(($add_data['shipment_type'] == '' || empty($add_data['shipment_type'])) && $add_data['demand_name_id'] == 2){

                        $add_data['shipment_type'] = 1;
                    }

                    if(empty($add_data['shipment_type']) || $add_data['shipment_type'] == ''){

                        $add_data['shipment_type'] = 2;
                    }

                    //TODO 标记时间
                    $mongodbLogs['product_buyer']['start'] = microtime();


                    $sku_info=$this->purchase_db->select('a.state_type AS sku_state_type,a.ticketed_point,a.product_img_url,a.product_name,a.product_line_id,a.supplier_code,a.supplier_name,
                                    a.purchase_price,a.create_id,a.create_user_name,a.is_drawback,b.is_cross_border,c.buyer_id,c.buyer_name,
                                    d.linelist_cn_name')->from('product a')
                        ->join('supplier b','a.supplier_code=b.supplier_code','left')
                        ->join('supplier_buyer c','b.supplier_code=c.supplier_code and c.status=1 and c.buyer_type="'.$bussiness_line.'"', 'left')
                        ->join('product_line d','a.product_line_id=d.product_line_id','left')
                        ->where(['a.sku'=>$val['sku']])
                        ->get()->row_array();
                    // TODO 标记时间
                    $mongodbLogs['product_buyer']['end'] = microtime();


                    $add_data['supplier_name'] = isset($productMessDatas[$add_data['sku']]['supplier_name'])?$productMessDatas[$add_data['sku']]['supplier_name']:'';
                    $add_data['supplier_code'] = isset($productMessDatas[$add_data['sku']]['supplier_code'])?$productMessDatas[$add_data['sku']]['supplier_code']:'';
                    if(!empty($sku_info)){

                        if($bussiness_line == 1){ // 国内仓都是不含税单价
                            $purchase_unit_price = format_two_point_price($sku_info['purchase_price']);// 含税单价（实际未税单价）
                            $purchase_name = 'HKYB';
                        }else{

                            if($add_data['is_drawback'] == 1){
                                $purchase_unit_price = format_two_point_price($sku_info['purchase_price'] * ( 1 + $sku_info['ticketed_point']/100));// 含税单价（实际含税单价）
                                $purchase_name = 'SZYB';
                            }else{
                                $purchase_unit_price = format_two_point_price($sku_info['purchase_price']);// 含税单价（实际未税单价）
                                $purchase_name = 'HKYB';
                            }
                        }

                        // $line_info=$this->get_one_level_product_line($sku_info['product_line_id']);
                        //print_r($sku_info);die();
                        if(!isset($sku_info['product_line_id']) || empty($sku_info['product_line_id'])){


                            $sku_info['product_line_id'] = isset($productMessDatas[$add_data['sku']]['product_line_id'])?$productMessDatas[$add_data['sku']]['product_line_id']:'';
                        }
                        //$line_info=$this->get_product_line_by_id($sku_info['product_line_id']);

                        $add_data['product_img_url']=!empty($sku_info['product_img_url']) ? $sku_info['product_img_url'] : '';
                        $add_data['product_name']=!empty($sku_info['product_name']) ? $sku_info['product_name'] : '';
                        $add_data['two_product_line_id']=!empty($sku_info['product_line_id']) ? $sku_info['product_line_id'] : '';
                        $add_data['two_product_line_name']=!empty($sku_info['linelist_cn_name']) ? $sku_info['linelist_cn_name'] : '0';
                        $add_data['product_line_id']=isset($product_line[$sku_info['product_line_id']]) ? $product_line[$sku_info['product_line_id']] : 0;
                        $add_data['product_line_name']= isset($product_line_title[$sku_info['product_line_id']]) ? $product_line_title[$sku_info['product_line_id']] : '';
                        //$add_data['supplier_code']=!empty($sku_info['supplier_code'])? $sku_info['supplier_code'] : '';
                        //$add_data['supplier_name']=!empty($sku_info['supplier_name']) ? $sku_info['supplier_name'] : '';
                        $add_data['purchase_unit_price']=$purchase_unit_price;
                        $add_data['purchase_total_price']=format_two_point_price($add_data['purchase_unit_price']*$val['purchase_qty']);
                        $add_data['developer_id']=!empty($sku_info['create_id']) ? $sku_info['create_id'] : 0;
                        $add_data['developer_name']=!empty($sku_info['create_user_name']) ? $sku_info['create_user_name'] : '';
                        if (empty($sku_info['buyer_id'])) {
                            //获取采购候补人数据
                            $buyer_info = $this->Product_line_buyer_config_model->get_buyer($add_data['product_line_id'], $add_data['purchase_type_id']);
                            $add_data['buyer_id'] = $buyer_info['buyer_id'];
                            $add_data['buyer_name'] = $buyer_info['buyer_name'];
                        } else {
                            $add_data['buyer_id'] = $sku_info['buyer_id'];
                            $add_data['buyer_name'] = $sku_info['buyer_name'];
                        }
                        $add_data['is_cross_border']=!empty($sku_info['is_cross_border']) ? $sku_info['is_cross_border'] : 0;
                        $add_data['transformation'] = $sku_info['sku_state_type'];
                        $add_data['cancel_reason'] = isset($cancel_info_skus[$val['sku']]['cancel_reason'])?$cancel_info_skus[$val['sku']]['cancel_reason']:'';
                        //$skus_scree_datas
                        $add_data['estimate_time'] = isset($skus_scree_datas[$val['sku']]['estimate_time'])?$skus_scree_datas[$val['sku']]['estimate_time']:'0000-00-00 00:00:00';
                    }

                    // 是否熏蒸
                    $add_data['extra_handle'] = 0;
                    if(isset($val['is_fumigation']) && $val['is_fumigation'] == 1){
                        $add_data['extra_handle'] = 2;
                    }elseif(isset($val['is_fumigation']) && $val['is_fumigation'] == 2){
                        $add_data['extra_handle'] = 1;
                    }

                    // FBA大货必填
                    if($bussiness_line == PURCHASE_TYPE_FBA_BIG){
                        $FBA_BIG_notnull = ["purchase_type_id", "sku", "purchase_amount", "is_expedited", "is_drawback", "warehouse_code", "logistics_type"];
                        $fb_err = false;
                        foreach ($FBA_BIG_notnull as $val_fb){
                            if(!isset($add_data[$val_fb]) || $add_data[$val_fb] == '')$fb_err = $val['pur_sn'];
                        }
                        if($fb_err){
                            $error_list[$fb_err] = $fb_err." 必填信息为空！";
                            continue;
                        }
                    }

                    if( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA/国内 一样
                        $purchase_type_id = 1;
                    }
                    if($add_data['is_drawback'] == 1){//否退税 对应所有的业务线
                        $purchase_type_id = 0;
                    }

                    $supplier_info = $this->supplier_model->get_supplier_info( $add_data['supplier_code']); // 供应商信息
                    $supplier_payment_info = $supplier_info['supplier_payment_info'][$add_data['is_drawback']][$purchase_type_id]??[];

                    // 如果支付方式为线上境内或者境外支付，采购来源为合同单

                    if(!empty($supplier_payment_info['payment_method']) && in_array($supplier_payment_info['payment_method'],[PURCHASE_PAY_TYPE_PUBLIC,PURCHASE_PAY_TYPE_PRIVATE])){

                        $add_data['source'] = SOURCE_COMPACT_ORDER;
                    }else{
                        $add_data['source'] = SOURCE_NETWORK_ORDER;
                    }
                    if(!$this->purchase_db->insert("purchase_demand",$add_data)){
                        $error_list[$val['pur_sn']] = '插入失败';
                    }else{

                        $success_list[$val['pur_sn']] = '插入成功';
                    }
                }else{
                    $success_list[$val['pur_sn']] = '数据已存在';
                }
            }
            // TODO
            $mongodbLogs['foreach']['end'] = microtime();
            $this->set_datas_logs($mongodbLogs);



        }
        return ['error_list' => $error_list,'success_list' => $success_list];
    }


    /**
     * 接收老采购系统推送的备货单数据
     * @param $data
     * @return array
     */
    public function receive_demand_data_v2($data){
        $this->load->model('warehouse/Warehouse_model');
        //加载采购候补人模块
        $this->load->model('system/Product_line_buyer_config_model');

        $now_time = date('Y-m-d H:i:s');
        $error_list     = [];
        $add_data_list  = [];
        $return = ['code' => true,'error_list','message' => ''];
        if(!empty($data)){
            $warehouse_list_tmp = $this->Warehouse_model->get_warehouse_list();
            $warehouse_list = array_column($warehouse_list_tmp,'warehouse_name','warehouse_code');
            $pertain_wms_list = array_column($warehouse_list_tmp,'pertain_wms','warehouse_code');

            $this->purchase_db->trans_begin();
            try{
                foreach($data as $key => $val){
                    $id = isset($val['id'])?$val['id']:null;
                    if(empty($id) or  intval($id) <= 0){
                        $error_list[] = 'id缺失';
                        continue;
                    }
                    $is_order = $this->purchase_db
                        ->select('id')
                        ->from($this->table_name)
                        ->where("gid='{$id}'")
                        ->get()
                        ->row_array();
                    if($is_order){
                        continue;
                    }
                    $is_new = isset($val['is_new'])?$val['is_new']:0;
                    $sale_state = 1;
                    if($is_new){
                        $sale_state = 4;// 新品
                    }

                    $earliest_exhaust_date = isset($val['earliest_exhaust_date'])?$val['earliest_exhaust_date']:0;
                    if($earliest_exhaust_date){
                        $earliest_exhaust_date = date('Y-m-d H:i:s',strtotime("+{$earliest_exhaust_date} days"));
                    }else{
                        $earliest_exhaust_date = '0000-00-00 00:00:00';
                    }

                    $warehouse_code = isset($val['warehouse_code'])?$val['warehouse_code']:null;

                    $add_data = [
                        'gid'                      => $id,
                        'sku'                      => isset($val['sku'])?$val['sku']:null,
                        'purchase_type_id'         => isset($val['purchase_type_id'])?$val['purchase_type_id']:null,
                        'earliest_exhaust_date'    => $earliest_exhaust_date,// 最早缺货时间(预计断货时间)
                        'expiration_time'          => date('Y-m-d').' 23:59:59',// 晚上过期
                        'left_stock'               => isset($val['left_stock'])?$val['left_stock']:0,
                        'warehouse_code'           => $warehouse_code,//'' 定时任务调接口更新
                        'warehouse_name'           => $warehouse_code?(isset($warehouse_list[$warehouse_code])?$warehouse_list[$warehouse_code]:''):'',//'',定时任务调接口更新
                        'plan_product_arrive_time' => '0000-00-00 00:00:00',
                        'sale_state'               => $sale_state,// 计划系统推送sku状态(非fba才有值)1.在售品;2.下架品;3.清仓品;4.新产品
                        'purchase_amount'          => isset($val['purchase_amount'])?$val['purchase_amount']:null,
                        'pertain_wms'              => $warehouse_code?(isset($pertain_wms_list[$warehouse_code])?$pertain_wms_list[$warehouse_code]:''):'',
                    ];

                    // 是否熏蒸
                    $add_data['extra_handle'] = 0;
                    if(isset($val['is_fumigation']) && $val['is_fumigation'] == 1){
                        $add_data['extra_handle'] = 2;
                    }elseif(isset($val['is_fumigation']) && $val['is_fumigation'] == 2){
                        $add_data['extra_handle'] = 1;
                    }

                    foreach($add_data as $ck_key => $ck_vvv){
                        if(is_null($ck_vvv)){
                            $error_list[$id] = "参数[$ck_key]缺失";
                            continue 2;
                        }
                        if ( $ck_key=='warehouse_code' && ( $ck_vvv==''||$ck_vvv=='0' ) ){
                            $error_list[$id] = "参数[$ck_key]错误";
                            continue 2;
                        }
                    }
                    $buyer_type = $add_data['purchase_type_id'];
                    if($val['purchase_type_id'] == PURCHASE_TYPE_FBA_BIG){
                        $buyer_type = PURCHASE_TYPE_OVERSEA;
                    }

                    $sku_info = $this->purchase_db->select('a.product_img_url,a.product_name,a.product_line_id,a.supplier_code,a.supplier_name,
                                a.purchase_price,a.create_id,a.create_user_name,a.is_drawback,b.is_cross_border,b.supplier_settlement,c.buyer_id,c.buyer_name,
                                d.linelist_cn_name')->from('product a')
                        ->join('supplier b', 'a.supplier_code=b.supplier_code', 'left')
                        ->join('supplier_buyer c', 'b.supplier_code=c.supplier_code and c.status=1 and c.buyer_type="'.$buyer_type.'"', 'left')
                        ->join('product_line d', 'a.product_line_id=d.product_line_id', 'left')
                        ->where(['a.sku' => $val['sku']])
                        ->get()
                        ->row_array();

                    //查询该sku的最新一条已作废需求单(如果存在)的作废原因
                    $cancel_info = $this->purchase_db->select('cancel_reason')
                        ->where('sku',$val['sku'])
                        ->where('suggest_status',SUGGEST_STATUS_CANCEL)
                        ->order_by('id','desc')
                        ->get('purchase_suggest')
                        ->row_array();

                    if(empty($sku_info)){
                        $error_list[$id] = 'SKU信息缺失';
                        continue;
                    }

                    /*//判断是否需要非实单锁单
                    $need_lock_res = $this->is_need_lock($val['sku'],$sku_info['supplier_code']);

                    if ($need_lock_res){
                        $add_data['lock_type'] = LOCK_SUGGEST_NOT_ENTITIES;//非实单锁单
                        $add_data['is_locked'] = 1;//是否非实单锁单过
                    }else{
                        $add_data['lock_type'] = 0;
                        $add_data['is_locked'] = 0;
                    }*/

//                    $line_info                         = $this->get_one_level_product_line($sku_info['product_line_id']);
                    $line_info                         = $this->get_product_line_by_id($sku_info['product_line_id']);
                    $add_data['suggest_status']        = SUGGEST_STATUS_NOT_FINISH;
                    $add_data['create_time']           = date("Y-m-d H:i:s", time());
                    $add_data['product_img_url']       = !empty($sku_info['product_img_url']) ? $sku_info['product_img_url'] : '';
                    $add_data['product_name']          = !empty($sku_info['product_name']) ? $sku_info['product_name'] : '';
                    $add_data['two_product_line_id']   = !empty($sku_info['product_line_id']) ? $sku_info['product_line_id'] : '';
                    $add_data['two_product_line_name'] = !empty($sku_info['linelist_cn_name']) ? $sku_info['linelist_cn_name'] : '0';
                    $add_data['product_line_id']       = $line_info['id'];
                    $add_data['product_line_name']     = $line_info['title'];
                    $add_data['supplier_code']         = !empty($sku_info['supplier_code']) ? $sku_info['supplier_code'] : '';
                    $add_data['supplier_name']         = !empty($sku_info['supplier_name']) ? $sku_info['supplier_name'] : '';
                    $add_data['purchase_unit_price']   = format_two_point_price(!empty($sku_info['purchase_price']) ? $sku_info['purchase_price'] : 0.000);
                    $add_data['purchase_total_price']  = format_two_point_price($add_data['purchase_unit_price'] * $add_data['purchase_amount']);
                    $add_data['developer_id']          = !empty($sku_info['create_id']) ? $sku_info['create_id'] : 0;
                    $add_data['developer_name']        = !empty($sku_info['create_user_name']) ? $sku_info['create_user_name'] : '';
                    if (empty($sku_info['buyer_id'])) {
                        //获取采购候补人数据
                        $buyer_info = $this->Product_line_buyer_config_model->get_buyer($line_info['id'], $add_data['purchase_type_id']);
                        $add_data['buyer_id'] = $buyer_info['buyer_id'];
                        $add_data['buyer_name'] = $buyer_info['buyer_name'];
                    } else {
                        $add_data['buyer_id'] = $sku_info['buyer_id'];
                        $add_data['buyer_name'] = $sku_info['buyer_name'];
                    }
                    $add_data['is_cross_border']       = !empty($sku_info['is_cross_border']) ? $sku_info['is_cross_border'] : 0;
                    $add_data['account_type']          = !empty($sku_info['supplier_settlement']) ? $sku_info['supplier_settlement'] : 0;//结算方式
                    $add_data['is_drawback']           = 0;//国内仓都默认不退税  !empty($sku_info['is_drawback']) ? $sku_info['is_drawback'] : 0;
                    $add_data['purchase_name']         = 'HKYB';
                    $add_data['cancel_reason']         = !empty($cancel_info)?$cancel_info['cancel_reason']:'';
                    $add_data['audit_status']          = 1;
//                    $add_data['audit_time']            = $add_data['create_time'];
                    $add_data['is_mrp']                = 1;
                    $add_data['sales_note'] = $this->get_sales_note($val['sku']);//根据sku查询七天内最近的备注,在生成备货单时添加备注

                    $add_data_list[] = $add_data;

                    if (empty($add_data['sales_note'])){
                        $delete_res = $this->delete_sales_note_and_cancel_reason($val['sku']);
                        if (empty($delete_res)) throw new Exception($val['sku'].'清空备注失败');
                    }
                }
                if($add_data_list){
                    foreach($add_data_list as &$value){
                        $value['demand_number'] = get_prefix_new_number('RD');
                    }
                    $res = $this->purchase_db->insert_batch($this->table_name,$add_data_list);
                    if($res){
                        $this->purchase_db->trans_commit();
                    }else{
                        throw new Exception('数据插入失败');
                    }
                }

                $return['error_list'] = $error_list;

            }catch(Exception $e){
                $return['code'] = false;
                $return['message'] = $e->getMessage();
                $this->purchase_db->trans_rollback();
            }

            return $return;
        }else{
            $return['code'] = false;
            $return['message'] = '数据为空';
            return $return;
        }

    }

    /**
     * 根据产品线ID查询一级产品线
     * @author yefanli
     */
    public function get_product_line_by_id($id)
    {
        if($id == 0)return ["id"=> 0, "title"=> '', "pid"=> 0,];
        $this->load->model('product/product_line_model');
        return $this->product_line_model->get_product_line_by_id([], $id);
    }


    /**
     * 根据产品线ID查询一级产品线
     * @author Jaxton
     * @param  int     $id
     * @return array
     */
    public function get_one_level_product_line($id){
        $this->load->model('product/product_line_model');
        $linest = $this->product_line_model->get_all_parent_category($id);
        if(!empty($linest)){
            return [
                'product_line_id'    => $linest[0]['product_line_id'],
                'linelist_cn_name'   => $linest[0]['product_line_name'],
            ];
        }
        return [
            'id'                 => 0,
            'product_line_id'    => 0,
            'linelist_parent_id' => 0,
            'linelist_cn_name'   => '',
            'linelist_level'     => '',
            'create_time'        => '0000-00-00 00:00:00'
        ];
    }

    /**
     * @desc 验证需求单状态是否过期
     * @author Jeff
     * @Date 2019/03/23 15:00
     * @param $suggest_list
     * @return boolean
     */
    public function validate_suggest_status($suggest_list)
    {
        if(!empty($suggest_list)){
            foreach($suggest_list as $key => $val){
                if($val['suggest_status'] == SUGGEST_STATUS_EXPIRED){
                    return $val;
                }
            }
            return;
        }
    }

    /**
     * 生成采购单显示页面
     * @author Jeff
     * @param array $ids
     * @return string|array
     */
    public function get_preview_suggest_data($ids)
    {
        $return = ['data'=>false,'msg'=>''];

        $query_builder = $this->db->where_in('ps.id',$ids);
        $query_builder = $query_builder->from('purchase_suggest as ps');
        $query_builder = $query_builder->join('product as pd','pd.sku=ps.sku','left');
        $query_builder = $query_builder->select('ps.id,ps.product_img_url,ps.suggest_status,ps.supplier_code,ps.supplier_name,ps.is_include_tax,ps.purchase_type_id,
        ps.warehouse_code,ps.expiration_time,ps.demand_number,ps.sku,ps.product_line_name,ps.buyer_name,ps.purchase_type_id,ps.logistics_type,ps.destination_warehouse,
        ps.two_product_line_name,ps.product_name,ps.purchase_amount,ps.is_drawback,ps.purchase_unit_price,ps.warehouse_name,
        pd.ticketed_point,ps.earliest_exhaust_date,ps.sales_note,ps.source_from');

        $suggest_list = $query_builder->get()->result_array();

        if(empty($suggest_list)){
            $return['msg'] = '未获取到符合要求的数据';
            return $return;
        }
        $validate_create_purchase_order = $this->purchase_suggest_model->validate_create_purchase_order($suggest_list);
        if(!$validate_create_purchase_order){
            $return['msg'] = '存在已生成采购单，请刷新后重新选择';
            return $return;
        }

        $this->load->model('ware/Warehouse_model'); // 仓库信息
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $pertain_wms_list = array_column($warehouse_list,'pertain_wms','warehouse_code');
        foreach ($suggest_list as &$suggest) {
            if(in_array($suggest['purchase_type_id'],[PURCHASE_TYPE_OVERSEA]) && $suggest['source_from']!=1 && empty($suggest['destination_warehouse'])){
                $return['msg'] = '备货单业务线为海外需求单号'.$suggest['demand_number']."目的仓库不能为空";
                return $return;
            }
            if(in_array($suggest['purchase_type_id'],[PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG]) && $suggest['source_from']!=1 && empty($suggest['logistics_type'])){
                $return['msg'] = '备货单业务线为海外或FBA大货需求单号'.$suggest['demand_number']."物流类型不能为空";
                return $return;
            }

            if(!isset($pertain_wms_list[$suggest['warehouse_code']]) or empty($pertain_wms_list[$suggest['warehouse_code']])){
                $return['msg'] = 'SKU：'.$suggest['sku'].' 仓库：'.$suggest['warehouse_code']." 的公共仓不能为空";
                return $return;
            }else{
                $suggest['pertain_wms'] = $pertain_wms_list[$suggest['warehouse_code']];
            }
        }
        $pertain_wms_list = array_unique(array_column($suggest_list,'pertain_wms'));
        if(count($pertain_wms_list) != 1){
            $return['msg'] = '备货单的公共仓不一致';
            return $return;
        }

        //判断是否是相同的业务线
        $purchase_type_id = array_unique(array_column($suggest_list,'purchase_type_id'));
        if(count($purchase_type_id) == 1){
            // 什么也不做
        }elseif(count($purchase_type_id) >= 2 and count($purchase_type_id) <= 4){
            if(!empty(array_diff($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]))){
                $return['msg'] = '请选择同一备货单业务线的备货单或国内和FBA的备货单';
                return $return;
            }
        }else{
            $return['msg'] = '请选择同一备货单业务线的备货单或国内和FBA的备货单';
            return $return;
        }


        if ($purchase_type_id[0] == PURCHASE_TYPE_OVERSEA) { //如果是海外线  就要判断过去时间
            $validate_suggest_status = $this->validate_suggest_status($suggest_list);
            if (!empty($validate_suggest_status)) {
                $return['msg'] = '备货单号[' . $validate_suggest_status['demand_number'] . ']已过期';
                return $return;
            }
            $check_expiration_time=$this->validate_expiration_time($suggest_list);
            if(!$check_expiration_time){
                $return['msg'] = '过期时间需要一致';
                return $return;
            }
//            $check_destination_warehouse = array_unique(array_column($suggest_list, 'destination_warehouse'));
            $check_source_from = array_unique(array_column($suggest_list, 'source_from'));
//            if (count($check_destination_warehouse) != 1) {
//                $return['msg'] = '目的仓不一致';
//                return $return;
//            }
//
            if (count($check_source_from) != 1) {
                $return['msg'] = '需求单来源不一致';
                return $return;
            }
//            if($check_source_from[0]!=1 && empty($check_destination_warehouse[0])){
//                 $return['msg'] = '目的仓不能空';
//                 return $return;
//            }

            $check_warehouse_code = array_unique(array_column($suggest_list, 'warehouse_code'));
            if(count($check_warehouse_code) != 1){
                $return['msg'] = '海外仓采购仓库不一致';
                return $return;
            }
        }

        $check_supplier_code = array_unique(array_column($suggest_list,'supplier_code'));
        if(count($check_supplier_code) !=1){
            $return['msg'] = '请选择同一供应商';
            return $return;
        }

        $supplier_code = current($check_supplier_code);
        $reject_info = $this->purchase_order_model->temporary_supplier_order_number($supplier_code);
        if($reject_info){
            $return['msg'] = implode(',',$reject_info);
            return $return;
        }

        $check_is_include_tax = array_unique(array_column($suggest_list,'is_include_tax'));
        if(count($check_is_include_tax) !=1){
            $return['msg'] = '需要全部都为退税或不退税';
            return $return;
        }

        $purchase_amount_num      = 0;//当前页PCS数(采购数量)
        $purchase_total_price_all = 0.00;//当前页订单总金额

        $aggregate_data['page_purchase_type'] = getPurchaseType($suggest_list[0]['purchase_type_id']);//业务线
        $aggregate_data['page_supplier_name']    = $suggest_list[0]['supplier_name'];//供应商
        $aggregate_data['page_buyer_name']       = $suggest_list[0]['buyer_name'];//采购
        $aggregate_data['page_warehouse_name']   = $suggest_list[0]['warehouse_name'];//仓库
        $aggregate_data['page_expiration_time']  = $suggest_list[0]['expiration_time'];//过期时间

        foreach($suggest_list as $value) {
            $value_tmp = [];

            $value_tmp['id']                    = $value['id'];
            $value_tmp['product_img_url']       = $value['product_img_url'];
            $value_tmp['demand_number']         = $value['demand_number'];
            $value_tmp['sku']                   = $value['sku'];
            $value_tmp['product_line_name']     = $value['product_line_name'];
            $value_tmp['two_product_line_name'] = $value['two_product_line_name'];
            $value_tmp['product_name']          = $value['product_name'];
            $value_tmp['purchase_amount']       = $value['purchase_amount'];
            $value_tmp['is_drawback']           = getIsDrawback($value['is_drawback']);
            $value_tmp['ticketed_point']        = $value['ticketed_point'];//开票点

            //根据是否退税来计算单价
            /*if ($value['is_drawback']==1){
                $value_tmp['purchase_unit_price']  = $value['purchase_unit_price'];
            }else{
                $value_tmp['purchase_unit_price']  = $value['purchase_unit_price']*(1+($value['ticketed_point']/100));
            }*/
            $value_tmp['purchase_unit_price'] = format_two_point_price($value['purchase_unit_price']);
            $value_tmp['purchase_total_price'] = format_two_point_price($value_tmp['purchase_unit_price'] * $value_tmp['purchase_amount']);
            $value_tmp['earliest_exhaust_date'] = $value['earliest_exhaust_date'];//预计断货时间
            $value_tmp['sales_note']           = $value['sales_note'];

            $data_list_tmp[] = $value_tmp;

            $purchase_amount_num      += $value_tmp['purchase_amount'];
            $purchase_total_price_all += format_two_point_price($value_tmp['purchase_total_price']);

        }

        $data_list = $data_list_tmp;
        unset($data_list_tmp);

        //表头
        $key_arr = ['图片','备货单号','SKU','一级产品线','二级产品线','产品名称','备货数量',
            '是否退税','开票点','单价','总金额','预计断货时间','备注'];

        //增加汇总信息
        $skus     = array_column($suggest_list, 'sku');
        $skus_num = count(array_unique($skus));//当前页SKU数量

        //汇总数据
        $aggregate_data['page_sku'] = $skus_num;
        $aggregate_data['page_purchase_amount'] =  $purchase_amount_num;
        $aggregate_data['page_purchase_total_price'] = format_two_point_price(sprintf("%.3f",$purchase_total_price_all));

        //汇总表头
        $aggregate_key = ['业务线','供应商','采购员','采购仓库','过期时间','sku总数','备货数量','合计金额'];

        //组合数据
        $return_data['values'] = $data_list;
        $return_data['keys'] = $key_arr;
        $return_data['aggregate_data']=$aggregate_data;
        $return_data['aggregate_key']=$aggregate_key;

        $return['data'] = $return_data;

        return $return;
    }

    /**
     * 修改需求单表的结算方式 pur_purchase_demand
     **/
    public function change_demand_pay_type($sku,$supplier_code,$id= NULL){

        if( NULL == $id) {
            $demand_list_infos = $this->purchase_db->from("purchase_demand as demand")
                ->where_in("demand_status", [SUGGEST_STATUS_NOT_FINISH, DEMAND_SKU_STATUS_CONFIR])
                ->where('sku', $sku)
                ->get()
                ->result_array();
        }else{

            $demand_list_infos = $this->purchase_db->from("purchase_demand as demand")
                ->where('id', $id)
                ->get()
                ->result_array();
        }

        if(!empty($demand_list_infos)) {
            $update_suggest = [];
            foreach ($demand_list_infos as $key => $unit_info) {

                $supplier_payment_info = $this->Supplier_payment_info_model->check_payment_info($supplier_code, $unit_info['is_drawback'], $unit_info['purchase_type_id']);
                $pay_type = isset($supplier_payment_info['payment_method']) ? $supplier_payment_info['payment_method'] :0;
                $account_type = isset($supplier_payment_info['supplier_settlement']) ? $supplier_payment_info['supplier_settlement'] :0;
                $update_suggest[] = [
                    'id' => $unit_info['id'],
                    // 'pay_type' => $pay_type,//支付方式
                    'account_type' => $account_type,// 结算方式
                ];
            }
        }

        if(!empty($update_suggest)){
            $update_res_sug = $this->purchase_db->update_batch('purchase_demand', $update_suggest,'id');
            if(empty($update_res_sug) and $update_res_sug!=0) throw new Exception("需求单更新支付方式/结算方式失败");
        }
    }

    //修改需求单支付方式
    public function change_suggest_pay_type($sku,$supplier_code){
        //根据sku查询未完结,未生成采购单的,未过期的需求单
        $query_builder = $this->purchase_db->where('sku', $sku);
        $query_builder = $query_builder->where('is_create_order', SUGGEST_ORDER_STATUS_N);
        $query_builder = $query_builder->where_in('suggest_status', [SUGGEST_STATUS_NOT_FINISH,SUGGEST_STATUS_REBORN]);
        $query_builder = $query_builder->from('purchase_suggest as ps');
        $query_builder = $query_builder->select('ps.id,ps.pay_type, ps.is_drawback, ps.purchase_type_id');
        $suggest_list_infos  = $query_builder->get()->result_array();

        //根据sku 查询还未审核的采购单及其关联的需求单号
        $query = $this->purchase_db->from('purchase_suggest_map as smp');
        $query = $query->join('purchase_order as od','od.purchase_number=smp.purchase_number','left');
        $query = $query->join('purchase_suggest as su','su.demand_number=smp.demand_number and su.sku=smp.sku','left');
        $query = $query->join('product as pd','pd.sku=smp.sku','left');
        $query = $query->where('su.sku', $sku);
        $query = $query->where('smp.sku', $sku);
        $query = $query->where_in('purchase_order_status', [PURCHASE_ORDER_STATUS_WAITING_QUOTE]);
        $query = $query->select('su.demand_number,su.sku,su.id,su.pay_type,su.is_drawback, su.purchase_type_id,smp.purchase_number,od.id as order_id');
        $purchase_order_infos  = $query->get()->result_array();

        if(empty($sku) || empty($supplier_code)){
            return;
        }
        if (empty($suggest_list_infos) && empty($purchase_order_infos)){
            return;
        }

        $supplier_code = $supplier_code;
        try {
            $this->purchase_db->trans_begin();
            //未完结的需求单
            if (!empty($suggest_list_infos)){
                foreach ($suggest_list_infos as $key => $unit_info) {
                    //新的供应商
                    $supplier_payment_info = $this->Supplier_payment_info_model->check_payment_info($supplier_code, $unit_info['is_drawback'], $unit_info['purchase_type_id']);
                    $pay_type = isset($supplier_payment_info['payment_method']) ? $supplier_payment_info['payment_method'] :0;
                    $account_type = isset($supplier_payment_info['supplier_settlement']) ? $supplier_payment_info['supplier_settlement'] :0;


                    $update_suggest[] = [
                        'id' => $unit_info['id'],
                        'pay_type' => $pay_type,//支付方式
                        'account_type' => $account_type,// 结算方式
                    ];
                    $insert_res = operatorLogInsert(
                        [
                            'id' => $unit_info['id'],
                            'type' => 'pur_purchase_suggest_pay_type',
                            'content' => '修改需求单支付方式',
                            'detail' => '修改支付/结算方式，从【' . $unit_info['pay_type'] . '/'.$account_type.'】改到【' . $pay_type . '/'.$account_type.'】',
                        ]
                    );
                    if(empty($insert_res)) throw new Exception($unit_info['id'].":需求单操作记录添加失败");
                }
                if(!empty($update_suggest)){
                    $update_res_sug = $this->purchase_db->update_batch('purchase_suggest', $update_suggest,'id');
                    if(empty($update_res_sug) and $update_res_sug!=0) throw new Exception("需求单更新支付方式/结算方式失败");
                }
            }


            if (!empty($purchase_order_infos)){
                foreach ($purchase_order_infos as $key => $value) {
                    $supplier_payment_info = $this->Supplier_payment_info_model->check_payment_info($supplier_code, $value['is_drawback'], $value['purchase_type_id']);
                    $pay_type = isset($supplier_payment_info['payment_method']) ? $supplier_payment_info['payment_method'] :0;
                    $account_type = isset($supplier_payment_info['supplier_settlement']) ? $supplier_payment_info['supplier_settlement'] :0;
                    $suggest_update[] = [
                        'id' => $value['id'],
                        'pay_type' => $pay_type,//支付方式
                        'account_type' => $account_type,// 结算方式
                    ];
                    $sug_insert_res = operatorLogInsert(
                        [
                            'id' => $value['id'],
                            'type' => 'pur_purchase_suggest_pay_type',
                            'content' => '修改需求单支付方式',
                            'detail' => '修改支付/结算方式，从【' . $value['pay_type'] . '/'.$account_type.'】改到【' . $pay_type . '/'.$account_type.'】',
                        ]
                    );

                    $update_order[] = [
                        'id' => $value['order_id'],
                        'pay_type' => $pay_type,//支付方式
                        'account_type' => $account_type,// 结算方式
                    ];
                    if(empty($sug_insert_res)) throw new Exception($value['id'].":需求单操作记录添加失败");
                }

                if(!empty($suggest_update)){
                    $update_res_sug_update = $this->purchase_db->update_batch('purchase_suggest', $suggest_update,'id');
                    if(empty($update_res_sug_update) and $update_res_sug_update!=0) throw new Exception("需求单更新支付方式/结算方式失败");
                }
                /* 41780 sku修改供应商后，采购单下单和审核新增逻辑限制 -> 只修改备货单不修改采购单
                 * if(!empty($update_order)){
                    $update_res_sug = $this->purchase_db->update_batch('purchase_order', $update_order,'id');
                    if(empty($update_res_sug) and $update_res_sug!=0) throw new Exception("采购单更新支付方式/结算方式失败");
                }*/

            }
            $this->purchase_db->trans_commit();
        }catch (Exception $e) {
            $this->purchase_db->trans_rollback();
            return ['msg' => $e->getMessage(), 'bool' => FALSE];
        }
        return ['msg' => '成功', 'bool' => TRUE];


    }



    public function change_purchase_ticketed_type($sku,$maintain_ticketed_point)
    {
        $query = $this->purchase_db->from('purchase_order as od');
        $query = $query->join('purchase_order_items as oi','oi.purchase_number=od.purchase_number','left');
        $query = $query->where('oi.sku', $sku);
        $query = $query->where_in('od.purchase_order_status', [PURCHASE_ORDER_STATUS_WAITING_QUOTE]);
        $query = $query->select('oi.purchase_number,oi.sku');
        $purchase_order_infos  = $query->get()->result_array();
        if( !empty($purchase_order_infos) )
        {
            foreach( $purchase_order_infos as $key=>$value)
            {
                $this->purchase_db->where("purchase_number",$value['purchase_number'])->where("sku",$value['sku'])->update('purchase_order_items',['maintain_ticketed_point'=>$maintain_ticketed_point]);

            }
        }
    }


    public function change_demand_purchase_price($sku,$change_data,$old_data = array(),$id = NULL)
    {


        if( NULL == $id) {
            $demand_list_infos = $this->purchase_db->from("purchase_demand as demand")
                ->where_in("demand_status", [SUGGEST_STATUS_NOT_FINISH, DEMAND_SKU_STATUS_CONFIR])
                ->where('sku', $sku)
                ->get()
                ->result_array();
        }

        if( NULL != $id){
            $demand_list_infos = $this->purchase_db->from("purchase_demand as demand")
                ->where('id', $id)
                ->get()
                ->result_array();

        }

        if(!empty($demand_list_infos)){

            try {
                $change_price = format_two_point_price($change_data['new_supplier_price']);
                $new_change_supplier_code = $change_data['new_supplier_code']; // 更新后供应商
                $old_supplier_code = isset($old_data['old_supplier_code'])?$old_data['old_supplier_code']:NULL; // 原来供应商CODE
                $old_change_price = isset($old_data['old_supplier_price'])?$old_data['old_supplier_price']:NULL;
                $update_suggest = [];
                foreach( $demand_list_infos as $unit_info){

                    $supplier_code = $change_data['new_supplier_code'];
                    $supplier_source = $this->purchase_db->from("supplier")->select("supplier_source")
                        ->where("supplier_code=",$supplier_code)->get()->row_array();

                    // 获取采购员信息。采购员ID，和采购员名称
                    $buyer_type = $unit_info['purchase_type_id'];
                    if($unit_info['purchase_type_id'] == PURCHASE_TYPE_FBA_BIG){
                        $buyer_type = PURCHASE_TYPE_OVERSEA;
                    }

                    if($unit_info['purchase_type_id'] == PURCHASE_TYPE_PFB){

                       $buyer_type = PURCHASE_TYPE_INLAND;
                    }

                    if($unit_info['purchase_type_id'] == PURCHASE_TYPE_PFH){

                        $buyer_type = PURCHASE_TYPE_FBA;
                    }

                    $supplier_buyer_info = $this->purchase_db->select('buyer_id,buyer_name')->from('supplier_buyer')
                        ->where(["supplier_code" => $supplier_code, "status"=>1, "buyer_type"=> $buyer_type])->get()->row_array();
                    if(!empty($supplier_buyer_info)){
                        $buyer_id = isset($supplier_buyer_info['buyer_id']) ? $supplier_buyer_info['buyer_id'] : '';
                        $buyer_name = isset($supplier_buyer_info['buyer_name']) ? $supplier_buyer_info['buyer_name'] : '';
                    }else{
                        $buyer_id = isset($unit_info['buyer_id']) ? $unit_info['buyer_id'] :'';
                        $buyer_name = isset($unit_info['buyer_name']) ? $unit_info['buyer_name'] :'';
                    }

                    $row_sug = [
                        'id' => $unit_info['id'],
                        'purchase_unit_price' => $change_price,//修改后的单价
                        'supplier_code' => $change_data['new_supplier_code'],//供应商编码
                        'supplier_name' => $change_data['new_supplier_name'],//供应商名称
                        'buyer_id' => $buyer_id,//采购员id
                        'buyer_name' => $buyer_name,//采购员名称
                        'purchase_total_price' => $change_price * $unit_info['purchase_amount'],//修改后的总价
                        //'supplier_source' => $supplier_source['supplier_source']
                    ];

                    if(in_array($unit_info['purchase_type_id'],[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_PFB])){
                        $change_price_de = $change_data['new_supplier_price'];
                    }else{
                        $change_price_de = $change_price;
                        if($change_data['is_drawback']){
                            $change_price_de = format_two_point_price($change_data['new_supplier_price']*(1+($change_data['new_ticketed_point']/100)));
                            $purchase_name = 'SZYB';
                        }else{
                            $change_price_de = format_two_point_price($change_data['new_supplier_price']);
                            $purchase_name = 'HKYB';
                        }

                        if(in_array($unit_info['purchase_type_id'],[PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFH])){
                            if($unit_info['is_drawback']){
                                $change_price_de = format_two_point_price($change_data['new_supplier_price']*(1+($change_data['new_ticketed_point']/100)));
                            }else{
                                $change_price_de = format_two_point_price($change_data['new_supplier_price']);
                            }
                        }

                        $row_sug['purchase_unit_price'] = $change_price_de;//修改后的单价
                        $row_sug['purchase_total_price'] = $change_price_de * $unit_info['demand_data'];//修改后的总价
                    }

                    $update_suggest[] = $row_sug;
                }

                if(!empty($update_suggest)){

                    $update_res_sug = $this->purchase_db->update_batch('purchase_demand', $update_suggest,'id');

                }

            }catch ( Exception $exp ){

                throw new Exception("SKU 需求单更新失败");
            }


        }

    }
    /**
     * @desc 产品修改单价,更新关联的需求单采购单价和总价,以及还未被采购经理审核通过的采购单的采购单价和总价
     * @author Jeff
     * @Date 2019/4/4 14:28
     * @return
     */
    public function change_suggest_purchase_price($sku,$change_data,$old_data = array())
    {
        //根据sku查询未完结,未生成采购单的,未过期的需求单
        $suggest_list_infos = $this->purchase_db->from('purchase_suggest as ps')
            ->select('ps.id,ps.purchase_amount,ps.purchase_unit_price,
            ps.supplier_code,ps.supplier_name,ps.is_drawback,ps.purchase_type_id,
            ps.buyer_id,ps.buyer_name,ps.account_type,ps.purchase_name')
            ->where_in('suggest_status', [SUGGEST_STATUS_NOT_FINISH,SUGGEST_STATUS_REBORN])
            ->where('is_create_order', SUGGEST_ORDER_STATUS_N)
            ->where('sku', $sku)
            ->get()->result_array();

        //根据sku 查询还未审核的采购单及其关联的需求单号
        $purchase_order_infos = $this->purchase_db->from('purchase_suggest_map as smp')
            ->select('pd.maintain_ticketed_point,
            od.is_ali_order,smp.demand_number,smp.sku,oi.id,
            oi.purchase_unit_price,od.is_drawback,
            pd.ticketed_point,od.purchase_number,od.purchase_type_id,od.is_gateway,pd.ticketed_point,pd.coupon_rate,
            pd.tax_rate,pd.declare_cname,pd.declare_unit')
            ->join('pur_purchase_order_items as oi','oi.purchase_number=smp.purchase_number AND oi.sku=smp.sku','inner')
            ->join('pur_purchase_order as od','od.purchase_number=smp.purchase_number','left')
            ->join('pur_product as pd','pd.sku=smp.sku','left')
            ->where('smp.sku=', $sku)
            ->where_in('od.purchase_order_status', [PURCHASE_ORDER_STATUS_WAITING_QUOTE])
            ->get()->result_array();

        if (empty($suggest_list_infos) && empty($purchase_order_infos)){
            return;
        }

        $change_price = format_two_point_price($change_data['new_supplier_price']);
        $new_change_supplier_code = $change_data['new_supplier_code']; // 更新后供应商
        $old_supplier_code = isset($old_data['old_supplier_code'])?$old_data['old_supplier_code']:NULL; // 原来供应商CODE
        $old_change_price = isset($old_data['old_supplier_price'])?$old_data['old_supplier_price']:NULL;
        //开始事物
        try {
            $this->purchase_db->trans_begin();
            $sug_id = array_column($suggest_list_infos, 'id');
            $has_suggest = [];
            if(count($sug_id) > 0)$has_suggest = $this->get_operator_log_change_buyer($sug_id);

            if (!empty($suggest_list_infos)){

                $update_suggest = [];
                foreach ($suggest_list_infos as $unit_info){
                    //采购员
                    $supplier_code = $change_data['new_supplier_code'];
                    $supplier_source = $this->purchase_db->from("supplier")->select("supplier_source")->where("supplier_code=",$supplier_code)->get()->row_array();

                    // 获取采购员信息。采购员ID，和采购员名称
                    $buyer_type = $unit_info['purchase_type_id'];
                    if($unit_info['purchase_type_id'] == PURCHASE_TYPE_FBA_BIG){
                        $buyer_type = PURCHASE_TYPE_OVERSEA;
                    }

                    if($unit_info['purchase_type_id'] == PURCHASE_TYPE_PFB){

                        $buyer_type = PURCHASE_TYPE_INLAND;
                    }

                    if($unit_info['purchase_type_id'] == PURCHASE_TYPE_PFH){

                        $buyer_type =  PURCHASE_TYPE_FBA;
                    }

                    $supplier_buyer_info = $this->purchase_db->select('buyer_id,buyer_name')->from('supplier_buyer')
                        ->where(["supplier_code" => $supplier_code, "status"=>1, "buyer_type"=> $buyer_type])->get()->row_array();
                    if(!empty($supplier_buyer_info)){
                        $buyer_id = isset($supplier_buyer_info['buyer_id']) ? $supplier_buyer_info['buyer_id'] : '';
                        $buyer_name = isset($supplier_buyer_info['buyer_name']) ? $supplier_buyer_info['buyer_name'] : '';
                    }else{
                        $buyer_id = isset($unit_info['buyer_id']) ? $unit_info['buyer_id'] :'';
                        $buyer_name = isset($unit_info['buyer_name']) ? $unit_info['buyer_name'] :'';
                    }
                    $row_sug = [
                        'id' => $unit_info['id'],
                        'purchase_unit_price' => $change_price,//修改后的单价
                        'supplier_code' => $change_data['new_supplier_code'],//供应商编码
                        'supplier_name' => $change_data['new_supplier_name'],//供应商名称
                        'buyer_id' => $buyer_id,//采购员id
                        'buyer_name' => $buyer_name,//采购员名称
                        'purchase_total_price' => $change_price * $unit_info['purchase_amount'],//修改后的总价
                        'supplier_source' => $supplier_source['supplier_source']
                    ];

                    if(in_array($unit_info['purchase_type_id'],[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_PFB])){
                        $change_price_de = $change_data['new_supplier_price'];
                    }else{
                        $change_price_de = $change_price;
                        if($change_data['is_drawback']){
                            $change_price_de = format_two_point_price($change_data['new_supplier_price']*(1+($change_data['new_ticketed_point']/100)));
                            $purchase_name = 'SZYB';
                        }else{
                            $change_price_de = format_two_point_price($change_data['new_supplier_price']);
                            $purchase_name = 'HKYB';
                        }

                        if(in_array($unit_info['purchase_type_id'],[PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFH])){
                            if($unit_info['is_drawback']){
                                $change_price_de = format_two_point_price($change_data['new_supplier_price']*(1+($change_data['new_ticketed_point']/100)));
                            }else{
                                $change_price_de = format_two_point_price($change_data['new_supplier_price']);
                            }
                        }

                        $row_sug['purchase_unit_price'] = $change_price_de;//修改后的单价
                        $row_sug['purchase_total_price'] = $change_price_de * $unit_info['purchase_amount'];//修改后的总价
                    }

                    if(in_array($unit_info['id'], $has_suggest)){
                        unset($row_sug['buyer_id']);
                        unset($row_sug['buyer_name']);
                    }
                    $update_suggest[] = $row_sug;
                    $insert_res = operatorLogInsert(
                        [
                            'id' => $unit_info['id'],
                            'type' => 'pur_purchase_suggest',
                            'content' => '修改需求单单价',
                            'detail' => '修改单价，从【' . $unit_info['purchase_unit_price'] . '】改到【' . $change_price_de . '】;修改供应商，从【' . $unit_info['supplier_code'] . '】改到【' . $change_data['new_supplier_code'] . '】;修改是否退税，从【' . $unit_info['is_drawback'] . '】改到【' . $change_data['is_drawback'] . '】',
                        ]
                    );

                    if(empty($insert_res)) throw new Exception($unit_info['id'].":需求单操作记录添加失败");
                }

                //更新需求表
                if(!empty($update_suggest)){
                    $update_res_sug = $this->purchase_db->update_batch('purchase_suggest', $update_suggest,'id');
                    if(empty($update_res_sug) and $update_res_sug!=0) throw new Exception("需求单更新采购价格失败");
                }
            }

            if (!empty($purchase_order_infos)){
                $demand_numbers = array_column($purchase_order_infos,'demand_number');
                $item_ids = [];

                /* 41780 sku修改供应商后，采购单下单和审核新增逻辑限制 -> 只修改备货单不修改采购单
                 * foreach ($purchase_order_infos as $key => $value) {
                    $demand_numbers[] = $value['demand_number'];
                    $item_ids[] = $value['id'];
                    $order_change = [];
                    $is_supplier_change_flag = true;
                    if( $value['is_ali_order'] == 1) {
                        if($new_change_supplier_code != $old_supplier_code && $old_change_price != $change_price ) {
                            $is_supplier_change_flag = false;
                        }
                    }

                    if($is_supplier_change_flag) {
                        $order_change['supplier_code'] = $change_data['new_supplier_code'];
                        $order_change['supplier_name'] = $change_data['new_supplier_name'];
                    }

                    if(!empty($order_change)){
                        $this->purchase_db->where('purchase_number', $value['purchase_number'])->update('purchase_order', $order_change);
                    }
                }*/

                //根据sku查询未完结,未生成采购单的,未过期的需求单
                $suggest_list_infos_ordered = $this->purchase_db->from('purchase_suggest')
                    ->select('id,purchase_amount,purchase_unit_price,supplier_code,supplier_name,is_drawback,
                    purchase_type_id,buyer_id,buyer_name,account_type,purchase_name')
                    ->where_in('demand_number', $demand_numbers)
                    ->get()->result_array();
                $suggest_update = [];
                foreach ($suggest_list_infos_ordered as $unit_info){
                    //采购员
                    $change_price_de = $change_data['new_supplier_price'];
                    $supplier_code = $change_data['new_supplier_code'];
                    $supplier_source = $this->purchase_db->from("supplier")->select("supplier_source")->where("supplier_code=",$supplier_code)->get()->row_array();
                    $row_suggest = [
                        'id' => $unit_info['id'],
                        'purchase_unit_price' => $change_price,//修改后的单价
                        'supplier_code' => $change_data['new_supplier_code'],//供应商编码
                        'supplier_name' => $change_data['new_supplier_name'],//供应商名称
                        'purchase_total_price' => $change_price * $unit_info['purchase_amount'],//修改后的总价
                        'supplier_source' =>$supplier_source['supplier_source']
                    ];
                    if(!in_array($unit_info['purchase_type_id'],[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_PFB])){
                        if($change_data['is_drawback']){
                            $change_price_de = format_two_point_price($change_data['new_supplier_price']*(1+($change_data['new_ticketed_point']/100)));
                            $purchase_name = 'SZYB';
                        }else{
                            $purchase_name = 'HKYB';
                        }
                        if(in_array($unit_info['purchase_type_id'],[PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFH])){
                            if($unit_info['is_drawback']){
                                $change_price_de = format_two_point_price($change_data['new_supplier_price']*(1+($change_data['new_ticketed_point']/100)));
                            }
                        }
                        $row_suggest['purchase_unit_price'] = $change_price_de;
                        $row_suggest['purchase_total_price'] = $change_price_de * $unit_info['purchase_amount'];
                    }
                    $suggest_update[] = $row_suggest;

                    $insert_res = operatorLogInsert(
                        [
                            'id' => $unit_info['id'],
                            'type' => 'pur_purchase_suggest',
                            'content' => '修改需求单单价',
                            'detail' => '修改单价，从【' . $unit_info['purchase_unit_price'] . '】改到【' . $change_price_de . '】;修改供应商，从【' . $unit_info['supplier_code'] . '】改到【' . $change_data['new_supplier_code'] . '】;修改是否退税，从【' . $unit_info['is_drawback'] . '】改到【' . $change_data['is_drawback'] . '】',
                        ]
                    );

                    if(empty($insert_res)) throw new Exception($unit_info['id'].":需求单操作记录添加失败");
                }

                //更新需求表
                if(!empty($suggest_update)){
                    $update_res_status = $this->purchase_db->update_batch('purchase_suggest', $suggest_update,'id');
                    if(empty($update_res_status) && $update_res_status!=0) throw new Exception("已生成采购单的需求单更新采购价格失败");
                }


                //采购单明细id
                $item_info = $purchaseInvoice = [];
                foreach ($item_ids as $key => $id){
                    //FBA是否退税,导入的时候已经决定，产品修改了票点，不影响FBA业务线的订单是否退税
                    $row_info = [
                        'id' => $id,
                        'product_base_price' => $change_price,//末税单价
                        'purchase_unit_price' => $change_price, //含税单价
                        'coupon_rate' => $purchase_order_infos[$key]['coupon_rate'],
                        'pur_ticketed_point' => $purchase_order_infos[$key]['ticketed_point'],
                        'is_customized' => $change_data['new_is_customized']
                    ];
                    if(in_array($purchase_order_infos[$key]['purchase_type_id'],[PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFH])){
                        $p_s_deinfo =$this->purchase_db->select('is_drawback')
                            ->from('purchase_suggest')
                            ->where(["demand_number" => $purchase_order_infos[$key]['demand_number'], "sku" => $purchase_order_infos[$key]['sku']])
                            ->get()->row_array();
                        if(isset($p_s_deinfo['is_drawback']) && $p_s_deinfo['is_drawback'] == 1){
                            $row_info['purchase_unit_price'] = format_two_point_price($change_price * (1 + ($purchase_order_infos[$key]['ticketed_point'] ? $purchase_order_infos[$key]['ticketed_point'] / 100 : 0))); //含税单价
                        }
                    }


                    if ($purchase_order_infos[$key]['is_drawback']){
                        if(!in_array($purchase_order_infos[$key]['purchase_type_id'],[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_PFB])){
                            $row_info['purchase_unit_price'] = format_two_point_price($change_price * (1 + ($purchase_order_infos[$key]['ticketed_point'] ? $purchase_order_infos[$key]['ticketed_point'] / 100 : 0))); //含税单价
                        }
                    }
                    $insert_res = operatorLogInsert(
                        [
                            'id' => $id,
                            'type' => 'purchase_order_items',
                            'content' => '修改采购明细表单价',
                            'detail' => '修改单价，从【' . $purchase_order_infos[$key]['purchase_unit_price'] . '】改到【' . $change_price*
                                format_two_point_price((1+($purchase_order_infos[$key]['ticketed_point']?$purchase_order_infos[$key]['ticketed_point']/100:0))) . '】;修改票点，从【' . $purchase_order_infos[$key]['ticketed_point'] . '】改到【' . $change_data['new_ticketed_point'] . '】',
                        ]
                    );
                    $item_info[] = $row_info;
                    if(empty($insert_res)) throw new Exception($purchase_order_infos[$key]['id'].":需求单操作记录添加失败");
                }
                //更新采购明细表
                if(!empty($item_info)){
                    $update_res_order = $this->purchase_db->update_batch('purchase_order_items', $item_info,'id');
                    if(empty($update_res_order) and $update_res_order!=0) throw new Exception("采购明细表更新采购价格失败");
                }
            }

            $this->purchase_db->trans_commit();
        }catch (Exception $e) {
            $this->purchase_db->trans_rollback();
            return ['msg' => $e->getMessage(), 'bool' => FALSE];
        }
        return ['msg' => '成功', 'bool' => TRUE];
    }

    /**
     * 获取日志中手动修改采购员记录
     */
    public function get_operator_log_change_buyer($id)
    {
        if(empty($id))return [];
        $data = $this->purchase_db->from('operator_log')
            ->where("record_type = ", "PURCHASE_SUGGEST")
            ->where_in("record_number", $id)
            ->get()
            ->result_array();
        if($data && count($data) > 0)return array_column($data, 'record_number');
        return [];
    }


    /**
     * @desc 作废需求单
     * @author Jeff
     * @Date 2019/4/18 13:45
     * @param $ids 需求单id
     * @return array
     * @return
     */
    public function demand_order_cancel($ids,$cancel_reason,$suggest_list,$cancel_reason_category,$skuList = null)
    {
        $return = ['code'=>false,'msg'=>''];
        $this->load->model('approval_model');
        $this->purchase_db->trans_begin();
        try {

            $update_data = [];
            $push_data = [];//推送计划系统数据
            foreach ($ids as $key => $id){
                $cancel_name = getActiveUserName();
                $update_data[] = [
                    'id' => $id,
                    'suggest_status' => SUGGEST_STATUS_CANCEL,//作废
                    'cancel_reason' => $cancel_reason.'-'.date('Y-m-d H:i:s',time()).'-'.$cancel_name,//作废原因
                    'audit_status' => SUGGEST_UN_AUDIT,//待审核
                    'cancel_reason_category' => $cancel_reason_category,//作废原因类别
                ];

                //构造推送数据
                if ($suggest_list[$key]['source_from']==1){//数据来源于计划系统才推送计划系统
                    $push_data[] = [
                        'pur_sn' => $suggest_list[$key]['demand_number'],//备货单号
                        'state' => SUGGEST_STATUS_CANCEL,
                        'business_line' => $suggest_list[$key]['purchase_type_id'],//业务线
                    ];
                }

                $insert_res = operatorLogInsert(
                    [
                        'id' => $suggest_list[$key]['demand_number'],
                        'type' => 'pur_purchase_suggest',
                        'content' => '修改需求状态',
                        'detail' => '修改状态，从【' . getSuggestStatus($suggest_list[$key]['suggest_status']) . '】改到【' . getSuggestStatus(SUGGEST_STATUS_CANCEL) . '】',
                    ]
                );
                if(empty($insert_res)) throw new Exception($suggest_list[$key]['demand_number'].":需求单操作记录添加失败");
            }


            $update_res = $this->purchase_db->update_batch('purchase_suggest', $update_data,'id');

            if(empty($update_res)){
                throw new Exception("需求单状态更新失败");
            }
            //对sku作废原因进行维护
            $cancel_update = $this->save_sku_cancel_reason($skuList, $cancel_reason.'-'.date('Y-m-d H:i:s',time()).'-'.$cancel_name,$cancel_reason_category);

            if(empty($cancel_update)){
                throw new Exception("SKU拒绝原因状态维护表保存失败");
            }


            if (!empty($push_data)){
                //推送计划系统
                $push_plan = $this->approval_model->push_plan_expiration($push_data);//推送计划系统作废备货单
                if($push_plan !== true){
                    throw new Exception('推送计划系统作废失败！');
                }
            }

            $this->purchase_db->trans_commit();
            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * 验证需求单是否已作废
     */
    public function validate_cancel($suggest_list){

        if(!empty($suggest_list)){
            $flag=0;
            foreach($suggest_list as $key => $val){

                if($val['suggest_status'] == SUGGEST_STATUS_CANCEL){
                    $flag++;
                }
            }
            if($flag){
                return false;
            }else{
                return true;
            }
        }
    }

    //获取待审核的需求单
    public function get_un_audit_list($params,$offset = 0,$limit = 0,$page = 1,$export = false)
    {
        $userid=jurisdiction(); //当前登录用户ID
        $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
        $role=get_user_role();//当前登录角色
        $data_role = getRole();//数据权限配置
        $res_arr = array_intersect($role, $data_role);
        $params                 = $this->table_query_filter($params);// 过滤为空的元素
        $query_builder          = $this->purchase_db;
        $query_builder->from('purchase_suggest as ps');
        $query_builder->where('ps.audit_status',SUGGEST_UN_AUDIT);
        $query_builder = $query_builder->join('product as pd','pd.sku=ps.sku','left');

//        if(isset($params['product_status']) and $params['product_status']!=''){
//            $query_builder = $query_builder->join('product as pd','pd.sku=ps.sku','left');
//        }

        if( !(!empty($res_arr) OR $userid === true )){
            $query_builder->where_in('ps.buyer_id',$userid);
        }

        if( isset($params['group_ids']) && !empty($params['group_ids']))
        {
            $query_builder->where_in('ps.buyer_id',$params['groupdatas']);
        }

        if(isset($params['user_groups_types'])){
            $user_groups_types = $params['user_groups_types'];
        }
        if(is_array($user_groups_types)){
            $query_builder->where_in('ps.purchase_type_id', $user_groups_types);
        }

        if (!empty($params['sku'])) {
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索
                $this->purchase_db->like('ps.sku', $params['sku'], 'both');
            } else {
                $this->purchase_db->where_in('ps.sku', $sku);
            }
        }

        if(isset($params['shipment_type']) && !empty($params['shipment_type'])){
            $this->purchase_db->where("ps.shipment_type",$params['shipment_type']);
        }

        if (isset($params['product_line_id']) and $params['product_line_id']){
            $query_builder->where('ps.product_line_id',$params['product_line_id']);
            unset($params['product_line_id']);
        }
        if(isset($params['transformation']) && !empty($params['transformation'])){
            if( $params['transformation'] == 1){
                $query_builder->where('ps.sku_state_type!=',6);
            }else {
                $query_builder->where('ps.sku_state_type', $params['transformation']);
            }
        }

        if( isset($params['is_overseas_first_order']) && $params['is_overseas_first_order'] != NULL ){

            $query_builder->where("ps.is_overseas_first_order",$params['is_overseas_first_order']);
        }

        if (isset($params['demand_number']) and trim($params['demand_number'])){
            $demand_number_arr = array_filter(explode(' ', trim($params['demand_number'])));
            $query_builder->where_in('ps.demand_number',$demand_number_arr);
            unset($params['demand_number']);
        }

        if (isset($params['is_drawback']) and $params['is_drawback']!=''){
            $query_builder->where('ps.is_drawback',$params['is_drawback']);
            unset($params['is_drawback']);
        }

        if( isset($params['estimate_time_start']) && isset($params['estimate_time_end']))
        {
            $query_builder->where('ps.estimate_time>=',$params['estimate_time_start'])->where('ps.estimate_time<=',$params['estimate_time_end']);
        }

        if(isset($params['earliest_exhaust_date_start']) and $params['earliest_exhaust_date_start']){
            $params['earliest_exhaust_date_start'] and $query_builder->where('ps.earliest_exhaust_date >=',$params['earliest_exhaust_date_start']);
            unset($params['earliest_exhaust_date_start']);
        }

        if(isset($params['earliest_exhaust_date_end']) and $params['earliest_exhaust_date_end']){
            $params['earliest_exhaust_date_end'] and $query_builder->where('ps.earliest_exhaust_date<',$params['earliest_exhaust_date_end']);
            unset($params['earliest_exhaust_date_end']);
        }

        if(isset($params['create_time_start']) and $params['create_time_start']){
            $params['create_time_start'] and $query_builder->where('ps.create_time >=',$params['create_time_start']);
            unset($params['create_time_start']);
        }
        if(isset($params['create_time_end']) and $params['create_time_end']){
            $params['create_time_end'] and $query_builder->where('ps.create_time <=',$params['create_time_end']);
            unset($params['create_time_end']);
        }

        if (isset($params['purchase_type_id']) and $params['purchase_type_id']){
            if(is_array($params['purchase_type_id'])){
                $query_builder->where_in('ps.purchase_type_id',$params['purchase_type_id']);
            }else{
                $query_builder->where('ps.purchase_type_id',$params['purchase_type_id']);
            }
            unset($params['purchase_type_id']);
        }


        // 预计到货时间
        if( isset($params['estimate_time_start']) && isset($params['estimate_time_end']) )
        {
            $query_builder->where('ps.create_time<ps.estimate_time')->where('ps.estimate_time>=',$params['estimate_time_start'])->where('ps.estimate_time<=',$params['estimate_time_end']);
        }

        if (isset($params['is_expedited']) and $params['is_expedited']){
            $query_builder->where('ps.is_expedited',$params['is_expedited']);
            unset($params['is_expedited']);
        }

        if (isset($params['create_user_id']) and $params['create_user_id']){
            $query_builder->where('ps.create_user_id',$params['create_user_id']);
            unset($params['create_user_id']);
        }

        if (isset($params['destination_warehouse']) and $params['destination_warehouse']){
            if(is_array($params['destination_warehouse'])){
                $query_builder->where_in('ps.destination_warehouse', $params['destination_warehouse']);
            }else{
                $query_builder->where('ps.destination_warehouse', $params['destination_warehouse']);
            }
            unset($params['destination_warehouse']);
        }

        if (isset($params['logistics_type']) and $params['logistics_type']){
            $query_builder->where('ps.logistics_type=binary("'.$params['logistics_type'].'")');
            unset($params['logistics_type']);
        }

        if (isset($params['warehouse_code']) and $params['warehouse_code']){
            if(is_array($params['warehouse_code'])){
                $query_builder->where_in('ps.warehouse_code', $params['warehouse_code']);
            }else{
                $query_builder->where('ps.warehouse_code', $params['warehouse_code']);
            }
            unset($params['warehouse_code']);
        }

        if (isset($params['pertain_wms']) and $params['pertain_wms']){
            if(is_array($params['pertain_wms'])){
                $pertain_wms_list = implode("','",$params['pertain_wms']);
            }else{
                $pertain_wms_list = implode("','",explode(',',$params['pertain_wms']));
            }
            $query_builder->where("ps.warehouse_code IN(SELECT warehouse_code FROM pur_warehouse WHERE pertain_wms IN('{$pertain_wms_list}'))");
            unset($params['pertain_wms']);
        }

        if (isset($params['suggest_status']) and $params['suggest_status']){
            if(is_array($params['suggest_status'])){
                $query_builder->where_in('ps.suggest_status', $params['suggest_status']);
            }else{
                $query_builder->where('ps.suggest_status', $params['suggest_status']);
            }

            unset($params['suggest_status']);
        }else{
            $query_builder->where('ps.suggest_status != ',SUGGEST_STATUS_EXPIRED);
        }

        //产品状态
        if (isset($params['product_status']) and trim($params['product_status'])!=''){
            $product_status= explode(',', trim($params['product_status']));
            $query_builder->where_in('pd.product_status',$product_status);
            unset($params['product_status']);
        }

        if (isset($params['is_new']) and $params['is_new']!=''){
            if ($params['is_new']==PRODUCT_SKU_IS_NEW){
                $query_builder->where('ps.is_new',PRODUCT_SKU_IS_NEW);//是新品
            }else{
                $query_builder->where('ps.is_new= ',PRODUCT_SKU_IS_NOT_NEW);//不是新品
            }
            unset($params['is_new']);
        }

        //是否精品
        if (isset($params['is_boutique']) and $params['is_boutique']!=''){
            $query_builder->where('ps.is_boutique',$params['is_boutique']);
            unset($params['is_boutique']);
        }

        //开发类型
        if (isset($params['state_type']) && !empty($params['state_type'])){
            if(is_array($params['state_type'])){
                $query_builder->where_in('pd.state_type',$params['state_type']);
            }else{
                $query_builder->where('pd.state_type',$params['state_type']);
            }

            unset($params['state_type']);
        }

        // 发运类型、
        if(  isset($params['shipment_type']) && !empty($params['shipment_type'])){
            $query_builder->where("ps.shipment_type",$params['shipment_type']);
        }

        //作废原因
        if (isset($params['cancel_reason']) and !empty($params['cancel_reason'])){
            /*$count = count($params['cancel_reason']);

            for ($i=0;$i<$count;$i++){
                if($i==$count) break;
                if ($i==0){
                    $query_builder->group_start();
                    $query_builder->like('ps.cancel_reason',$params['cancel_reason'][$i],'after');

                }else{
                    $query_builder->or_group_start();
                    $query_builder->like('ps.cancel_reason',$params['cancel_reason'][$i],'after');
                    $query_builder->group_end();
                }

            }
            $query_builder->group_end();*/

            $query_builder->where_in('ps.cancel_reason_category',$params['cancel_reason']);

            unset($params['cancel_reason']);
        }

        if (isset($params['order_by']) and $params['order_by'] and isset($params['order']) and $params['order']){
            switch ($params['order_by']){
                /*case 1://供应商
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.supplier_name',$params['order']);
                    break;
                case 2://采购员
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.buyer_id',$params['order']);
                    break;
                case 3://产品名称
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.product_name',$params['order']);
                    break;
                case 4://是否退税
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.is_drawback',$params['order']);
                    break;
                case 5://预计到货时间
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.plan_product_arrive_time',$params['order']);
                    break;
                case 6://创建时间
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.create_time',$params['order']);
                    break;*/
                case 7://一级产品线
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.product_line_id',$params['order']);
                    break;
                case 8://二级产品线
                    if (in_array($params['order'],['desc','asc'])) $query_builder->order_by('ps.two_product_line_id',$params['order']);
                    break;
                default:
            }

            unset($params['order_by']);
            unset($params['order']);
        }

        if (isset($params['id']) and $params['id']){
            $query_builder->where_in('ps.id',$params['id']);
            unset($params['id']);
        }

        unset($params['order_by']);
        unset($params['order']);
        $limit = query_limit_range($limit,false);

//        $query_builder_sum      = clone $query_builder;// 克隆一个查询 用来做数据汇总
        $query_builder          = $query_builder->select('ps.*,ps.sku_state_type,pd.state_type,pd.starting_qty,pd.starting_qty_unit,pd.product_thumb_url');
        $query_builder_count    = clone $query_builder;// 克隆一个查询 用来计数
        $query_builder_tmp      = clone $query_builder;// 克隆一个查询,用来返回查询的 SQL 语句

        $total_count            = $query_builder_count->count_all_results();

        //数据汇总
//        $huizong_arr            = $query_builder_sum->select('sum(ps.purchase_amount) as purchase_amount_all,count(distinct ps.sku) as sku_all')->get()->row_array();

        $query_sql              = $query_builder_tmp->get_compiled_select();// 获取查询的 SQL

        $this->load->service('basic/SearchExportCacheService');
        $total = str_pad((string)$total_count, 10, '0', STR_PAD_LEFT);
        $this->searchexportcacheservice->setScene($this->searchexportcacheservice::PURCHASE_SUGGEST_AUDIT_LIST_SEARCH_EXPORT)->set($total.$query_sql);


        if($export){//导出查询，不需要传分页
            $results                = $query_builder->get('',$limit)->result_array();
        }else{//列表查询
            $query_builder->order_by('ps.create_time','desc');
            $results                = $query_builder->get('',$limit,$offset)->result_array();
        }
        $this->session->set_tempdata('purchase_suggest-get_list', $query_sql,500);//设置缓存和有效时间
        if(in_array(SALE, $role)){
            foreach ($results as $key=>$row) {
                $results[$key]['purchase_unit_price']=0;
                $results[$key]['purchase_total_price']=0;
                $results[$key]['supplier_name']='';

            }
        }

        //待
        $return_data = [
            'data_list'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'offset'    => $page,
                'limit'     => $limit
            ],
//            'aggregate_data'  => $huizong_arr,
        ];

        return $return_data;
    }

    /**
     * 验证需求单是否已审核
     */
    public function validate_audit($suggest_list){

        if(!empty($suggest_list)){
            $flag=0;
            foreach($suggest_list as $key => $val){

                if($val['audit_status'] == SUGGEST_AUDITED_PASS || $val['audit_status'] == SUGGEST_AUDITED_UN_PASS ){
                    $flag++;
                }
            }
            if($flag){
                return false;
            }else{
                return true;
            }
        }
    }

    public function audit_suggest($ids,$type,$suggest_list,$audit_note=''){
        $this->load->model('suggest_expiration_set_model');
        $return = ['code'=>false,'msg'=>''];

        $this->purchase_db->trans_begin();
        try {

            if ($type == SUGGEST_AUDITED_UN_PASS){
                //审核未通过
                $update_data = [];
                $push_data = [];
                foreach ($ids as $key => $id){

                    if(!isset($suggest_list[$key])){
                        throw new Exception("请刷新页面重试");
                    }

                    //根据产品线获取过期时间
//                        $expiration = $this->suggest_expiration_set_model->get_one_by_type_id($suggest_list[$key]['purchase_type_id']);

                    $update_data[] = [
                        'id' => $id,
                        'audit_status' => SUGGEST_AUDITED_UN_PASS,//审核未通过
                        'audit_time' => date('Y-m-d H:i:s',time()),//审核时间
                        'audit_note' => $audit_note,//审核备注
//                            'expiration_time' => date('Y-m-d H:i:s',strtotime('+'.$expiration['expiration_time'].'day'))//过期时间
                    ];

                    //构造推送数据
                    if ($suggest_list[$key]['source_from']==1){//数据来源于计划系统才推送计划系统
                        $push_data[] = [
                            'id' => $id,
                            'demand_number' => $suggest_list[$key]['demand_number'],//备货单号
                            'audit_status' => SUGGEST_AUDITED_UN_PASS,//审核未通过
                            'audit_time' => date('Y-m-d H:i:s',time()),//审核时间
                            'business_line' => $suggest_list[$key]['purchase_type_id'],//业务线
//                            'expiration_time' => date('Y-m-d H:i:s',strtotime('+'.$expiration['expiration_time'].'day'))//过期时间
                        ];
                    }

                    $insert_res = operatorLogInsert(
                        [
                            'id' => $suggest_list[$key]['demand_number'],
                            'type' => 'pur_purchase_suggest',
                            'content' => '修改需求审核状态',
                            'detail' => '修改审核状态，从【待审核】改到【审核未通过】',
                        ]
                    );
                    if(empty($insert_res)) throw new Exception($suggest_list[$key]['demand_number'].":需求单操作记录添加失败");
                }

                $update_res = $this->purchase_db->update_batch('purchase_suggest', $update_data,'id');

                if(empty($update_res)){
                    throw new Exception("需求单状态更新失败");
                }

                if (!empty($push_data)){
                    //推送计划系统审核后的需求单
                    $this->push_plan_audit($push_data);
                }


                $this->purchase_db->trans_commit();
                $return['code'] = true;

            }elseif($type == SUGGEST_AUDITED_PASS){
                //审核通过
                $update_data = [];
                $push_data = [];
                // 获取IDS 的备货单的SKU

                $skusIds = $this->purchase_db->from("purchase_suggest")->where_in("id",$ids)->select("sku,id,purchase_type_id,is_overseas_first_order")->get()->result_array();
                $skusIds = array_column( $skusIds,NULL,"id");
                foreach ($ids as $key => $id){

                    if(!isset($suggest_list[$key])){
                        throw new Exception("请刷新页面重试");
                    }

                    if($suggest_list[$key]['suggest_status']==SUGGEST_STATUS_CANCEL){
                        throw new Exception($suggest_list[$key]['demand_number'].":需求单已作废,无法审核");
                    }

                    if(empty($suggest_list[$key]['warehouse_code'])){
                        throw new Exception($suggest_list[$key]['demand_number'].":采购仓库缺失，无法审核，请联系IT线下处理");
                    }

                    if($suggest_list[$key]['purchase_type_id']==PURCHASE_TYPE_OVERSEA && $suggest_list[$key]['source_from']!=1 && (empty($suggest_list[$key]['destination_warehouse']))){
                        throw new Exception($suggest_list[$key]['demand_number'].":目的仓缺失，无法审核，请联系IT线下处理");
                    }

                    //根据产品线获取过期时间
//                    $expiration = $this->suggest_expiration_set_model->get_one_by_type_id($suggest_list[$key]['purchase_type_id']);
                    $update_data[$key] = [
                        'id' => $id,
                        'audit_status' => SUGGEST_AUDITED_PASS,//审核通过
                        'audit_time' => date('Y-m-d H:i:s',time()),
//                        'expiration_time' => date('Y-m-d H:i:s',strtotime('+'.$expiration['expiration_time'].'day'))//过期时间
                    ];

                    /*//判断是否需要非实单锁单
                    $need_lock_res = $this->is_need_lock($suggest_list[$key]['sku'],$suggest_list[$key]['supplier_code']);

                    if ($need_lock_res){
                        $update_data[$key]['lock_type'] = LOCK_SUGGEST_NOT_ENTITIES;//非实单锁单
                        $update_data[$key]['is_locked'] = 1;//是否非实单锁单过
                    }else{
                        $update_data[$key]['lock_type'] = 0;
                        $update_data[$key]['is_locked'] = 0;
                    }*/

                    //构造推送数据
                    if ($suggest_list[$key]['source_from']==1){//数据来源于计划系统才推送计划系统
                        $push_data[] = [
                            'id' => $id,
                            'demand_number' => $suggest_list[$key]['demand_number'],//备货单号
                            'audit_status' => SUGGEST_AUDITED_PASS,//审核通过
                            'audit_time' => date('Y-m-d H:i:s',time()),//审核时间
                            'business_line' => $suggest_list[$key]['purchase_type_id'],//业务线
//                            'expiration_time' => date('Y-m-d H:i:s',strtotime('+'.$expiration['expiration_time'].'day'))//过期时间
                        ];
                    }

                    $insert_res = operatorLogInsert(
                        [
                            'id' => $suggest_list[$key]['demand_number'],
                            'type' => 'pur_purchase_suggest',
                            'content' => '修改需求审核状态',
                            'detail' => '修改审核状态，从【待审核】改到【审核通过】',
                        ]
                    );

                    $is_tax = $suggest_list[$key]['is_drawback']??'';
                    $purchase_type_id = $suggest_list[$key]['purchase_type_id']??'';

                    if( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA/国内 一样
                        $purchase_type_id = 1;
                    }
                    if($is_tax == 1){//否退税 对应所有的业务线
                        $purchase_type_id = 0;
                    }

                    $supplier_info = $this->supplier_model->get_supplier_info($suggest_list[$key]['supplier_code']); // 供应商信息
                    $supplier_payment_info = $supplier_info['supplier_payment_info'][$is_tax][$purchase_type_id]??[];
                    // 如果支付方式为线上境内或者境外支付，采购来源为合同单
                    if(!empty($supplier_payment_info['payment_method']) && in_array($supplier_payment_info['payment_method'],[PURCHASE_PAY_TYPE_PUBLIC,PURCHASE_PAY_TYPE_PRIVATE])){

                        $update_data[$key]['source'] = SOURCE_COMPACT_ORDER;
                    }else{
                        $update_data[$key]['source'] = SOURCE_NETWORK_ORDER;
                    }
                    if(empty($insert_res)) throw new Exception($suggest_list[$key]['demand_number'].":需求单操作记录添加失败");
                }
                $update_res = $this->purchase_db->update_batch('purchase_suggest', $update_data,'id');
                $insert_res = operatorLogInsert(
                    [
                        'id' => $suggest_list[$key]['demand_number'],
                        'type' => 'pur_purchase_suggest',
                        'content' => '备货单采购来源日志',
                        'detail' => 'suggestData='.json_encode($suggest_list).'is_tax='.$is_tax.",purchase_type_id=".$purchase_type_id.",supplier_info=".json_encode($supplier_info).",payinfo=".json_encode($supplier_payment_info),
                    ]
                );
                if(empty($update_res)){
                    throw new Exception("需求单状态更新失败");
                }

                if (!empty($push_data)){
                    //推送计划系统审核后的需求单
                    $this->push_plan_audit($push_data);
                }

                $this->purchase_db->trans_commit();
                $return['code'] = true;
            }

        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * @desc 回写审核后的需求单到计划系统
     * @author Jeff
     * @Date 2019/4/2 14:49
     * @param $audit_data
     * @throws Exception
     */
    public function push_plan_audit($audit_data)
    {
        if(PUSH_PLAN_SWITCH == false) return true;
        $push_data['data_list'] = json_encode($audit_data);
        $push_data = json_encode($push_data);
        $access_token = getOASystemAccessToken();

        //推送计划系统
        $url = getConfigItemByName('api_config', 'java_system_plan', 'push_audit_suggest');
        $url    = $url.'?access_token='.$access_token;
        $header = ['Content-Type: application/json'];
        $res = getCurlData($url, $push_data, 'POST',$header);
        if (!is_json($res)) throw new Exception('计划系统返回的不是json: '.$res);
        $result = json_decode($res, TRUE);

        if (isset($result['code']) && $result['code']!=200){
            throw new Exception('推送计划返回信息, '.$result['msg']);
        }elseif(isset($result['error'])){
            //throw new Exception('推送计划返回信息, '.isset($result['error_description'])?$result['error_description']:$result['message']);
            $msg = NULL;
            if( isset($result['error_description'])){

                $msg = $result['error_description'];
            }else{
                $msg = $result['message'];
            }
            throw new Exception('推送计划返回信息, '.$msg);
        }
    }

    /**
     * 备货单预计到货时间
     * @param   $sku   string   产品SKU
     * @return  estimate_time   预计供货时间
     **/
    public function get_scree_estimate_time_datas($skus){

        $estimate_time = $this->purchase_db->where("apply_remark",4)->where("status",PRODUCT_SCREE_STATUS_END)->where_in("sku",$skus)
            ->select("estimate_time,sku")->order_by("id DESC")->group_by("sku")->get('pur_product_scree')->result_array();

        if(empty($estimate_time)){

            return [];
        }

        return array_column($estimate_time,NULL,'sku');
    }

    /**
     * 备货单预计到货时间
     * @param   $sku   string   产品SKU
     * @return  estimate_time   预计供货时间
     **/
    public function get_scree_estimate_time($sku)
    {

        $estimate_time = $this->purchase_db->where("apply_remark",4)->where("status",PRODUCT_SCREE_STATUS_END)->where("sku",$sku)->select("estimate_time")->order_by("id DESC")->get('pur_product_scree')->row_array();
        return $estimate_time['estimate_time'];
    }


    /**
     * 需求单导入
     * @author Jolon
     * @param array $data 备货单数据
     * @param string  $sku_status_type 备货单导入SKU状态类型，1.正常限制状态，2.正常+允许导入停售状态
     * @return array
     * @throws Exception
     */
    public function import_suggest($data,$sku_status_type = 1){
        $this->load->model('warehouse/Warehouse_model');
        $this->load->model('warehouse/Logistics_type_model');
        $this->load->model('product/Product_line_model');

        $this->load->helper('status_product');
        //加载采购候补人模块
        $this->load->model('system/Product_line_buyer_config_model');

        $getProductStatus = getProductStatus();
        $warehouse_list = $this->Warehouse_model->get_warehouse_map();
        $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
        $warehouse_map = [];
        foreach ($warehouse_list as $key => $item){
            $item['purchase_type_id'] = explode(',',$item['purchase_type_id']);
            $warehouse_map[$item['warehouse_name']] = [
                'warehouse_code' => $item['warehouse_code'],
                'purchase_type_id' => $item['purchase_type_id'],
            ];
        }

        unset($warehouse_list);
        $logistics_type_list = array_column($logistics_type_list,'type_code','type_name');
        $product_line_ids = $this->Product_line_model->get_all_category(1763);
        $product_line_ids = trim($product_line_ids,',');
        $product_line_ids = explode(',',$product_line_ids);

        $return = ['code' => true,'data' => [],'message' => ''];

        if($sku_status_type == 2){
            $allow_import_product_status_list = [10,12,2,3,4,14,15,16,17,18,19,20,27,29,30,31,32,33,35,7];
        }else{
            $allow_import_product_status_list = [10,12,2,3,4,14,15,16,17,18,19,20,27,29,30,31,32,33,35];
        }

        $error_list = [];
        $now_time = date('Y-m-d H:i:s');
        //国家code映射关系
        $result = $this->purchase_db->select('en_abbr,cn_name')->get('pur_country')->result_array();
        $country_map = empty($result)?[]:array_column($result,'en_abbr','cn_name');
        $insert_list = [];

        if($data){
            foreach($data as $key => $value){
                if($key <= 1) continue;
                $purchase_type         = isset($value['A'])?trim($value['A']):'';
                $sku                   = isset($value['B'])?trim($value['B']):'';
                $changgui_amount       = isset($value['C'])?intval($value['C']):0;
                $huodong_amount        = isset($value['D'])?intval($value['D']):0;
                $purchase_amount       = $changgui_amount + $huodong_amount;
                $left_stock            = isset($value['E'])?intval($value['E']):0;//缺货数量
                $is_expedited          = (isset($value['F']) && trim($value['F']) == '是') ? PURCHASE_IS_EXPEDITED_Y : PURCHASE_IS_EXPEDITED_N;// 是否加急 1.否，2.是
                $param_is_drawback     = isset($value['G'])?trim($value['G']):'';//是否退税 FBA必填
                $is_fumigation         = isset($value['H'])?trim($value['H']):''; // 是否熏蒸
                $is_boutique           = isset($value['I'])?trim($value['I']):'';
                $warehouse_name        = isset($value['J'])?trim($value['J']):'';
                $destination_warehouse = isset($value['K'])?trim($value['K']):'';
                $destination_country   = isset($value['L'])?trim($value['L']):'';
                $logistics_type        = isset($value['M'])?trim($value['M']):'';
                $platform              = isset($value['N'])?trim($value['N']):'';
                $site                  = isset($value['O'])?trim($value['O']):'';
                $sales_group           = isset($value['P'])?trim($value['P']):'';
                $sales_name            = isset($value['Q'])?trim($value['Q']):'';
                $sales_account         = isset($value['R'])?trim($value['R']):'';
                $sales_note            = isset($value['S'])?trim($value['S']):'';

                if(empty($purchase_type) || empty($sku)) continue;
                if($purchase_amount <= 0){
                    $error_list[$key] = '备货总数量缺失';
                    continue;
                }

                if($purchase_type == '国内仓'){
                    $purchase_type_id = PURCHASE_TYPE_INLAND;
                }elseif($purchase_type == '海外仓'){
                    if(empty($is_boutique)){
                        $error_list[$key] = '业务线为海外仓时，是否海外精品必填。';
                        continue;
                    }
                    $purchase_type_id = PURCHASE_TYPE_OVERSEA;
                }elseif($purchase_type == 'FBA大货'){
                    $purchase_type_id = PURCHASE_TYPE_FBA_BIG;
                }elseif($purchase_type == 'FBA'){
                    $purchase_type_id = PURCHASE_TYPE_FBA;
                }else{
                    $error_list[$key] = '备货单业务线非[国内仓|海外仓|FBA|FBA大货]';
                    continue;
                }

                if(in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA]) && $is_fumigation == ''){
                    $error_list[$key] = '海外仓导入时，是否熏蒸不能为空';
                    continue;
                }

                if(in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG]) && empty($param_is_drawback)){
                    $error_list[$key] = '海外仓是否退税必填';
                    continue;
                }
                if(!isset($warehouse_map[$warehouse_name])){
                    $error_list[$key] = '仓库错误';
                    continue;
                }
                $warehouse_code = $warehouse_map[$warehouse_name]['warehouse_code'];
                if(in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG]) && $warehouse_code == 'HM_ZH'){
                    $purchase_type_id_tmp = 1;// 业务线=海外仓，采购仓库=国内转海外虚拟仓-虎门仓，采购员取值为：国内仓业务线对应的采购员
                }else{
                    $purchase_type_id_tmp = $purchase_type_id;
                }
                if($purchase_type_id == PURCHASE_TYPE_FBA_BIG){ // FBA大货时取海外仓业务员
                    $purchase_type_id_tmp = PURCHASE_TYPE_OVERSEA;
                }

                $sku_info = $this->purchase_db->select('a.sku_state_type,a.product_status,a.product_img_url,a.product_name,a.product_line_id,a.supplier_code,a.supplier_name,
                                    a.purchase_price,a.ticketed_point,a.create_id,a.create_user_name,a.is_drawback,b.is_cross_border,b.supplier_settlement,c.buyer_id,c.buyer_name,
                                    d.linelist_cn_name,a.productismulti,a.producttype,a.product_type')->from('product a')
                    ->join('pur_supplier b', 'a.supplier_code=b.supplier_code', 'left')
                    ->join('pur_supplier_buyer c', 'b.supplier_code=c.supplier_code and c.status=1 and c.buyer_type='.$purchase_type_id_tmp, 'left')
                    ->join('pur_product_line d', 'a.product_line_id=d.product_line_id', 'left')
                    ->where(['a.sku' => $sku])
                    ->get()
                    ->row_array();
                $is_drawback = $sku_info['is_drawback'];

                if(in_array($warehouse_name,['塘厦AM精品退税仓','虎门FBA虚拟仓','慈溪fba虚拟仓','慈溪FBA虚拟仓','慈溪海外虚拟仓'])){
                    $error_list[$key] = $warehouse_name.'已停用';
                    continue;
                }

                if($purchase_type_id == PURCHASE_TYPE_FBA){  // FBA是否退税必填

                    if ($param_is_drawback=='是'){
                        $is_drawback = 1;
                        $warehouse_name = 'FBA退税仓_塘厦';

                    }elseif($param_is_drawback=='否'){

                        $is_drawback = 0;
                        if (!in_array($warehouse_name,['FBA精品仓_塘厦','FBA虚拟仓_塘厦','FBA虚拟仓_虎门'])){
                            $error_list[$key] = '仓库错误';
                            continue;
                        }

                    }else{
                        $error_list[$key] = '是否退税非[是|否]';
                        continue;
                    }

                }

                if(in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){

                    if ($param_is_drawback=='是') {
                        $is_drawback = 1;
                    }elseif($param_is_drawback=='否'){
                        $is_drawback = 0;
                    }
                }


                if(empty($sku_info)){
                    $error_list[$key] = 'SKU信息缺失';
                    continue;
                }

                if($sku_info['product_status'] == 7 && $sku_status_type != 2){
                    $error_list[$key] = 'SKU已停售状态不能导入';
                    continue;
                }

                if ($sku_info['is_multi']==2 || $sku_info['product_type']==2 ){
                    $error_list[$key] = 'spu/捆绑销售不允许导入';
                    continue;
                }

                if(empty($sku_info['product_status'])){
                    $now_sku_status_text = '当前状态为空';
                }else{
                    $now_sku_status_text = '当前状态为';
                    $now_sku_status_text .= isset($getProductStatus[$sku_info['product_status']])?$getProductStatus[$sku_info['product_status']]:'未知状态';
                }
                if(in_array($sku_info['product_line_id'],$product_line_ids)){// 汽摩用品 产品线
                    //   if(!in_array($sku_info['product_status'],[8,9,10,12,2,3,4,14,15,16,27,29,30,31,32,33,21])){
                    if(in_array($sku_info['product_status'],[21])){
//                        $error_list[$key] = $now_sku_status_text.',SKU产品线是汽摩用品状态非可创建状态[待买样,待品检,拍摄中,修图中,编辑中,预上线,在售中,设计审核中,文案审核中,文案主管终审中,作图审核中,开发检查中,编辑中拍摄中,已编辑拍摄中,编辑中已拍摄,物流审核中]';
                        $error_list[$key] = $now_sku_status_text.',SKU产品线是汽摩用品状态不可创建状态[物流审核中]';
                    }
                }else{
                    if(empty($sku_info['product_status']) || !in_array($sku_info['product_status'],$allow_import_product_status_list)){
                        $error_list[$key] = $now_sku_status_text.',SKU状态非可创建状态[拍摄中,修图中,编辑中,预上线,在售中,设计审核中, 文案审核中,文案主管审核中,试卖编辑中,试卖在售中,试卖文案终审中,预上线拍摄中,作图审核中,开发检查中,编辑中拍摄中,已编辑拍摄中,编辑中已拍摄,新系统开发中]';
                        continue;
                    }
                }
                $warehouse_code = $warehouse_map[$warehouse_name]['warehouse_code'];
                $warehouse_business_line = $warehouse_map[$warehouse_name]['purchase_type_id'];

                if(!in_array($purchase_type_id,$warehouse_business_line)){
                    $error_list[$key] = '仓库与备货单业务线对应关系出错';
                    continue;
                }

                if(in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA])){// 只有海外仓才需要正常 目的仓必填
                    if(empty($destination_warehouse) || !isset($warehouse_map[$destination_warehouse])){
                        $error_list[$key] = '目的仓库错误';
                        continue;
                    }
                    $destination_warehouse_code = $warehouse_map[$destination_warehouse]['warehouse_code'];
                }else{
                    $destination_warehouse_code = $destination_warehouse?(isset($warehouse_map[$destination_warehouse]['warehouse_code'])??''):'';
                }

                if(in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){// 海外仓物流类型必填
                    if(empty($logistics_type) || !isset($logistics_type_list[$logistics_type])){
                        $error_list[$key] = '物流类型错误';
                        continue;
                    }
                    $logistics_type = $logistics_type_list[$logistics_type];// 转成物流类型代码
                }else{
                    $logistics_type = $logistics_type?(isset($logistics_type_list[$logistics_type])?$logistics_type_list[$logistics_type]:''):'';// 转成物流类型代码
                }


                if($purchase_type_id == PURCHASE_TYPE_FBA){// FBA 仓库验证
                    /*if(in_array($warehouse_code, ['TS','YB_TXAMTS','FBA_SZ_AA','AM'])){
                        if(in_array($warehouse_code, ['FBA_SZ_AA','AM'])){
                                  $is_drawback=0;
                        }else{
                            if(in_array($warehouse_code, ['TS','YB_TXAMTS'])) {
                               if($sku_info['is_drawback'] == 1){
                                   $is_drawback=1;
                               }else{
                                   $error_list[$key] = 'SKU非退税不允许导入';
                                    continue;
                               }
                            }
                        }
                    }else{
                        $error_list[$key] = 'SKU采购仓库只能是退税仓或塘厦AM精品退税仓,东莞仓FBA虚拟仓,塘厦AM精品仓';
                        continue;
                    }*/



//                    if($sku_info['is_drawback'] == 1 and  !in_array($warehouse_code, ['TS','YB_TXAMTS'])){
//                        $error_list[$key] = 'SKU退税但采购仓库不是退税仓或塘厦AM精品退税仓';
//                        continue;
//                    }
//                    if($sku_info['is_drawback'] != 1 and !in_array($warehouse_code, ['FBA_SZ_AA','AM'])){
//                        $error_list[$key] = 'SKU非退税但采购仓库不是东莞仓FBA虚拟仓或塘厦AM精品仓';
//                        continue;
//                    }



                    if(empty($platform)){
                        $error_list[$key] = 'FBA平台必填';
                        continue;
                    }
                    if(empty($site)){
                        $error_list[$key] = 'FBA站点必填';
                        continue;
                    }
                    if(empty($sales_group)){
                        $error_list[$key] = 'FBA销售分组必填';
                        continue;
                    }
                    if(empty($sales_name)){
                        $error_list[$key] = 'FBA销售名称必填';
                        continue;
                    }
                    if(empty($sales_account)){
                        $error_list[$key] = 'FBA销售账号必填';
                        continue;
                    }
                }

                if(in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG]) && $sku_status_type == 1){//海外仓和需求导入
                    if(empty($destination_country)){
                        $error_list[$key] = '目的国必填';
                        continue;
                    }
                    $country_code = $country_map[$destination_country]??'';
                    if(empty($country_code)){
                        $error_list[$key] = sprintf('[%s]未存在该国家编码',$destination_country);
                        continue;
                    }

                    if (!$this->product_model->check_sku_logistics($sku,$country_code)){//验证物流属性是否审核通过
                        $error_list[$key] = '物流属性审核中';
                        continue;
                    }
                }
                //查询该sku的最新一条已作废需求单(如果存在)的作废原因
                /*    $cancel_info = $this->purchase_db->select('cancel_reason')
                        ->where('sku',$sku)
                        ->where('suggest_status',SUGGEST_STATUS_CANCEL)
                        ->order_by('id','desc')
                        ->get('purchase_suggest')
                        ->row_array();*/

                $cancel_info =$this->get_noexpired_reason($sku);


                $add_data = [
                    'purchase_type_id'      => $purchase_type_id,
                    'sku'                   => $sku,
                    'demand_data'       => $purchase_amount,
                    'is_expedited'          => $is_expedited,//是否加急
                    'warehouse_code'        => $warehouse_code,
                    'warehouse_name'        => $warehouse_name,
                    'destination_warehouse' => $destination_warehouse_code,
                    'site'                  => $site,
                    'sales_group'           => $sales_group,
                    'sales_name'            => $sales_name,
                    'sales_account'         => $sales_account,
                    'sales_note2'           => $sales_note,
                    'platform'              => $platform,
                    'logistics_type'        => $logistics_type,
                    'left_stock'            => empty($left_stock)?0:$left_stock,//缺货数量
                    'transformation'        => $sku_info['sku_state_type']
                ];


                if(!empty($is_boutique)){
                    $add_data["is_boutique"] = $is_boutique == "是"?1:0;
                }

                if($purchase_type_id == PURCHASE_TYPE_INLAND){ // 国内仓都是不含税单价
                    $purchase_unit_price = format_two_point_price($sku_info['purchase_price']);// 含税单价（实际未税单价）
                    $purchase_name = 'HKYB';
                }else{
                    if($is_drawback == 1){
                        $purchase_unit_price = format_two_point_price($sku_info['purchase_price'] * ( 1 + $sku_info['ticketed_point']/100));// 含税单价（实际含税单价）
                        $purchase_name = 'SZYB';
                    }else{
                        $purchase_unit_price = format_two_point_price($sku_info['purchase_price']);// 含税单价（实际未税单价）
                        $purchase_name = 'HKYB';
                    }
                }
                $add_data['sales_note'] = $this->get_sales_note($sku);//根据sku查询七天内最近的备注,在生成备货单时添加备注

                // $line_info                         = $this->get_one_level_product_line($sku_info['product_line_id']);
                //  $line_info                         = $this->get_product_line_by_id($sku_info['product_line_id']);




                $pro_line_cache = $this->product_line_model->cache_product_line();
                $product_line = SetAndNotEmpty($pro_line_cache, 'line') ?  $pro_line_cache['line']: [];
                $product_line_title = SetAndNotEmpty($pro_line_cache, 'master') ?  $pro_line_cache['master']: [];









                //$add_data['demand_status']        = SUGGEST_STATUS_NOT_FINISH;
                $add_data['product_img_url']       = !empty($sku_info['product_img_url']) ? $sku_info['product_img_url'] : '';
                $add_data['product_name']          = !empty($sku_info['product_name']) ? $sku_info['product_name'] : '';
                $add_data['two_product_line_id']   = !empty($sku_info['product_line_id']) ? $sku_info['product_line_id'] : '';
                $add_data['two_product_line_name'] = !empty($sku_info['linelist_cn_name']) ? $sku_info['linelist_cn_name'] : '0';
                $add_data['product_line_id']       = isset($product_line[$sku_info['product_line_id']]) ? $product_line[$sku_info['product_line_id']] : 0;
                $add_data['product_line_name']     = isset($product_line_title[$sku_info['product_line_id']]) ? $product_line_title[$sku_info['product_line_id']] : '';
                $add_data['supplier_code']         = !empty($sku_info['supplier_code']) ? $sku_info['supplier_code'] : '';
                $add_data['supplier_name']         = !empty($sku_info['supplier_name']) ? $sku_info['supplier_name'] : '';
                $add_data['purchase_unit_price']   = $purchase_unit_price;
                $add_data['purchase_total_price']  = $purchase_unit_price * $add_data['demand_data'];
                $add_data['developer_id']          = !empty($sku_info['create_id']) ? $sku_info['create_id'] : 0;
                $add_data['developer_name']        = !empty($sku_info['create_user_name']) ? $sku_info['create_user_name'] : '';
                $add_data['extra_handle'] = 0;
                if($is_fumigation == "熏蒸"){
                    $add_data['extra_handle'] = 1;
                }elseif($is_fumigation == "不熏蒸"){
                    $add_data['extra_handle'] = 2;
                }
                if (empty($sku_info['buyer_id']) && empty($sku_info['buyer_name'])) {
                    //获取采购候补人数据
                    $buyer_info = $this->Product_line_buyer_config_model->get_buyer($add_data['product_line_id'], $purchase_type_id);
                    $add_data['buyer_id'] = $buyer_info['buyer_id'];
                    $add_data['buyer_name'] = $buyer_info['buyer_name'];
                } else {
                    $add_data['buyer_id'] = $sku_info['buyer_id'];
                    $add_data['buyer_name'] = $sku_info['buyer_name'];
                }
                $add_data['is_cross_border']       = !empty($sku_info['is_cross_border']) ? $sku_info['is_cross_border'] : 0;
                $add_data['account_type']          = !empty($sku_info['supplier_settlement']) ? $sku_info['supplier_settlement'] : 0;//结算方式

                if($purchase_type_id==PURCHASE_TYPE_INLAND){
                    $add_data['is_drawback']       = 0;//业务线为国内仓的，是否退税，也全部都默认为否#25738
                }else{
                    // $add_data['is_drawback']       = !empty($sku_info['is_drawback']) ? $sku_info['is_drawback'] : 0;
                    $add_data['is_drawback']         = $is_drawback;
                }
                //采购主体界定
                //$purchase_name = $this->purchase_order_model->get_subject_title($is_drawback,$sku_info['supplier_code']);
                $add_data['purchase_name']         = isset($purchase_name) ? $purchase_name : '';

                $add_data['cancel_reason']         = !empty($cancel_info['cancel_reason'])?$cancel_info['cancel_reason']:'';//!empty($cancel_info)?$cancel_info['cancel_reason']:'';
                $add_data['cancel_reason_category']         = !empty($cancel_info['cancel_reason_category'])?$cancel_info['cancel_reason_category']:0;//!empty($cancel_info)?$cancel_info['cancel_reason']:'';
                $add_data['create_user_id']        = getActiveUserId();
                $add_data['create_user_name']      = getActiveUserName();
                $add_data['create_time']           = $now_time;
                //$add_data['audit_status']          = 0;
                $add_data['expiration_time']       = '2090-01-01 00:00:00';// 不过期
                $add_data['is_overseas_first_order'] = 0;
                $estimate_time =  $this->get_scree_estimate_time($sku);

                if( !empty($estimate_time) ) {
                    $add_data['estimate_time'] = $estimate_time;
                }else{
                    $add_data['estimate_time'] = '0000-00-00 00:00:00';
                }

                // 获取导入的SKU是否为新品
                $skuIsNew = $this->product_model->getProductNew($sku);
                if(True == $skuIsNew){
                    // SKU 为新品
                    $add_data['is_new'] = 1;
                }else{
                    $add_data['is_new'] = 0;
                }

                // 判断导入SKU 是否为海外仓首单
                if( in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){

                    $is_overseas_first_order = $this->product_model->is_overseas_first_order($sku);

                    if( $is_overseas_first_order == True ) {
                        $add_data['is_overseas_first_order'] = 1;
                    }else{
                        $add_data['is_overseas_first_order']=0;
                    }
                }

                $insert_list[] = $add_data;

                if (empty($add_data['sales_note'])){
                    $delete_res = $this->delete_sales_note_and_cancel_reason($sku);
                    if (empty($delete_res)) throw new Exception($sku.'清空备注失败');
                }
            }

            if(isset($error_list) && $error_list){
                $return['code'] = false;
                $return['data'] = $error_list;
                return $return;
            }

            try{
                $this->purchase_db->trans_begin();

                foreach($insert_list as &$value){
                    $value['demand_number'] = get_prefix_new_number('RD');
                    $demand_repeat = $this->judge_sku_repeat($value['sku'],$value['purchase_type_id'],$value['demand_number']);

                    $sku_repeatflag = 1; // 标记为重复
                    if($demand_repeat == "no_repetition") {
                        $sku_repeatflag =2;
                    }
                    $value['demand_repeat'] = $sku_repeatflag;
                }
                //print_r($insert_list);die();
                $res = $this->purchase_db->insert_batch("purchase_demand",$insert_list);
                if($res){

                    $this->purchase_db->trans_commit();
                    $return['code']     = true;
                    $return['message']  = '';
                }else{
                    throw new Exception('数据插入失败');
                }
            }catch(Exception $e){
                $return['code']     = false;
                $return['message']  = $e->getMessage();
                $this->purchase_db->trans_rollback();
            }

            return $return;
        }else{
            $return['code']     = false;
            $return['message']  = '数据缺失';
            return $return;
        }
    }
    /**
     * 更新需求单仓库
     * @author harvin 2019-5-24
     * @param array $demand_number_list
     * @param type $warehouse_code
     * @return array
     */
    public function update_suggest_warecahouse(array $demand_number_list,$warehouse_code){
        $this->load->helper('status_order');
        if(empty($demand_number_list) || !is_array($demand_number_list)){
            $return['code']=false;
            $return['message']='未找到备货单号';
            return $return;
        }
        if(empty($warehouse_code)){
            $return['code']=false;
            $return['message']='仓库编码不存在';
            return $return;
        }
        $data=[
            'warehouse_code'=>$warehouse_code,
            'warehouse_name'=>getWarehouse($warehouse_code),
            'is_push'=>0,
        ];
        $result=$this->purchase_db
            ->where_in('demand_number',$demand_number_list)
            ->update($this->table_name,$data);
        if(empty($result)){
            $return['code']=false;
            $return['message']='更新需求单仓库失败';
        }else{
            $return['code']=TRUE;
            $return['message']='更新成功';
        }
        return $return;
    }

    /**
     * @desc 推送物流需求单数据
     * @author Jeff
     * @Date 2019/4/2 14:49
     * @param $audit_data
     * @throws Exception
     */
    public function push_logistics_suggest($suggest_data)
    {
//        if(CG_ENV == 'dev' || PUSH_PLAN_SWITCH == false) return true;
        //推送计划系统
        $url = getConfigItemByName('api_config', 'logistics_rule', 'push_suggest');

        $post_data['data'] = json_encode($suggest_data);

        $res = getCurlData($url, $post_data, 'POST');

        apiRequestLogInsert(
            [
                'record_type'      => '推送需求单到物流系统',
                'post_content'     => json_encode($suggest_data),
                'response_content' => $res,
                'create_time'      => date("Y-m-d H:i:s"),
            ]
        );

        if (!is_json($res)) throw new Exception('物流系统返回的不是json:'.$res);

        $result = json_decode($res, TRUE);
        if ($result['status']!=true){
            throw new Exception('物流系统返回信息, '.$result['message']);
        }
        return $result;
    }

    /**
     * 接收ERP系统推送的备货单数据
     * @param $data
     * @return array
     * @author Sinder
     * @date 2019-05-29
     */
    public function receive_suggest_data_from_erp($data){
        $this->load->model('warehouse/Warehouse_model');
        $this->load->model('warehouse/Logistics_type_model');
        $this->load->model('product/Product_line_model');
        $this->load->helper('status_product');
        //加载采购候补人模块
        $this->load->model('system/Product_line_buyer_config_model');

        $getProductStatus = getProductStatus();
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
        $logistics_type_list = array_column($logistics_type_list,'type_code','type_name');
        $warehouse_list = array_column($warehouse_list,'warehouse_code','warehouse_name');
//        $product_line_ids = $this->Product_line_model->get_all_category(1763);
//        $product_line_ids = trim($product_line_ids,',');
//        $product_line_ids = explode(',',$product_line_ids);

        $return = ['code' => true,'data' => [],'message' => ''];

        $insert_list = [];
        $error_list = [];
        $success_list = [];
        $now_time = date('Y-m-d H:i:s');

        $cache_supplier_payment_info_list = [];// 缓存变量

        if($data){

            $isNewData = $isOverseasFirstOrder=[];
            $receiveSkus = array_column( $data,"sku");
            $erp_id_list = array_column( $data,"erp_id");

            // 一次查出所有存在的记录
            $erp_id_history_info = $this->purchase_db->select('id,erp_id,demand_number')
                ->from("purchase_demand")
                ->where_in('erp_id',$erp_id_list)
                ->get()
                ->result_array();
            $erp_id_history_info = array_column($erp_id_history_info,'demand_number','erp_id');

            // 一次性查出所有记录
            $sales_note_list     = $this->get_sales_note($receiveSkus);//根据sku查询七天内最近的备注,在生成备货单时添加备注

            // 一次性查出所有记录
            $cancel_info_list    = $this->get_noexpired_reason($receiveSkus);// 获取七天内未过期的作废原因

            if( !empty($receiveSkus )){

                $receiveSkusData = $this->purchase_db->from($this->table_product_name)->where_in("sku",$receiveSkus)->select("sku,is_new,is_overseas_first_order")->get()->result_array();
                if( !empty($receiveSkusData)){
                    $isNewData = array_map(function($data){

                        if( $data['is_new'] == 1){
                            return $data['sku'];
                        }
                    },$receiveSkusData);
                    $isNewData = array_filter($isNewData);
                    $isOverseasFirstOrder = array_map(function($data){

                        if( $data['is_overseas_first_order'] == 1){

                            return $data['sku'];
                        }
                    },$receiveSkusData);
                    $isOverseasFirstOrder = array_filter($isOverseasFirstOrder);
                }

            }
            $insertData = [];
            $timekey = date("Ymd",time());
            $erp_ids = array_column($data,"erp_id");

            //$zsetDatas = $this->rediss->lrange($timekey,0,-1);


            foreach($data as $key => $value){
                $zsetDatas = $this->rediss->getData($value['erp_id']);

                if(!empty($zsetDatas)){
                    //$error_list[$value['erp_id']] = "数据已经存在";
                    $success_list[$value['erp_id']] = $zsetDatas;

                    continue;
                }
                $purchase_type         = isset($value['purchase_type'])?trim($value['purchase_type']):'';
                $sku                   = isset($value['sku'])?trim($value['sku']):'';
                $purchase_amount        = isset($value['purchase_amount'])?intval($value['purchase_amount']):0;
                $is_expedited          = (isset($value['is_expedited']) and trim($value['is_expedited']) == '2') ? PURCHASE_IS_EXPEDITED_Y : PURCHASE_IS_EXPEDITED_N;// 是否加急 1.否，2.是
                $warehouse_name        = isset($value['warehouse_name'])?trim($value['warehouse_name']):'';
                $destination_warehouse = isset($value['transfer_warehouse'])?trim($value['transfer_warehouse']):'';
                $logistics_type        = isset($value['logistics_type'])?trim($value['logistics_type']):'';
                $platform              = isset($value['platform'])?trim($value['platform']):'';
                $site                  = isset($value['site'])?trim($value['site']):'';
                $sales_group           = isset($value['sales_group'])?trim($value['sales_group']):'';
                $sales_name            = isset($value['sales_name'])?trim($value['sales_name']):'';
                $sales_account         = isset($value['sales_account'])?trim($value['sales_account']):'';
                $sales_note            = isset($value['sales_note'])?trim($value['sales_note']):'';
                $erp_id                = isset($value['erp_id'])?trim($value['erp_id']):'';
               // $demand_date           =isset($value['demand_date'])?trim($value['demand_date']):'';
                $demand_date           = date("Y-m-d H:i:s",time());
                $create_user_id        =isset($value['create_id'])?trim($value['create_id']):'';
                $create_user_name      =isset($value['create_user'])?trim($value['create_user']):'';
                $is_drawback           =isset($value['is_drawback'])?trim($value['is_drawback']):'';
                $is_boutique           = isset($value['is_boutique'])?trim($value['is_boutique']):'';
                $left_stock           = isset($value['left_stock'])?$value['left_stock']:0;
                if(empty($purchase_type) or empty($sku) or empty($erp_id)){
                    $error_list[$erp_id] = '备货单业务线，sku或erpID缺失';
                    continue;
                }
                if($purchase_amount <= 0){
                    $error_list[$erp_id] = '备货总数量缺失';
                    continue;
                }

                if($purchase_type == '国内仓'){
                    $purchase_type_id = PURCHASE_TYPE_INLAND;
                }elseif($purchase_type == '海外仓'){
                    $purchase_type_id = PURCHASE_TYPE_OVERSEA;
                }elseif($purchase_type == 'FBA大货'){
                    $purchase_type_id = PURCHASE_TYPE_FBA_BIG;
                }elseif($purchase_type == 'FBA'){
                    $purchase_type_id = PURCHASE_TYPE_FBA;
                }else{
                    $error_list[$erp_id] = '备货单业务线非[国内仓|海外仓|FBA|FBA大货]';
                    continue;
                }

                if(isset($erp_id_history_info[$erp_id])){
                    //$error_list[$erp_id] = "已经存在";//重复的接口返回成功,不写入
                    $success_list[$erp_id] = $erp_id_history_info[$erp_id];

                    continue;
                }

                $buyer_type_temp = $purchase_type_id;
                if($buyer_type_temp == PURCHASE_TYPE_FBA_BIG){
                    $buyer_type_temp = PURCHASE_TYPE_OVERSEA;
                }

                $sku_info = $this->purchase_db->select('a.product_status,a.product_img_url,a.product_name,a.product_line_id,a.supplier_code,a.supplier_name,
                                    a.purchase_price,a.ticketed_point,a.create_id,a.create_user_name,a.is_drawback,b.is_cross_border,b.supplier_settlement,c.buyer_id,c.buyer_name,
                                    d.linelist_cn_name')->from('product a')
                    ->join('supplier b', 'a.supplier_code=b.supplier_code', 'left')
                    ->join('supplier_buyer c', 'b.supplier_code=c.supplier_code and c.status=1 and c.buyer_type="'.$buyer_type_temp.'"', 'left')
                    ->join('product_line d', 'a.product_line_id=d.product_line_id', 'left')
                    ->where(['a.sku' => $sku])
                    ->get()
                    ->row_array();
                if(empty($sku_info)){
                    $error_list[$erp_id] = 'SKU信息缺失';
                    continue;
                }
                if($is_drawback!=$sku_info['is_drawback']){
                    $sku_info['is_drawback']=$is_drawback;
                }
                /*if($sku_info['product_status'] == 7){
                    $error_list[$erp_id] = 'SKU已停售状态不能导入';
                    continue;
                }*/

                if(empty($sku_info['product_status'])){
                    $now_sku_status_text = '当前状态为空';
                }else{
                    $now_sku_status_text = '当前状态为';
                    $now_sku_status_text .= isset($getProductStatus[$sku_info['product_status']])?$getProductStatus[$sku_info['product_status']]:'未知状态';
                }
                /*if(in_array($sku_info['product_line_id'],$product_line_ids)){// 汽摩用品 产品线
                    //   if(!in_array($sku_info['product_status'],[8,9,10,12,2,3,4,14,15,16,27,29,30,31,32,33,21])){
                    if(in_array($sku_info['product_status'],[21])){
//                        $result_list[$erp_id] = $now_sku_status_text.',SKU产品线是汽摩用品状态非可创建状态[待买样,待品检,拍摄中,修图中,编辑中,预上线,在售中,设计审核中,文案审核中,文案主管终审中,作图审核中,开发检查中,编辑中拍摄中,已编辑拍摄中,编辑中已拍摄,物流审核中]';
                        $error_list[$erp_id] = $now_sku_status_text.',SKU产品线是汽摩用品状态不可创建状态[物流审核中]';
                    }
                }else{
                    if(empty($sku_info['product_status']) or !in_array($sku_info['product_status'],[10,12,2,3,4,14,15,16,17,18,19,20,27,29,30,31,32,33,35])){
                        $error_list[$erp_id] = $now_sku_status_text.',SKU状态非可创建状态[拍摄中,修图中,编辑中,预上线,在售中,设计审核中, 文案审核中,文案主管审核中,试卖编辑中,试卖在售中,试卖文案终审中,预上线拍摄中,作图审核中,开发检查中,编辑中拍摄中,已编辑拍摄中,编辑中已拍摄,新系统开发中]';
                        continue;
                    }
                }*/
                if(!isset($warehouse_list[$warehouse_name])){
                    $error_list[$erp_id] = '仓库错误';
                    continue;
                }
                $warehouse_code = isset($warehouse_list[$warehouse_name])?$warehouse_list[$warehouse_name]:'';
                if(in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA])){// 只有海外仓才需要正常 目的仓必填
                    if(empty($destination_warehouse) or !isset($warehouse_list[$destination_warehouse])){
                        $error_list[$erp_id] = '目的仓库错误';
                        continue;
                    }
                    $destination_warehouse_code = $warehouse_list[$destination_warehouse];
                }else{
                    $destination_warehouse_code = $destination_warehouse?(isset($warehouse_list[$destination_warehouse])?$warehouse_list[$destination_warehouse]:''):'';
                }

                if(in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){// 海外仓 物流类型必填
                    if(empty($logistics_type) or !isset($logistics_type_list[$logistics_type])){
                        $error_list[$erp_id] = '物流类型错误';
                        continue;
                    }
                    $logistics_type = $logistics_type_list[$logistics_type];// 转成物流类型代码
                }else{
                    $logistics_type = $logistics_type?(isset($logistics_type_list[$logistics_type])?$logistics_type_list[$logistics_type]:''):'';// 转成物流类型代码
                }

                if($purchase_type_id == PURCHASE_TYPE_FBA){// FBA 仓库验证
//                    if($sku_info['is_drawback'] == 1 and $warehouse_code != 'TS'){
//                        $warehouse_code='TS';
//                        $warehouse_name="退税仓";
//                    }
//                    if($sku_info['is_drawback'] != 1 and $warehouse_code != 'FBA_SZ_AA'){
//                        $warehouse_code='FBA_SZ_AA';
//                        $warehouse_name="东莞仓FBA虚拟仓";
//                    }
                    if(empty($platform)){
                        $error_list[$erp_id] = 'FBA平台必填';
                        continue;
                    }
                    if(empty($site)){
                        $error_list[$erp_id] = 'FBA站点必填';
                        continue;
                    }
                    if(empty($sales_group)){
                        $error_list[$erp_id] = 'FBA销售分组必填';
                        continue;
                    }
                    if(empty($sales_name)){
                        $error_list[$erp_id] = 'FBA销售名称必填';
                        continue;
                    }
                    if(empty($sales_account)){
                        $error_list[$erp_id] = 'FBA销售账号必填';
                        continue;
                    }
                }





                $add_data = [
                    'purchase_type_id'      => $purchase_type_id,
                    'sku'                   => $sku,
                    'demand_data'           => $purchase_amount,
                    'is_expedited'          => $is_expedited,
                    'warehouse_code'        => $warehouse_code,
                    'warehouse_name'        => $warehouse_name,
                    'destination_warehouse' => $destination_warehouse_code,
                    'site'                  => $site,
                    'sales_group'           => $sales_group,
                    'sales_name'            => $sales_name,
                    'sales_account'         => $sales_account,
                    'sales_note2'           => $sales_note,
                    'platform'              => $platform,
                    'logistics_type'        => $logistics_type,
                    'is_erp'                => 1,
                    'erp_id'                => $erp_id,
                    'is_boutique'           =>$is_boutique,
                    'is_new'                => in_array($sku,$isNewData)?1:0,
                    'left_stock'            => $left_stock,
                    'is_overseas_boutique'  => isset($value['is_overseas_boutique'])?$value['is_overseas_boutique']:0, // 是否海外精品
                    'is_distribution'       => isset($value['is_distribution'])?$value['is_distribution']:1,


                ];
                if(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){
                    $add_data['is_overseas_first_order'] = in_array($sku,$isOverseasFirstOrder)?1:0;
                }else {
                    $add_data['is_overseas_first_order'] = 0;

                }
                if($purchase_type_id == PURCHASE_TYPE_INLAND){// 国内仓都是不含税单价
                    $purchase_unit_price = format_two_point_price($sku_info['purchase_price']);// 含税单价（实际未税单价）
                    $purchase_name = 'HKYB';
                }else{
                    if($sku_info['is_drawback'] == 1){
                        $purchase_unit_price = format_two_point_price($sku_info['purchase_price'] * ( 1 + $sku_info['ticketed_point']/100));// 含税单价（实际含税单价）
                        //采购主体
                        $purchase_name = 'SZYB';
                    }else{
                        $purchase_unit_price = format_two_point_price($sku_info['purchase_price']);// 含税单价（实际未税单价）
                        $purchase_name = 'HKYB';

                    }
                }

                $line_info      = $this->get_product_line_by_id($sku_info['product_line_id']);
                $cancel_info    = isset($cancel_info_list[$sku])?$cancel_info_list[$sku]:[];

                //根据sku查询七天内最近的备注,在生成备货单时添加备注
                if(isset($sales_note_list[$sku])){
                    $add_data['sales_note'] = $sales_note_list[$sku];
                }else{
                    $add_data['sales_note'] = '';
                }

                $demand_repeat = $this->judge_sku_repeat($value['sku'],$purchase_type_id);
                if($demand_repeat == "no_repetition"){
                    $add_data['demand_repeat'] = 2;// 标记为重复=否
                }else{
                    $add_data['demand_repeat'] = 1;// 标记为重复=是
                }

                $add_data['demand_status']         = SUGGEST_STATUS_NOT_FINISH;
                $add_data['product_img_url']       = !empty($sku_info['product_img_url']) ? $sku_info['product_img_url'] : '';
                $add_data['product_name']          = !empty($sku_info['product_name']) ? $sku_info['product_name'] : '';
                $add_data['two_product_line_id']   = !empty($sku_info['product_line_id']) ? $sku_info['product_line_id'] : '';
                $add_data['two_product_line_name'] = !empty($sku_info['linelist_cn_name']) ? $sku_info['linelist_cn_name'] : '0';
                $add_data['product_line_id']       = !empty($line_info['id'])?$line_info['id']:'';
                $add_data['product_line_name']     = !empty($line_info['title'])?$line_info['title']:'';
                $add_data['supplier_code']         = !empty($sku_info['supplier_code']) ? $sku_info['supplier_code'] : '';
                $add_data['supplier_name']         = !empty($sku_info['supplier_name']) ? $sku_info['supplier_name'] : '';
                $add_data['purchase_unit_price']   = $purchase_unit_price;
                $add_data['purchase_total_price']  = $purchase_unit_price * $add_data['demand_data'];
                $add_data['developer_id']          = !empty($sku_info['create_id']) ? $sku_info['create_id'] : 0;
                $add_data['developer_name']        = !empty($sku_info['create_user_name']) ? $sku_info['create_user_name'] : '';

                if (empty($sku_info['buyer_id'])) {//获取采购候补人数据
                    $buyer_info = $this->Product_line_buyer_config_model->get_buyer($line_info['id'], $purchase_type_id);
                    $add_data['buyer_id']   = $buyer_info['buyer_id'];
                    $add_data['buyer_name'] = $buyer_info['buyer_name'];
                } else {
                    $add_data['buyer_id']   = $sku_info['buyer_id'];
                    $add_data['buyer_name'] = $sku_info['buyer_name'];
                }

                $add_data['is_cross_border']       = !empty($sku_info['is_cross_border']) ? $sku_info['is_cross_border'] : 0;
                $add_data['account_type']          = !empty($sku_info['supplier_settlement']) ? $sku_info['supplier_settlement'] : 0;//结算方式

                if($purchase_type_id==PURCHASE_TYPE_INLAND){
                    $add_data['is_drawback']       = 0;//业务线为国内仓的，是否退税，也全部都默认为否#25738
                }else{
                    $add_data['is_drawback']       = !empty($sku_info['is_drawback']) ? $sku_info['is_drawback'] : 0;
                }
                $add_data['purchase_name']         = isset($purchase_name) ? $purchase_name : '';//采购主体
                $add_data['cancel_reason']         = !empty($cancel_info['cancel_reason'])?$cancel_info['cancel_reason']:'';//!empty($cancel_info)?$cancel_info['cancel_reason']:'';
                $add_data['cancel_reason_category']= !empty($cancel_info['cancel_reason_category'])?$cancel_info['cancel_reason_category']:0;//!empty($cancel_info)?$cancel_info['cancel_reason']:'';
                $add_data['create_user_id']        = getActiveUserId();
                $add_data['create_user_name']      = getActiveUserName();
                $add_data['create_time']           = $demand_date;
                $add_data['source_from']           = 1;
                $add_data['create_user_id']        = $create_user_id;
                $add_data['create_user_name']      = $create_user_name;
                $add_data['expiration_time']       = '2090-01-01 00:00:00';// 不过期
                $add_data['demand_number']         = get_prefix_new_number('RD');
                $add_data['demand_name']           = isset($value['demand_name'])?$value['demand_name']:'';// 需求单类型
                $add_data['demand_name_id']        = isset($value['demand_name_id'])?$value['demand_name_id']:0;
                $add_data['temp_container']        = (isset($value['temp_container']) && !empty($value['temp_container']))?$value['temp_container']:'';
                $add_data['shipment_type']         = $add_data['demand_name_id'] == 2? 1 : 2;


                // 是否熏蒸
                if(isset($value['is_fumigation']) && $value['is_fumigation'] == 1){
                    $add_data['extra_handle'] = 1;
                }elseif(isset($value['is_fumigation']) && $value['is_fumigation'] == 2){
                    $add_data['extra_handle'] = 2;
                }else{
                    $add_data['extra_handle'] = 0;
                }


                if ($add_data['is_drawback'] == 1) {//是退税 对应所有的业务线
                    $purchase_type_id = 0;
                } elseif (in_array($purchase_type_id, [PURCHASE_TYPE_INLAND, PURCHASE_TYPE_FBA, PURCHASE_TYPE_PFB, PURCHASE_TYPE_PFH])) {//FBA/国内 一样
                    $purchase_type_id = 1;
                }


                // 只读取供应商结算信息，并缓存下次使用
                if(isset($cache_supplier_payment_info_list[$add_data['supplier_code']])){
                    $supplier_payment_info = $cache_supplier_payment_info_list[$add_data['supplier_code']];
                }else{
                    $supplier_payment_info = $this->Supplier_payment_info_model->supplier_payment_info($add_data['supplier_code']); // 供应商信息
                    $supplier_payment_info = $supplier_payment_info[$add_data['is_drawback']][$purchase_type_id]??[];
                    $cache_supplier_payment_info_list[$add_data['supplier_code']] = $supplier_payment_info;
                }

                // 如果支付方式为线上境内或者境外支付，采购来源为合同单
                if(!empty($supplier_payment_info['payment_method']) && in_array($supplier_payment_info['payment_method'],[PURCHASE_PAY_TYPE_PUBLIC,PURCHASE_PAY_TYPE_PRIVATE])){
                    $add_data['source'] = SOURCE_COMPACT_ORDER;
                }else {
                    $add_data['source'] = SOURCE_NETWORK_ORDER;
                }

                $sucessDatas[] = $add_data;

                $success_list[$erp_id] = true;

                $res = $this->purchase_db->insert("purchase_demand",$add_data);
                $this->rediss->setData($add_data['erp_id'],$add_data['demand_number'],86400);//$timekey

                if($res){
                    $success_list = [];
                    foreach($sucessDatas as $add_datas_key => $add_datas_values){
                        $success_list[$add_datas_values['erp_id']] = $add_datas_values['demand_number'];
                    }
                }else{
                    $error_list[] =$erp_id;
                }

                //$insertData[] = $add_data['demand_number'];
                //$insert_list[] = $add_data;
            }

            if(empty($insert_list)){
                $return['code'] = true;
                $return['data']['success_list'] = $success_list;
                $return['data']['error_list'] = $error_list;
                return $return;
            }

            try{
                // $this->purchase_db->trans_begin();
                // $res = $this->purchase_db->insert_batch("purchase_demand",$insert_list);
                //$this->purchase_db->trans_commit();
                $res = TRUE;

            }catch(Exception $e){
                $error_list = array_merge($error_list,$success_list);
                //$this->purchase_db->trans_rollback();
            }

            foreach($error_list as $key_id => $value_id){
                if($value_id === true){
                    $error_list[$key_id] = '数据写入失败';
                }
            }
            $return['code'] = true;
            $return['data']['success_list'] = $success_list;
            $return['data']['error_list'] = $error_list;
            return $return;

        }else{
            $return['code']     = false;
            $return['message']  = '数据缺失';
            return $return;
        }

    }

    /**
     * @desc 接收ERP同步请求（ERP 获取需求单在采购系统下单情况）
     * @param array $erp_id_list
     * @author Sinder
     * @date 2019-05-29
     * @return array
     */
    public function sync_suggest_to_erp($erp_id_list=[]){
        $return = [
            'success_list'  => [],
            'error_list'    => [],
        ];

        $success_list = [];
        $error_list = [];
        $this->load->model('purchase_suggest_model');



        $erp_list = $this->purchase_db->select('erp_id,demand_number')
            ->from('purchase_demand')
            ->where_in('erp_id', $erp_id_list)
            ->where('is_erp', 1)
            ->get()
            ->result_array();
        $suggestDemands = array_column($erp_list,'demand_number');
        $suggest_to_erp = array_column($erp_list,NULL,'demand_number');
        $erp_list = array_column($erp_list, 'erp_id');
        /*$erp_list_no = array_diff($erp_id_list, $erp_list);

        if ($erp_list_no) {
            foreach($erp_list_no as $erp_id){
                $error_list[$erp_id] = '无备货单信息';
            }
        }

        if (!$erp_list){
            $return['error_list'] = $error_list;
            return $return;
        }*/

        $this->load->model('warehouse/Warehouse_model');
        $this->load->model('warehouse/Logistics_type_model');
        $this->load->helper('status_product');
        $this->load->helper('status_order');
        $this->load->model('purchase/purchase_order_remark_model');


        $order_status_list   = getPurchaseStatus();
        $suggest_status_list = getSuggestStatus();
        $pay_status_list     = getPayStatus();
        $warehouse_list      = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list      = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
        $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
        $logistics_type_list = array_column($logistics_type_list, 'type_name', 'type_code');

        //原因类别
        $reason_cate = $this->purchase_db->select('*')->from('reason_config')->get()->result_array();
        $reason_cate = array_column($reason_cate,'reason_name','id');

        $purchase_info_list = $this->purchase_db->select(
            'ps.sku,ps.purchase_type_id, ps.product_name, ps.create_time, ps.product_category_name,ps.purchase_amount,
            ps.is_expedited, ps.warehouse_code, ps.destination_warehouse, ps.logistics_type, 
            ps.platform, ps.site,ps.cancel_reason,ps.cancel_reason_category,ps.audit_note,
            ps.sales_group, ps.sales_name, ps.sales_account,  ps.sales_note2,ps.is_abnormal,ps.sales_note,
            ps.demand_number, ps.suggest_status,ps.supplier_code, ps.supplier_name,demand.erp_id, ps.audit_status,'

            .'psm.purchase_number,demand.demand_number as ddemand_number,demand.purchase_type_id as dpurchase_type_id,'

            .'demand.demand_status as ddemand_status,demand.cancel_reason_category,po.buyer_name,po.purchase_order_status,po.pay_status, po.plan_product_arrive_time,po.waiting_time,'

            .'poi.id as items_id,poi.confirm_amount, ps.is_drawback,  poi.create_time as place_time,
            poi.upselft_amount,poi.receive_amount,'

            .'pwr.instock_qty,pwr.arrival_qty, pwr.bad_qty,'

            .'popt.express_no'
        )
            ->from('purchase_demand as demand')
            ->join('purchase_suggest ps',"demand.suggest_demand=ps.demand_number","LEFT")
            ->join('purchase_suggest_map psm', 'ps.demand_number = psm.demand_number and ps.sku = psm.sku', 'left')
            ->join('purchase_order po', 'po.purchase_number=psm.purchase_number', 'left')
            ->join('purchase_order_items poi', 'po.purchase_number=poi.purchase_number and ps.sku=poi.sku', 'left')
            ->join('warehouse_results_main pwr', 'pwr.items_id=poi.id', 'left')
            ->join('purchase_order_pay_type popt', 'popt.purchase_number=po.purchase_number', 'left')
            ->where('demand.is_erp',1)
            ->where_in('demand.erp_id',$erp_id_list)
            ->get()->result_array();

        $record_number = '';
        if (count($purchase_info_list) > 0){
            foreach ($purchase_info_list as $k=>$purchase_info) {
                // $record_number .= $purchase_info['erp_id']. ',';
                $record_number .= $suggest_to_erp[$purchase_info['ddemand_number']]['erp_id'].",";

                //取消数量
                $can_audit_status = '';
                $cancel_ctq       = 0;
                if ($purchase_info['items_id']) {
                    $cancel_info = $this->purchase_db->select("cancel_id")
                        ->from('purchase_order_cancel_detail')
                        ->where('items_id', $purchase_info['items_id'])
                        ->get()
                        ->result_array();
                    if (!empty($cancel_info)) {
                        $cancel_id_list = array_column($cancel_info, 'cancel_id');
                        $cancel = $this->purchase_db->select('audit_status,id')
                            ->where_in('id', $cancel_id_list)
                            ->get('purchase_order_cancel')
                            ->result_array();
                        if (!empty($cancel)) {
                            foreach ($cancel as $row) {
                                if (in_array($row['audit_status'], [60, 70])) {
                                    $cancel_array[] = $row['id'];
                                }
                                $can_audit_status = $row['audit_status'];
                            }
                            if (!empty($cancel_array)) {
                                $cancel_item = $this->purchase_db->select("cancel_ctq")
                                    ->from('purchase_order_cancel_detail')
                                    ->where_in('cancel_id', $cancel_array)
                                    ->get()
                                    ->result_array();
                                foreach ($cancel_item as $key => $value) {
                                    $cancel_ctq += $value['cancel_ctq'];
                                }
                            }
                        }
                    }
                }

                $push_data                                  = [];
                $push_data['erp_id']                        = $suggest_to_erp[$purchase_info['ddemand_number']]['erp_id'];       //erp订单号
                $push_data['sku']                           = $purchase_info['sku'];
                $push_data['purchase_type']                 = $purchase_info['purchase_type_id'];   //业务线
                $push_data['product_name']                  = $purchase_info['product_name'];  //产品名称
                $push_data['demand_date']                   = $purchase_info['create_time'];   //需求时间  suggest
                $push_data['category']                      = $purchase_info['product_category_name'];  //品类
                $push_data['demand_quantity']               = $purchase_info['purchase_amount'];  //需求数量
                $push_data['is_expedited']                  = $purchase_info['is_expedited'];    //是否加急
                $push_data['es_shipment_time']              = $purchase_info['es_shipment_time']; // 预计发货时间
                $push_data['warehouse_name']                = isset($warehouse_list[$purchase_info['warehouse_code']])?$warehouse_list[$purchase_info['warehouse_code']]:'';   //采购仓库
                $push_data['transfer_warehouse']            = isset($warehouse_list[$purchase_info['destination_warehouse']])?$warehouse_list[$purchase_info['destination_warehouse']]:'';   //目的仓
                $push_data['logistics_type']                = isset($logistics_type_list[$purchase_info['logistics_type']])?$logistics_type_list[$purchase_info['logistics_type']]:'';  //物流类型
                //    $push_data['platform']                      = $purchase_info['platform'];   //平台
                //   $push_data['site']                          = $purchase_info['site'];     //站点
                //   $push_data['sales_group']                   = $purchase_info['sales_group'];   //销售分组
                //   $push_data['sales_name']                    = $purchase_info['sales_name'];  //销售名称
                //    $push_data['sales_account']                 = $purchase_info['sales_account'];   //销售账号
                //     $push_data['sales_note']                    = $purchase_info['sales_note2'];  //销售备注
                $push_data['demand_number']                 = $purchase_info['demand_number'];  //需求单号
                $push_data['suggest_status']                = isset($suggest_status_list[$purchase_info['suggest_status']])?$suggest_status_list[$purchase_info['suggest_status']]:'';  //需求状态 默认1,1.未完结,2.已完结,3.过期,4.作废',
                $push_data['purchase_number']               = $purchase_info['purchase_number'];  //采购单号
                $push_data['buyer_name']                    = $purchase_info['buyer_name'];  //采购员
                $push_data['purchase_order_status']         = isset($order_status_list[$purchase_info['purchase_order_status']])?$order_status_list[$purchase_info['purchase_order_status']]: '';   //采购单状态
                $push_data['purchase_amount']               = $purchase_info['confirm_amount'];   //下单数量
                $push_data['place_date']                    = $purchase_info['place_time'];   //下单时间
                $push_data['cancel_amount']                 = $cancel_ctq;   //取消数量
                $push_data['supplier_code']                 = $purchase_info['supplier_code'];  //供应商编码
                $push_data['supplier_name']                 = $purchase_info['supplier_name'];  //供应商名称
                $push_data['is_drawback']                   = $purchase_info['is_drawback'];  //是否退税(默认 0.否,1.退税)'
                $push_data['pay_status']                    = isset($pay_status_list[$purchase_info['pay_status']])?$pay_status_list[$purchase_info['pay_status']]:'';     //付款状态
                $push_data['plan_product_arrive_time']      = $purchase_info['plan_product_arrive_time'];  //预计到货时间
                $express_no_info = $this->purchase_order_model->get_logistics_info($purchase_info['purchase_number'],$purchase_info['sku']);
                if (!empty($express_no_info)) {
                    $express_no_info = array_column($express_no_info,'express_no');
                    $express_no_info = implode(',',$express_no_info);

                } else {
                    $express_no_info = '';

                }



                $push_data['express_no']                    = $express_no_info;   //物流单号
                $push_data['receive_amount']                = $purchase_info['arrival_qty'];   //收货数量
                $push_data['instock_qty']                   = $purchase_info['instock_qty'];   //入库数量
                $push_data['arrival_qty']                   = $purchase_info['arrival_qty'];     //到货数量
                $push_data['bad_qty']                       = $purchase_info['bad_qty'];       //不良品数量
                $push_data['on_way_number']                 = !empty($purchase_info['confirm_amount'])?$purchase_info['purchase_amount']-$purchase_info['confirm_amount']:0;//未转在途取消数量
                $push_data['is_abnormal']                   = $purchase_info['is_abnormal'];//是否异常
                $cancel_cate = !empty($purchase_info['cancel_reason_category'])?$reason_cate[$purchase_info['cancel_reason_category']]:'';
                $push_data['causes_abolition']              = $cancel_cate.$purchase_info['cancel_reason'];//作废原因
                $push_data['audit_note']                    = $purchase_info['audit_note'];//备货单审核备注
                $push_data['sales_note2']                   = $purchase_info['sales_note'];//备货单备注

                $confirm_note = $this->purchase_order_remark_model->get_remark_list($purchase_info['purchase_number']);
                if ($confirm_note) {
                    $confirm_note = array_column($confirm_note,'remark');
                    $confirm_note = implode(',',$confirm_note);
                } else {
                    $confirm_note = '';

                }
                $push_data['purchase_audit_note']           = $confirm_note;//采购单备注

                $push_data['can_audit_status']              = $can_audit_status;//取消未到货状态
                $push_data['audit_status']                  = $purchase_info['audit_status'];//需求单审核状态
                if($purchase_info['dpurchase_type_id'] != PURCHASE_TYPE_FBA_BIG ||
                    $push_data['dpurchase_type_id'] != PURCHASE_TYPE_OVERSEA){
                    $push_data['demand_number'] =  $purchase_info['ddemand_number'];
                    //需求单状态
                    $purchase_info['suggest_status'] = $purchase_info['ddemand_status'];
                    if($purchase_info['suggest_status'] == 3){
                        $push_data['suggest_status'] = "已生成备货单";
                    }

                    if($purchase_info['suggest_status'] == 7){

                        $push_data['suggest_status'] = "作废";
                    }

                    if($purchase_info['suggest_status'] == 6){

                        $push_data['suggest_status'] = "已完结";
                    }

                    if($purchase_info['suggest_status'] ==4 || $purchase_info['suggest_status']==1 ){

                        $push_data['suggest_status'] = "未完结";
                    }

                    //print_R($suggest_status_list);die();
                    // $push_data['suggest_status']                = isset($suggest_status_list[$purchase_info['suggest_status']])?$suggest_status_list[$purchase_info['suggest_status']]:'';  //需求状态 默认1,1.未完结,2.已完结,3.过期,4.作废',

                }
                $success_list[$suggest_to_erp[$purchase_info['ddemand_number']]['erp_id']] = $push_data;
            }
        }
        $return['error_list']       = $error_list;
        $return['success_list']     = $success_list;
        return $return;
    }

    /**
     * @desc 根据sku查询七天内最近的备注,在生成备货单时添加备注
     * @author Jeff
     * @Date 2019/6/11 15:24
     * @param $sku
     * @return
     */
    public function get_sales_note($sku)
    {
        if(is_array($sku)){// 批量查找
            $return     = [];
            $sku_str    = implode("','",$sku);

            $querySql = "SELECT sku,sales_note
                FROM (
                    SELECT sugg.id,sugg.sku,sugg.sales_note
                    FROM pur_purchase_suggest AS sugg
                    WHERE sugg.sku IN('$sku_str')
                    AND sugg.id=(SELECT MAX(id) FROM pur_purchase_suggest AS aa WHERE aa.sku=sugg.sku)
                    ORDER BY sugg.id DESC
                ) AS tmp
                GROUP BY sku;";

            $querySqlResult = $this->purchase_db->query($querySql)->result_array();

            if($querySqlResult){
                foreach($querySqlResult as $value){
                    if (!empty($value['sales_note'])){
                        $sales_note = explode(' ',trim($value['sales_note']));
                        $last_time = ( isset($sales_note[count($sales_note)-3])&&isset($sales_note[count($sales_note)-2]) )?$sales_note[count($sales_note)-2]:'';

                        if(!empty($last_time) && strtotime("-7 day") < strtotime($last_time)){
                            $return[$value['sku']] = $sales_note[count($sales_note)-3].' '.$sales_note[count($sales_note)-2].' '.$sales_note[count($sales_note)-1];
                        }
                    }
                }
            }

            return $return;
        }else{
            $suggest_info = $this->purchase_db->select('sales_note')
                ->where('sku',$sku)
                ->order_by('id','desc')
                ->get('purchase_suggest')
                ->row_array();
            $return = '';
            if (!empty($suggest_info['sales_note'])){
                $sales_note = explode(' ',trim($suggest_info['sales_note']));
                $last_time = ( isset($sales_note[count($sales_note)-3])&&isset($sales_note[count($sales_note)-2]) )?$sales_note[count($sales_note)-2]:'';

                if(!empty($last_time) && strtotime("-7 day") < strtotime($last_time)){
                    $return = $sales_note[count($sales_note)-3].' '.$sales_note[count($sales_note)-2].' '.$sales_note[count($sales_note)-1];
                }
            }
            return $return;
        }
    }

    /*
     * @desc 查询七天内拒绝备注,如果过期了，就不显示作废原因了
     * @param
     */

    public function get_cancel_notes($date_str,$reason='')
    {
        if (empty($reason)) return '';
        if (time()-strtotime($date_str)>86400*7) //超出7天，作废原因消失
            return '';
        return $reason;


    }


    public function delete_sales_note_and_cancel_reason($sku)
    {
        $update_data['sales_note']='';
        $update_data['cancel_reason']='';
        $update_data['cancel_reason_category']=0;
        $result = $this->purchase_db->where('sku',$sku)->where('suggest_status',SUGGEST_STATUS_NOT_FINISH)->update($this->table_name,$update_data);
        return $result;
    }

    /**
     *获取待推送erp数据
     *@author harvin
     *@return arry
     */
    public function erp_suggest_id(){
        $erp_idList=$this->purchase_db->select('erp_id')
            ->where('is_erp',1)->where('is_push_erp',0)
            ->limit(100)
            ->get($this->table_name)
            ->result_array();
        if(empty($erp_idList)){
            return '';
        }else{
            return array_column($erp_idList, 'erp_id');
        }
    }

    /** 需求单变更采购员 wangliang
     * @param $params (array)id 需求单编号  (int)buyer_id 变更后的采购员id
     * @return array
     */
    public function change_purchaser($params){
        $ids = $params['id'];
        $buyer_id   = $params['buyer_id'];

        try{
            $this->load->model('user/purchase_user_model');
            $data_list = $this->purchase_user_model->get_list();
            $buyer_list = array_column($data_list,'name','id');
            if(!isset($buyer_list[$buyer_id])) throw new Exception('采购员不存在');

            foreach ($ids as $v){
                $id = $v;

                $info =  $this->purchase_db->where('id',$id)->get($this->table_name)->row_array();
                if(!$info) throw new Exception('需求单号有误');

                if($info['is_create_order'] == 1) throw new Exception($id.'已生成采购单!');

                $update_data = [
                    'buyer_id'      => $buyer_id,
                    'buyer_name'    => $buyer_list[$buyer_id],
                ];

                $result = $this->purchase_db->where('id',$id)->update($this->table_name,$update_data);
                if(!$result) throw new Exception('更新记录失败');

                operatorLogInsert([
                    'id'        => $id,
                    'type'      => 'purchase_suggest',
                    'content'   => '未生成备货单变更采购员',
                    'detail'    => "备货单{$id}采购员由【{$info['buyer_name']}{$info['buyer_id']}】变更为【{$buyer_list[$buyer_id]}{$buyer_id} 】"
                ]);

            }

            $return = ['bool'=>true,'msg'=>'操作成功'];

        }catch (Exception $e){
            $return = ['bool'=>false,'msg'=>$e->getMessage()];
        }

        return $return;
    }

    /**
     * 接收仓库推送的备货单数据
     * @param $data
     * @return array
     * @author Jeff
     * @date 2019-05-29
     */
    public function receive_suggest_data_from_warehouse($data){
        $this->load->model('warehouse/Warehouse_model');
        //加载采购候补人模块
        $this->load->model('system/Product_line_buyer_config_model');

        $error_list     = [];
        $add_data_list  = [];
        $success_list   = [];
        $return = ['code' => '200','error_list','message' => '','sku'=>[]];
        if(!empty($data)){
            $warehouse_list = $this->Warehouse_model->get_warehouse_list();
            $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');

            $this->purchase_db->trans_begin();
            try{
                foreach($data as $key => $val){
                    $sku = isset($val['sku'])?$val['sku']:'';
                    $order_id = isset($val['order_id'])?$val['order_id']:'';
                    if(empty($sku)){
                        $error_list[] = 'sku缺失';
                        continue;
                    }
                    if(empty($sku)){
                        $error_list[$sku] = '参数 order_id 缺失';
                        continue;
                    }
                    $is_order = $this->purchase_db
                        ->select('id')
                        ->from($this->table_name)
                        //->where("suggest_status",SUGGEST_STATUS_NOT_FINISH)
                        //->where("sku",$sku)
                        //->where("warehouse_code='{$val['purchase_warehouse']}'")
                        //->where("purchase_type_id",PURCHASE_TYPE_INLAND)
                        ->where("sku='{$sku}'")
                        ->where_in("source_from", [2,3]) // =2表示来自仓库系统,3-来自新wms
                        ->where("source_from_order_id='{$order_id}'")
                        ->get()
                        ->row_array();
                    if($is_order){
                        $success_list[] = ['sku' => $sku,'order_id' => $order_id];// 已存在的直接返回成功，不更新
                        continue;
                    }

                    $warehouse_code = isset($val['purchase_warehouse'])?$val['purchase_warehouse']:'';

                    $add_data = [
                        'sku'                      => isset($val['sku'])?$val['sku']:'',
                        'source_from'              => isset($val['source_from']) ? $val['source_from'] : 2,// 数据来源（1.计划系统，2.仓库需求，3.新仓库需求）
                        'source_from_order_id'     => $order_id,// 仓库需求单据ID
                        'purchase_type_id'         => PURCHASE_TYPE_INLAND,
                        'expiration_time'          => '2090-01-01 00:00:00',// 不过期
                        'warehouse_code'           => $warehouse_code,//'' 定时任务调接口更新
                        'warehouse_name'           => $warehouse_code?(isset($warehouse_list[$warehouse_code])?$warehouse_list[$warehouse_code]:''):'',//'',定时任务调接口更新
                        'plan_product_arrive_time' => '0000-00-00 00:00:00',
                        'purchase_amount'          => isset($val['purchase_quantity'])?$val['purchase_quantity']:0,
                        'sales_note2'              => isset($val['sales_note'])?$val['sales_note']:'',
                        'create_user_name'         => isset($val['create_user'])?$val['create_user']:'',
                        'is_warehouse'             => 1,
                    ];

                    foreach($add_data as $ck_key => $ck_vvv){
                        if(is_null($ck_vvv)){
                            $error_list[$sku] = "参数[$ck_key]缺失";
                            continue;
                        }
                    }

                    $sku_info = $this->purchase_db->select('a.product_img_url,a.product_name,a.product_line_id,a.supplier_code,a.supplier_name,
                                a.purchase_price,a.create_id,a.create_user_name,a.is_drawback,b.is_cross_border,c.buyer_id,c.buyer_name,
                                d.linelist_cn_name')->from('product a')
                        ->join('supplier b', 'a.supplier_code=b.supplier_code', 'left')
                        ->join('supplier_buyer c', 'b.supplier_code=c.supplier_code and c.status=1 and c.buyer_type="'.$add_data['purchase_type_id'].'"', 'left')
                        ->join('product_line d', 'a.product_line_id=d.product_line_id', 'left')
                        ->where(['a.sku' => $val['sku']])
                        ->get()
                        ->row_array();

                    //查询该sku的最新一条已作废需求单(如果存在)的作废原因
                    $cancel_info = $this->purchase_db->select('cancel_reason')
                        ->where('sku',$val['sku'])
                        ->where('suggest_status',SUGGEST_STATUS_CANCEL)
                        ->order_by('id','desc')
                        ->get('purchase_suggest')
                        ->row_array();

                    if(empty($sku_info)){
                        $error_list[$sku] = 'SKU信息缺失';
                        continue;
                    }

                    /*//判断是否需要非实单锁单
                    $need_lock_res = $this->is_need_lock($val['sku'],$sku_info['supplier_code']);

                    if ($need_lock_res){
                        $add_data['lock_type'] = LOCK_SUGGEST_NOT_ENTITIES;//非实单锁单
                        $add_data['is_locked'] = 1;//是否非实单锁单过
                    }else{
                        $add_data['lock_type'] = 0;
                        $add_data['is_locked'] = 0;
                    }*/

//                    $line_info                         = $this->get_one_level_product_line($sku_info['product_line_id']);
                    $line_info                         = $this->get_product_line_by_id($sku_info['product_line_id']);
                    $add_data['suggest_status']        = SUGGEST_STATUS_NOT_FINISH;
                    $add_data['create_time']           = date("Y-m-d H:i:s", time());
                    $add_data['product_img_url']       = !empty($sku_info['product_img_url']) ? $sku_info['product_img_url'] : '';
                    $add_data['product_name']          = !empty($sku_info['product_name']) ? $sku_info['product_name'] : '';
                    $add_data['two_product_line_id']   = !empty($sku_info['product_line_id']) ? $sku_info['product_line_id'] : '';
                    $add_data['two_product_line_name'] = !empty($sku_info['linelist_cn_name']) ? $sku_info['linelist_cn_name'] : '0';
                    $add_data['product_line_id']       = $line_info['id'];
                    $add_data['product_line_name']     = $line_info['title'];
                    $add_data['supplier_code']         = !empty($sku_info['supplier_code']) ? $sku_info['supplier_code'] : '';
                    $add_data['supplier_name']         = !empty($sku_info['supplier_name']) ? $sku_info['supplier_name'] : '';
                    $add_data['purchase_unit_price']   = format_two_point_price(!empty($sku_info['purchase_price']) ? $sku_info['purchase_price'] : 0.000);
                    $add_data['purchase_total_price']  = format_two_point_price($add_data['purchase_unit_price'] * $add_data['purchase_amount']);
                    $add_data['developer_id']          = !empty($sku_info['create_id']) ? $sku_info['create_id'] : 0;
                    $add_data['developer_name']        = !empty($sku_info['create_user_name']) ? $sku_info['create_user_name'] : '';
                    if (empty($sku_info['buyer_id'])) {
                        //获取采购候补人数据
                        $buyer_info = $this->Product_line_buyer_config_model->get_buyer($line_info['id'], $add_data['purchase_type_id']);
                        $add_data['buyer_id'] = $buyer_info['buyer_id'];
                        $add_data['buyer_name'] = $buyer_info['buyer_name'];
                    } else {
                        $add_data['buyer_id'] = $sku_info['buyer_id'];
                        $add_data['buyer_name'] = $sku_info['buyer_name'];
                    }
                    $add_data['is_cross_border']       = !empty($sku_info['is_cross_border']) ? $sku_info['is_cross_border'] : 0;
                    $add_data['is_drawback']           = 0;//国内仓都默认不退税  !empty($sku_info['is_drawback']) ? $sku_info['is_drawback'] : 0;
                    $add_data['cancel_reason']         = !empty($cancel_info)?$cancel_info['cancel_reason']:'';
                    $add_data['audit_status']          = 1;
                    $add_data['audit_time']            = $add_data['create_time'];
                    $add_data['sales_note']            = $this->get_sales_note($val['sku']);//根据sku查询七天内最近的备注,在生成备货单时添加备注

                    $add_data_list[] = $add_data;

                    if (empty($add_data['sales_note'])){
                        $delete_res = $this->delete_sales_note_and_cancel_reason($sku);
                        if (empty($delete_res)) throw new Exception($sku.'清空备注失败');
                    }
                }
                if($add_data_list){
                    foreach($add_data_list as &$value){
                        $value['demand_number'] = get_prefix_new_number('RD');
                    }
                    $res = $this->purchase_db->insert_batch($this->table_name,$add_data_list);
                    if($res){
                        foreach($add_data_list as $value_2){
                            $success_list[] = ['sku' => $value_2['sku'],'order_id' => $value_2['source_from_order_id']];
                        }
                        $this->purchase_db->trans_commit();
                    }else{
                        throw new Exception('数据插入失败');
                    }
                }

                $return['sku']        = $success_list;
                $return['error_list'] = $error_list;

            }catch(Exception $e){
                $return['code'] = '500';
                $return['message'] = $e->getMessage();
                $this->purchase_db->trans_rollback();
            }

            return $return;
        }else{
            $return = ['code'=>'500','message'=>'无数据'];
            return $return;
        }
    }

    /**
     * @desc 判断是否需要非实单锁单
     * @author Jeff
     * @Date 2019/8/6 11:16
     * @param $sku
     * @param $supplier_code
     * @return
     */
    public function is_need_lock($sku='', $supplier_code='')
    {
        //获取锁单配置
        $lock_data = $this->suggest_lock_model->get_one();

        if (empty($lock_data)) return false;
        $endtime = date("Y-m-d H:i:s");
        $starttime = date("Y-m-d H:i:s",strtotime("-{$lock_data['not_reduce_day']} day"));//获取配置多少天数内无降价记录

        //判断配置期限内是否降价，如果无降价记录,走判断供应商近三个月合作金额是否大于10万
        $sku_is_reduction = $this->reduced_edition->get_sku_is_reduction($sku,$starttime,$endtime);

        //有降价记录,直接生成备货单,不用非实单锁单
        if (!empty($sku_is_reduction)) return false;

        if (empty($supplier_code)) return false;

        //判断该供应商近三个月合作金额是否大于等于10万,否的话返回不用非实单锁单
        $limit_amount = NOT_ENTITIES_LIMIT_AMOUNT;// 界定金额

        /*$month_3 = date('Y-m', strtotime(" -3 month")).'-01';
        $month_2 = date('Y-m', strtotime(" -2 month")).'-01';
        $month_1 = date('Y-m', strtotime(" -1 month")).'-01';
        $purchase_price_arr3 = $this->Supplier_purchase_amount->get_calculate_amount($month_3,$supplier_code);
        $purchase_price_arr2 = $this->Supplier_purchase_amount->get_calculate_amount($month_2,$supplier_code);
        $purchase_price_arr1 = $this->Supplier_purchase_amount->get_calculate_amount($month_1,$supplier_code);
        $month_3_price = isset($purchase_price_arr3['actual_price'])?$purchase_price_arr3['actual_price']:0;
        $month_2_price = isset($purchase_price_arr2['actual_price'])?$purchase_price_arr2['actual_price']:0;
        $month_1_price = isset($purchase_price_arr1['actual_price'])?$purchase_price_arr1['actual_price']:0;

        $actual_price_total = $month_3_price + $month_2_price + $month_1_price;// 三个月的 总金额

        if (empty($actual_price_total) or $actual_price_total < $limit_amount){
            return false;
        }else{
            return true;
        }

        */

        //获取新老系统近3个月合作金额
        $actual_price_total = $this->purchase_order_model->cooperation_amount($supplier_code);
        if (empty($actual_price_total) or $actual_price_total[$supplier_code] < $limit_amount){
            return false;
        }else{
            return true;
        }

    }

    //解锁需求单
    public function unlock_suggest($ids,$suggest_list=''){
        $return = ['code'=>false,'msg'=>''];

        $this->purchase_db->trans_begin();
        try {

            //审核通过
            $update_data = [];

            foreach ($ids as $key => $id){

                $update_data[$key] = [
                    'id' => $id,
                    'lock_type' => 0,//未锁单
                    'unlock_time' => date("Y-m-d H:i:s"),//解锁时间
                ];

                if ($suggest_list[$key]['lock_type']==LOCK_SUGGEST_NOT_ENTITIES){
                    $lock_type = '非实单锁单';
                }else{
                    $lock_type = '实单锁单';
                }

                $insert_res = operatorLogInsert(
                    [
                        'id' => $suggest_list[$key]['demand_number'],
                        'type' => 'pur_purchase_suggest',
                        'content' => '修改需求锁单状态',
                        'detail' => '修改需求锁单状态，从【'.$lock_type.'】改到【未锁单】',
                    ]
                );
                if(empty($insert_res)) throw new Exception($suggest_list[$key]['demand_number'].":需求单操作记录添加失败");
            }

            $update_res = $this->purchase_db->update_batch('purchase_suggest', $update_data,'id');

            if(empty($update_res)){
                throw new Exception("需求单状态更新失败");
            }

            $this->purchase_db->trans_commit();
            $return['code'] = true;

        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }

    //产品降价审核通过后自动解锁需求单
    public function unlock_suggest_from_product_reduce($sku){
        $return = ['code'=>false,'msg'=>''];

        //根据sku查询未完结,未生成采购单的,未过期的需求单
        $query_builder = $this->purchase_db->where('sku', $sku);
        $query_builder = $query_builder->where_in('lock_type', [LOCK_SUGGEST_NOT_ENTITIES,LOCK_SUGGEST_ENTITIES]);
        $query_builder = $query_builder->from('purchase_suggest as ps');
        $query_builder = $query_builder->select('ps.id,ps.demand_number,ps.lock_type');
        $lock_suggest_lists  = $query_builder->get()->result_array();

        if (!empty($lock_suggest_lists)){
            $this->purchase_db->trans_begin();
            try {

                //审核通过
                $update_data = [];

                foreach ($lock_suggest_lists as $key => $value){

                    $update_data[$key] = [
                        'id' => $value['id'],
                        'lock_type' => 0,//未锁单
                        'unlock_time' => date("Y-m-d H:i:s"),//解锁时间
                    ];

                    if ($value['lock_type']==LOCK_SUGGEST_NOT_ENTITIES){
                        $lock_type = '非实单锁单';
                    }else{
                        $lock_type = '实单锁单';
                    }

                    $insert_res = operatorLogInsert(
                        [
                            'id' => $value['demand_number'],
                            'type' => 'pur_purchase_suggest',
                            'content' => '产品降价审核通过,修改需求锁单状态',
                            'detail' => '修改需求锁单状态，从【'.$lock_type.'】改到【未锁单】',
                        ]
                    );
                    if(empty($insert_res)) throw new Exception($value['demand_number'].":需求单操作记录添加失败");
                }

                $update_res = $this->purchase_db->update_batch('purchase_suggest', $update_data,'id');

                if(empty($update_res)){
                    throw new Exception("需求单状态更新失败");
                }

                $this->purchase_db->trans_commit();
                $return['code'] = true;

            } catch (\Exception $e) {
                $this->purchase_db->trans_rollback();
                $return['msg'] = $e->getMessage();
            }
        }else{
            $return['code'] = true;
        }

        return $return;
    }

    //定时任务生成实单锁单数据(每五分钟执行一次)
    public function plan_create_entities_lock_list($ids = array())
    {

        $is_debug = $this->input->get_post('is_debug');//是否开启调试

        $return = ['code' => 200, 'message' => ''];

        //获取锁单信息
        $is_lock_info = $this->suggest_lock_model->validate_is_lock_time();
        //锁单时间外不用往下执行了
//        if (!empty($is_lock_info['message'])) {
//            //系统配置错误
//            if ($is_lock_info['code'] != 200) {
//                $return['message'] = $is_lock_info['message'];
//                $return['code']    = $is_lock_info['code'];
//                return $return;
//            }
//        } else {
//
//            if (empty($is_debug)){//没开启调试,限制锁单时间
//                //锁单时间外
//                $return['message'] = '锁单时间外';
//                $return['code']    = $is_lock_info['code'];
//                return $return;
//            }
//        }

//        if (empty($is_debug)){
//            //判断当前时间是否在锁单配置结束时间之前10分钟之内,是则执行下去,否则返回
//            $is_before_end_ten_min = $this->suggest_lock_model->validate_is_before_ten_min($is_lock_info['data']);
//        }
//
//
//        if (!empty($is_before_end_ten_min['message'])) {
//
//            $return['message'] = $is_before_end_ten_min['message'];
//            $return['code']    = $is_before_end_ten_min['code'];
//            return $return;
//        }
        $this->purchase_db->trans_start();
        try {
            //解锁过的sku,该sku关联的备货单在15天内都不再进入到实单锁单页面
            $fifteen_day_time = date('Y-m-d H:i:s',strtotime('-15 days'));//15天前的时间戳

            //根据供应商来查询锁单时间内采购总金额大于等于配置值的需求单
            $query_builder_over_sea  = $this->purchase_db->where('sug.suggest_status', SUGGEST_STATUS_NOT_FINISH)//未完结的需求单
            ->where('sug.audit_status', SUGGEST_AUDITED_PASS)//审核通过的需求单
            ->where('sug.lock_type', 0)//非锁单
            ->where('sug.is_locked', 0)//非实单已解锁过的备货单,不再进入实单锁单
            ->where('sug.unlock_time <', $fifteen_day_time);

            if( !empty($ids) ){

                $query_builder_over_sea->where_in('sug.id',$ids);
            }
            $query_builder_over_fba_inside = clone $query_builder_over_sea;

            $lock_info = $is_lock_info['data'];

            // 海外仓
            $over_sea_suppliers = $query_builder_over_sea->select('sug.sku,sug.id,sug.supplier_code,sug.purchase_total_price AS purchase_total_all')
                ->from($this->table_name.' as sug')
                ->join('product as prd','sug.sku=prd.sku','LEFT')
                ->where('prd.is_purchasing',1)
                ->where('sug.purchase_type_id', PURCHASE_TYPE_OVERSEA)
                ->order_by('purchase_total_all desc')
                ->having('purchase_total_all >' . $lock_info['purchase_total_over_sea'] * 10000)
                ->get()->result_array();

            $over_sea_supplier_codes = array_column($over_sea_suppliers, 'supplier_code');
            $over_sea_supplier_codes = array_filter($over_sea_supplier_codes);//海外仓需锁单供应商

            // 根据备货的ID 进行锁单

            $over_sea_suggest_ids = array_column($over_sea_suppliers, 'id');
            $over_sea_suggest_ids = array_filter($over_sea_suggest_ids);



            $fba_inside_suppliers = $query_builder_over_fba_inside->select('sug.id,sug.supplier_code,sug.purchase_total_price AS purchase_total_all')
                ->from($this->table_name. ' AS sug')
                ->join('product as prd','sug.sku=prd.sku','LEFT')
                ->where('prd.is_purchasing',1)
                ->where_in('sug.purchase_type_id', [PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH])
                ->order_by('purchase_total_all desc')
                ->group_by('supplier_code')
                ->having('purchase_total_all >' . $lock_info['purchase_total_fba_inside'] * 10000)
                ->get()->result_array();



            $fba_inside_supplier_codes = array_column($fba_inside_suppliers, 'supplier_code');
            $fba_inside_supplier_codes = array_filter($fba_inside_supplier_codes);//国内仓,fba需锁单供应商

            // 备货单ID锁单

            $fba_inside_suggest_ids = array_column($fba_inside_suppliers,'id');

            $fba_inside_suggest_ids = array_filter($fba_inside_suggest_ids);


            if (!empty($over_sea_suggest_ids)){


                //获取海外仓有降级记录的sku列表
                $over_sea_reduce_skus = $this->get_sku_reduce_list($over_sea_supplier_codes);
//
                $over_sea_supplier_codes_string = format_query_string($over_sea_supplier_codes);//将数组转换为字符串

                //海外仓锁仓
                $update_over_sea_db = $this->purchase_db;

                $update_over_sea_db_sql = 'UPDATE `pur_purchase_suggest` SET `lock_type` = '.LOCK_SUGGEST_ENTITIES.'
                                          WHERE  `purchase_type_id` = '.PURCHASE_TYPE_OVERSEA.'
                                          AND `suggest_status` = '.SUGGEST_STATUS_NOT_FINISH.'
                                          AND `audit_status` = '.SUGGEST_AUDITED_PASS.'
                                          AND `lock_type` = 0
                                          AND `unlock_time` < "'.$fifteen_day_time.'"
                                          AND `is_locked` = 0 
                                          AND `id` IN ('.implode(",",$over_sea_suggest_ids).')';

                if (!empty($over_sea_reduce_skus)){
                    $over_sea_reduce_skus = array_column($over_sea_reduce_skus, 'sku');
                    $over_sea_reduce_skus = format_query_string($over_sea_reduce_skus);
                    $update_over_sea_db_sql .= ' AND sku not in('.$over_sea_reduce_skus.')';
                }
                $lock_over_sea = $update_over_sea_db->query($update_over_sea_db_sql);
                //待生成备货单页面，需要验证是退税的采购金额是否满足条件，不满足的，是退税改为否退税

                $overseaRefundRule = $this->purchase_db->from("oversea_refund_rule")->select("amount")->get()->row_array();
                if(!empty($overseaRefundRule)) {
                    $sql = " SELECT SUM(purchase_total_price) AS purchase_total_all,is_drawback,demand_number FROM pur_purchase_suggest AS suggest WHERE is_cross_border=0 AND audit_status!=3 AND supplier_code IN (" . $over_sea_supplier_codes_string . ")";
                    $sql .= " AND is_drawback=1 GROUP BY demand_number HAVING purchase_total_all<".$overseaRefundRule['amount'];
                    $suggestSupplierData = $this->purchase_db->query($sql)->result_array();
                    if(!empty($suggestSupplierData)){

                        $updateDrawback = ['is_drawback' => 0];
                        $demandNumbers = array_column($suggestSupplierData,"demand_number");
                        $this->purchase_db->where("is_drawback",1)->where_in("demand_number",$demandNumbers)->update('pur_purchase_suggest',$updateDrawback);
                    }
                }
                if (empty($lock_over_sea)) throw new Exception("海外仓实单锁单失败");
            }

            if (!empty($fba_inside_suggest_ids)){

                //获取国内仓,fba有降级记录的sku列表
                $fba_inside_reduce_skus = $this->get_sku_reduce_list($fba_inside_supplier_codes);

                $fba_inside_supplier_codes_string = format_query_string($fba_inside_supplier_codes);//将数组转换为字符串

                //国内仓,fba锁仓
                $update_fba_inside_db = $this->purchase_db;
                $update_fba_inside_db_sql = 'UPDATE `pur_purchase_suggest` SET `lock_type` = '.LOCK_SUGGEST_ENTITIES.'
                                          WHERE  `purchase_type_id` in ('.PURCHASE_TYPE_INLAND.','.PURCHASE_TYPE_FBA.','.PURCHASE_TYPE_PFB.','.PURCHASE_TYPE_PFH.')
                                          AND `suggest_status` = '.SUGGEST_STATUS_NOT_FINISH.'
                                          AND `audit_status` = '.SUGGEST_AUDITED_PASS.'
                                          AND `lock_type` = 0
                                          AND `unlock_time` < "'.$fifteen_day_time.'"
                                          AND `is_locked` = 0 AND `id` in('.implode(",",$fba_inside_suggest_ids).')';

                if (!empty($fba_inside_reduce_skus)){
                    $fba_inside_reduce_skus = array_column($fba_inside_reduce_skus, 'sku');
                    $fba_inside_reduce_skus = format_query_string($fba_inside_reduce_skus);
                    $update_fba_inside_db_sql .= ' AND sku not in('.$fba_inside_reduce_skus.')';
                }
                $lock_fba_inside = $update_fba_inside_db->query($update_fba_inside_db_sql);


                if (empty($lock_fba_inside)) throw new Exception("国内仓,fba实单锁单失败");
            }

            //1.SKU 商品属于新品 is_new = 1
            $is_new_sku_data_query = $this->purchase_db->select('suggest.id')
                ->from($this->table_name.' AS suggest')
                ->join('product as prd','suggest.sku=prd.sku','LEFT')
                ->where('suggest.suggest_status', SUGGEST_STATUS_NOT_FINISH)//未完结的需求单
                ->where('suggest.audit_status', SUGGEST_AUDITED_PASS)//审核通过的需求单
                ->where('suggest.lock_type', 0)//非锁单
                ->where('prd.is_purchasing',1)
                ->where('suggest.is_locked', 0)//非实单已解锁过的备货单,不再进入实单锁单
                ->where('suggest.unlock_time <', $fifteen_day_time)
                ->where('suggest.is_new',1);
            if(!empty($ids)){

                $is_new_sku_data_query->where_in('suggest.id',$ids);
            }

            $is_new_sku_data = $is_new_sku_data_query->get()->result_array();
            $is_new_sku_data = empty($is_new_sku_data)?[]:array_column($is_new_sku_data,'id');
            if(!empty($is_new_sku_data)){
                $is_new_sku_data = format_query_string($is_new_sku_data);

                $update_is_new_sku_data_sql = 'UPDATE `pur_purchase_suggest` SET `lock_type` = 2
                                          WHERE `id` in ('.$is_new_sku_data.') 
                                          AND `suggest_status` = '.SUGGEST_STATUS_NOT_FINISH.'
                                          AND `audit_status` = '.SUGGEST_AUDITED_PASS.'
                                          AND `lock_type` = 0
                                          AND `is_locked` = 0 
                                          AND `unlock_time` < "'.$fifteen_day_time.'"
                                          AND `is_new` = 1';
                $update_result = $this->purchase_db->query($update_is_new_sku_data_sql);
                if (empty($update_result)) throw new Exception("是新品,实单锁单失败");
            }


            //2.海外仓且是首单 is_overseas_first_order = 1
            $oversea_first_sku_data_query = $this->purchase_db->select('suggest.id')
                ->from($this->table_name.' AS suggest')
                ->join('product as prd','suggest.sku=prd.sku','LEFT')
                ->where('prd.is_purchasing',1)
                ->where('suggest.suggest_status', SUGGEST_STATUS_NOT_FINISH)//未完结的需求单
                ->where('suggest.audit_status', SUGGEST_AUDITED_PASS)//审核通过的需求单
                ->where('suggest.lock_type', 0)//非锁单
                ->where('suggest.is_locked', 0)//非实单已解锁过的备货单,不再进入实单锁单
                ->where('suggest.unlock_time <', $fifteen_day_time)
                ->where('suggest.is_overseas_first_order',1)
                ->where('suggest.purchase_type_id',2);
//                ->get()->result_array();

            if( !empty($ids)){

                $oversea_first_sku_data_query->where_in("suggest.id",$ids);
            }

            $oversea_first_sku_data = $oversea_first_sku_data_query->get()->result_array();
            if(!empty($oversea_first_sku_data)){
                $oversea_first_sku_data = array_column($oversea_first_sku_data,"id");
                $oversea_first_sku_data = format_query_string($oversea_first_sku_data);
                $update_is_new_sku_data_sql = 'UPDATE `pur_purchase_suggest` SET `lock_type` = 2
                                          WHERE `id` in ('.$oversea_first_sku_data.')
                                          AND `suggest_status` = '.SUGGEST_STATUS_NOT_FINISH.'
                                          AND `audit_status` = '.SUGGEST_AUDITED_PASS.'
                                          AND `lock_type` = 0
                                          AND `is_locked` = 0 
                                          AND `unlock_time` < "'.$fifteen_day_time.'"
                                          AND `purchase_type_id` = 2
                                          AND `is_overseas_first_order` = 1';
                $update_result = $this->purchase_db->query($update_is_new_sku_data_sql);

                if (empty($update_result)) throw new Exception("是海外仓且首单,实单锁单失败");
            }



            $this->purchase_db->trans_commit();
            if (empty($over_sea_supplier_codes) && empty($fba_inside_supplier_codes) && empty($is_new_sku_data) && empty($oversea_first_sku_data)){
                $return['message'] = '没有需锁单数据';
                return $return;
            }

            $return['message'] = '实单锁单成功';

        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['code'] = 500;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    //查找供应商降价的sku列表
    public function get_sku_reduce_list($supplier_codes)
    {
        $skus = [];
        //获取锁单配置
        $lock_data = $this->suggest_lock_model->get_one();

        if (empty($lock_data)) return $skus;
        $endtime = date("Y-m-d H:i:s");
        $starttime = date("Y-m-d H:i:s",strtotime("-{$lock_data['not_reduce_day']} day"));//获取配置多少天数内无降价记录

        //判断配置期限内是否降价
        $sku_reduction_list = $this->reduced_edition->get_sku_list_is_reduction($supplier_codes,$starttime,$endtime);

        //有降价记录,直接生成备货单,不用实单锁单
        if (!empty($sku_reduction_list)) return $sku_reduction_list;

        return $skus;
    }


    /** 更新需求单结算方式 wangliang
     * @param $demand_list 需求单号数组
     * @param $account_type 新的结算方式
     * @return array
     */
    public function update_suggest_account_type($demand_list,$account_type){
        if(!is_array($demand_list) || !is_numeric($account_type)){
            return ['code'=>0,'msg'=>'更新需求单结算方式：参数不合法'];
        }
        $list = $this->purchase_db->select('id')->where_in('demand_number',$demand_list)
            ->where('account_type!=',$account_type)
            ->get($this->table_name)
            ->result_array();
        if(!empty($list)){
            $ids = array_column($list,'id');
            $update_data = ['account_type'=>$account_type];
            $res = $this->update_suggest($update_data,$ids);
            if(!$res){
                return ['code'=>0,'msg'=>'更新需求单结算方式失败'];
            }
        }

        return ['code'=>1,'msg'=>'更新需求单结算方式成功'];
    }

    /**
     * 更新需求单支付方式
     * @author Jolon
     * @param $demand_list 需求单号数组
     * @param $pay_type 新的支付方式
     * @return array
     */
    public function update_suggest_pay_type($demand_list,$pay_type){
        if(!is_array($demand_list) || !is_numeric($pay_type)){
            return ['code'=>0,'msg'=>'更新需求单支付方式：参数不合法'];
        }
        $list = $this->purchase_db->select('id')->where_in('demand_number',$demand_list)
            ->where('pay_type!=',$pay_type)
            ->get($this->table_name)
            ->result_array();
        if(!empty($list)){
            $ids = array_column($list,'id');
            $update_data = ['pay_type'=>$pay_type];
            $res = $this->update_suggest($update_data,$ids);
            if(!$res){
                return ['code'=>0,'msg'=>'更新需求单支付方式失败'];
            }
        }

        return ['code'=>1,'msg'=>'更新需求单支付方式成功'];
    }

    /**
     * 导入修改需求单采购数量和仓库
     * @author jeff
     * @param $data
     * @return array
     */
    public function import_change_suggest($data){
        $this->load->model('warehouse/Warehouse_model');

        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_code','warehouse_name');

        $return = ['code' => true,'data' => [],'message' => ''];

        $error_list = [];
        if($data){
            $update_list = [];
            foreach($data as $key => $value){
                if($key <= 1) continue;
                $sku             = isset($value['A']) ? trim($value['A']) : '';
                $purchase_amount = isset($value['B']) ? trim($value['B']) : 0;
                $warehouse_name  = isset($value['C']) ? trim($value['C']) : '';
                $sales_note      = isset($value['D']) ? trim($value['D']) : '';

                if(empty($sku)) {
                    $error_list[$key] = 'SKU不能为空';
                    continue;
                }

                if (empty($purchase_amount)){
                    $error_list[$key] = '采购数量不能为空';
                    continue;
                }

                if (empty($warehouse_name)){
                    $error_list[$key] = '仓库不能为空';
                    continue;
                }

                $sku_info = $this->purchase_db->select('a.product_status,a.product_img_url,a.product_name,a.product_line_id,a.supplier_code,a.supplier_name,
                                    a.purchase_price,a.ticketed_point,a.create_id,a.create_user_name,a.is_drawback
                                    ')->from('product a')
                    ->where(['a.sku' => $sku])
                    ->get()
                    ->row_array();

                if(in_array($warehouse_name,['塘厦AM精品退税仓','虎门FBA虚拟仓','慈溪fba虚拟仓','慈溪FBA虚拟仓','慈溪海外虚拟仓'])){
                    $error_list[$key] = $warehouse_name.'已停用';
                    continue;
                }

                if(empty($sku_info)){
                    $error_list[$key] = 'SKU信息缺失';
                    continue;
                }

                if(!isset($warehouse_list[$warehouse_name])){
                    $error_list[$key] = '仓库错误';
                    continue;
                }
                $warehouse_code = $warehouse_list[$warehouse_name];

                $update_data = [
                    'sku'             => $sku,
                    'purchase_amount' => $purchase_amount,
                    'warehouse_code'  => $warehouse_code,
                    'warehouse_name'  => $warehouse_name,
                    'sales_note'      => $sales_note,
                ];

                $update_list[] = $update_data;
            }

            if($error_list){
                $return['code'] = false;
                $return['data'] = $error_list;
                return $return;
            }

            try{
                $this->purchase_db->trans_begin();

                $res = $this->purchase_db->where('is_mrp',1)
                    ->where('suggest_status',SUGGEST_STATUS_NOT_FINISH)
                    ->where('purchase_type_id',PURCHASE_TYPE_INLAND)//只改国内仓的
                    ->where('create_time >=',date("Y-m-d 00:00:00"))
                    ->update_batch($this->table_name,$update_list,'sku');

                if($res){
                    $this->purchase_db->trans_commit();
                    $return['code']     = true;
                    $return['message']  = '修改个数:'.$res;
                }elseif($res===0){
                    $this->purchase_db->trans_commit();
                    $return['code']     = true;
                    $return['message']  = '无数据可修改';
                }else{
                    throw new Exception('数据修改失败');
                }
            }catch(Exception $e){
                $return['code']     = false;
                $return['message']  = $e->getMessage();
                $this->purchase_db->trans_rollback();
            }

            return $return;
        }else{
            $return['code']     = false;
            $return['message']  = '数据缺失';
            return $return;
        }
    }

    /**
     * @desc 删除备注或作废原因
     * @author Jeff
     * @Date 2019/8/30 14:07
     * @param $id
     * @param $note_type
     * @return bool
     * @return
     */
    public function delete_sales_note($id,$note_type)
    {
        $suggest = $this->get_one_suggest($id);
        if(empty($suggest)) return false;

        if($note_type==1){
            $result = $this->purchase_db->where('sku',$suggest['sku'])->update($this->table_name,['sales_note' => '']);
        }else{
            $result = $this->purchase_db->where('sku',$suggest['sku'])->update($this->table_name,['cancel_reason' => '']);
        }

        if(empty($result)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * @desc 需求单作废确认
     * @author Jeff
     * @Date 2019/4/18 13:45
     * @param $ids 需求单id
     * @return array
     * @return
     */
    public function demand_order_cancel_confirm($ids,$suggest_list)
    {
        $return = ['code'=>false,'msg'=>''];
        $this->load->model('approval_model');
        $this->purchase_db->trans_begin();
        try {

            $update_data = [];
            $push_data = [];//推送计划系统数据
            foreach ($ids as $key => $id){
                $cancel_name = getActiveUserName();
                $cancel_reason = $this->get_cancel_reason($suggest_list[$key]['cancel_reason']);
                $update_data[] = [
                    'id' => $id,
                    'suggest_status' => SUGGEST_STATUS_CANCEL,//作废
                    'cancel_reason' => $cancel_reason.'-'.date('Y-m-d H:i:s',time()).'-'.$cancel_name,//作废原因
                    'audit_status' => SUGGEST_UN_AUDIT,//待审核
                ];

                //构造推送数据
                if ($suggest_list[$key]['source_from']==1){//数据来源于计划系统才推送计划系统
                    $push_data[] = [
                        'pur_sn' => $suggest_list[$key]['demand_number'],//备货单号
                        'state' => SUGGEST_STATUS_CANCEL,
                        'business_line' => $suggest_list[$key]['purchase_type_id'],//业务线
                        'cancel_reason' => $cancel_reason
                    ];
                }

                $insert_res = operatorLogInsert(
                    [
                        'id' => $suggest_list[$key]['demand_number'],
                        'type' => 'pur_purchase_suggest',
                        'content' => '修改需求状态',
                        'detail' => '修改状态，从【' . getSuggestStatus($suggest_list[$key]['suggest_status']) . '】改到【' . getSuggestStatus(SUGGEST_STATUS_CANCEL) . '】',
                    ]
                );
                if(empty($insert_res)) throw new Exception($suggest_list[$key]['demand_number'].":需求单操作记录添加失败");
            }

            $update_res = $this->purchase_db->update_batch('purchase_suggest', $update_data,'id');

            if(empty($update_res)){
                throw new Exception("需求单状态更新失败");
            }

            $this->save_sku_cancel_reason(array($suggest_list[$key]['sku']),$cancel_reason.'-'.date('Y-m-d H:i:s',time()).'-'.$cancel_name,$suggest_list[$key]['cancel_reason_category']);

            if (!empty($push_data)){
                //推送计划系统
                $push_plan = $this->approval_model->push_plan_expiration($push_data);//推送计划系统作废备货单
                if($push_plan !== true){
                    throw new Exception('推送计划系统作废失败！');
                }
            }

            $this->purchase_db->trans_commit();
            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * @desc 获取作废原因(提测作废时间,作废人)
     * @author Jeff
     * @Date 2019/6/11 15:24
     * @param $sku
     * @return
     */
    public function get_cancel_reason($cancel_reason)
    {

        if (!empty($cancel_reason)){
            $cancel_reason_arr = explode('-',trim($cancel_reason));
            $cancel_reason = ( isset($cancel_reason_arr[count($cancel_reason_arr)-5])&&isset($cancel_reason_arr[count($cancel_reason_arr)-2]) )?$cancel_reason_arr[count($cancel_reason_arr)-5]:'';
        }
        return $cancel_reason;
    }

    /**
     * @desc 验证需求单关联的采购单是否作废
     * @author Jeff
     * @Date 2019/03/23 15:00
     * @param $suggest_list
     * @return boolean
     */
    public function validate_order_cancel($suggest_list)
    {
        if(!empty($suggest_list)){
            foreach($suggest_list as $key => $val){
                if($val['connect_order_cancel'] != 2){//关联采购单是否已作废（1.否;2.是）
                    return $val;
                }
            }
            return;
        }
    }


    /*
     *@desc 在后台按sku将作废原因存储起来
     *@author Lk
     *@param array $skuList string $cancel_reason  int $cancel_reason_category
     *@return boolean
     */

    public function save_sku_cancel_reason($sku_list,$cancel_reason,$cancel_reason_category)
    {
        $flag = true;
        foreach ($sku_list as $sku) {
            $skuInfo = $this->purchase_db->select('*')->from('pur_purchase_cancelsku_reason')
                ->where(['sku' => $sku])->get()->row_array();
            if (!empty($skuInfo)) {
                $update =  array(
                    'sku' => $sku,
                    'cancel_reason' => $cancel_reason,//作废原因
                    'cancel_reason_category' => $cancel_reason_category,//作废原因类别
                    'edit_time'=>date('Y-m-d H:i:s',time())
                );
                $result=$this->purchase_db->where('sku',$sku)->update('pur_purchase_cancelsku_reason',$update);

            } else {
                $insert = array(
                    'sku' => $sku,
                    'cancel_reason' => $cancel_reason,//作废原因
                    'cancel_reason_category' => $cancel_reason_category,//作废原因类别
                    'edit_time'=>date('Y-m-d H:i:s',time())
                );
                $result = $this->purchase_db->insert('pur_purchase_cancelsku_reason',$insert);
            }
            if (empty($result)) {
                $flag =false;

            }


        }
        return $flag;

    }

    /*
     * @desc 获取七天内未过期的作废原因
     * @params string $sku string $cancel_time
     * @return string $cancel_reason
     */
    public function get_noexpired_reason($sku=null,$cancel_reason=null)
    {

        $cancel_return = null;
        if ($sku) {//如果sku存在就是采购需求生成的时候判断是否有拒绝原因
            if(is_array($sku)){
                $cancel_log_list = $this->purchase_db->select('*')
                    ->where_in('sku',$sku)
                    ->order_by('id','desc')
                    ->get('purchase_cancelsku_reason')
                    ->result_array();

                if($cancel_log_list){
                    foreach($cancel_log_list as $value){
                        if (!empty($value)) {
                            if (strtotime("-7 day")<=strtotime($value['edit_time'])) {
                                $cancel_return[$value['sku']]['cancel_reason'] = $value['cancel_reason'];
                                $cancel_return[$value['sku']]['cancel_reason_category'] = $value['cancel_reason_category'];
                            }
                        }
                    }
                }

            }else{
                $cancel_log = $this->purchase_db->select('*')
                    ->where('sku',$sku)
                    ->order_by('id','desc')
                    ->get('purchase_cancelsku_reason')
                    ->row_array();

                if (!empty($cancel_log)) {
                    if (strtotime("-7 day")<=strtotime($cancel_log['edit_time'])) {
                        $cancel_return['cancel_reason'] = $cancel_log['cancel_reason'];
                        $cancel_return['cancel_reason_category'] = $cancel_log['cancel_reason_category'];

                    }
                }
            }
        } else {//通过该需求的拒绝时间来判断是否需要显示他的作废原因
            $cancel_reason_arr = explode('-',trim($cancel_reason));
            $cancel_time = ( isset($cancel_reason_arr[count($cancel_reason_arr)-4])&&isset($cancel_reason_arr[count($cancel_reason_arr)-2]) )?$cancel_reason_arr[count($cancel_reason_arr)-4].'-'.$cancel_reason_arr[count($cancel_reason_arr)-3].'-'.$cancel_reason_arr[count($cancel_reason_arr)-2]:'';
            if(!empty($cancel_time)){
                if (strtotime("-7 day")<=strtotime($cancel_time)) {//七天内使用这个原因
                    $cancel_return  = $cancel_reason;
                }

            }

        }

        return $cancel_return;


    }


//修改代采还是非代采
    public function change_suggest_purchasing($sku,$is_purchasing){

        //根据sku 查询还未审核的采购单及其关联的需求单号
        $query = $this->purchase_db->from('purchase_suggest_map as smp');
        $query = $query->join('purchase_order as od','od.purchase_number=smp.purchase_number','left');
        $query = $query->join('purchase_suggest as su','su.demand_number=smp.demand_number and su.sku=smp.sku','left');
        $query = $query->join('product as pd','pd.sku=smp.sku','left');
        $query = $query->where('su.sku', $sku);
        $query = $query->where('smp.sku', $sku);
        $query = $query->where_in('purchase_order_status', [PURCHASE_ORDER_STATUS_WAITING_QUOTE]);
        $query = $query->select('su.demand_number,su.sku,su.id');
        $purchase_order_infos  = $query->get()->result_array();

        if(empty($sku) || empty($is_purchasing)){
            return;
        }
        if ( empty($purchase_order_infos)){
            return;
        }
        if (!empty($purchase_order_infos)){
            foreach ($purchase_order_infos as $key => $value) {
                $suggest_update[] = [
                    'id' => $value['id'],
                    'is_purchasing' => $is_purchasing,//是否代采
                ];

            }
            if(!empty($suggest_update)){
                $update_res_sug_update = $this->purchase_db->update_batch('purchase_suggest', $suggest_update,'id');
            }

        }
    }

    /**
     * 备货单获取SKU预计到货时间
     * @param : skus   array  商品SKU
     * @author: luxu
     **/
    public function get_suggest_scree( $skus )
    {
        return $this->purchase_db->from("product_scree")->where("apply_remark",4)->where("estimate_time>",date("Y-m-d H:i:s",time()))->where_in("sku",$skus)->select("sku,estimate_time")->get()->result_array();
    }

    /**
     * SKU 备货单是否处于锁单状态
     * @param $skus   string   商品SKU
     **/
    public function get_suggest_lock($skus = NULL,$ids = NULL){

        $query = $this->purchase_db->from("purchase_suggest")->where("lock_type",LOCK_SUGGEST_ENTITIES);
        if( NULL != $skus){
            $query->where_in("sku",$skus);
        }

        if( NULL != $ids){
            $query->where_in("id",$ids);
        }
        return $query->get()->result_array();
    }
    /**
     * 通过采购单信息和SKU 查询对应备货单号
     * @param $purchase_number   string  采购单编号
     *        $sku               string|array  SKU
     * @time:2020/3/17
     * @author:luxu
     **/
    public function getSuggestDemand($purchase_number = NULL,$sku=NULL){

        $query = $this->purchase_db->from("purchase_suggest_map");
        if( NULL != $purchase_number ){
            if(is_array($purchase_number)){
                $query->where_in("purchase_number", $purchase_number);
            }else {
                $query->where("purchase_number", $purchase_number);
            }
        }

        if( NULL != $sku){
            if(is_array($sku)) {
                $query->where_in("sku", $sku);
            }else{
                $query->where("sku",$sku);
            }
        }
        $demandNumbers = $query->select("id,demand_number,sku")->get()->result_array();
        return $demandNumbers;
    }

    /**
     * 通过备货单号获取备货单是否在锁单中
     * @param $demandNumbers  string|array  备货单号
     * @author:luxu
     * @time:2020/3/17
     **/
    public function getDemandLock($demandNumbers){

        $result = $this->purchase_db->from("purchase_suggest")->where_in("demand_number",$demandNumbers)->where("lock_type",LOCK_SUGGEST_ENTITIES)->select("demand_number")->get()->result_array();
        return $result;
    }

    /**
     * 设置需求单是否推送门户字段
     * @param : suggest_list   array   备货单信息
     *
     **/
    public function setSuggestGateWay($suggest_list){
        $suggestSupplierCodes = array_filter(array_column( $suggest_list,"supplier_code"));
        if( !empty($suggestSupplierCodes) ){
            $gateway = $this->Supplier_model->getSupplierMessage($suggestSupplierCodes);
            if(!empty($gateway)){

                $gatewayCodes = array_column( $gateway,"supplier_code");
                $isGateWaySuggest = [];
                foreach($suggest_list as $suggest_list_key=>$suggest_list_value){

                    if( in_array($suggest_list_value['supplier_code'],$gatewayCodes)){

                        $isGateWaySuggest[] = $suggest_list_value['id'];
                    }
                }

                if(!empty($isGateWaySuggest)){

                    $update = array(
                        'is_gateway' =>1
                    );

                    $result = $this->purchase_db->where_in("id",$isGateWaySuggest)->update("purchase_suggest",$update);
                }
            }
        }
    }

    /**
     * 获取备货是否退税查询
     * @params $ids   array   备货单ID
     * @author :luxu
     * @time:2020/4/27
     **/
    public function getSuggestIsDrawback($ids){

        if(!is_array($ids)){

            $ids =  [$ids];
        }
        $result = $this->purchase_db->from("purchase_suggest")->where_in("id",$ids)->where("is_drawback",1)
            ->select("demand_number")->get()->result_array();
        if(!empty($result)){

            $demandNumbers = array_column($result,"demand_number");
            return implode(",",$demandNumbers);
        }
        return NULL;
    }

    /**
     *
     * @author Manson
     */
    public function get_demand_info($demand_number_list)
    {
        $result = $this->purchase_db->select('suggest_order_status, demand_number')
            ->where_in('demand_number',$demand_number_list)
            ->from($this->table_name)
            ->get()->result_array();
        return $result;
    }

    /**
     * 获取锁单中的备货单
     */
    public function get_demand_lock_data($pur_number, $sku=[])
    {
        $res = [];return $res;
        if(empty($pur_number) || empty($sku))return $res;
        if(!is_array($pur_number))$pur_number = [$pur_number];
        $data = $this->purchase_db->from('purchase_suggest_map as map')
            ->select("map.demand_number,map.sku")
            ->join('pur_purchase_suggest as su', 'map.demand_number=su.demand_number', 'left')
            ->where("su.lock_type=", LOCK_SUGGEST_ENTITIES)
            ->where_in("map.purchase_number", $pur_number)
            ->get()
            ->result_array();
        if($data && count($data) > 0){
            foreach ($data as $val){
                if(!empty($val['sku']) && in_array($val['sku'], $sku))$res[] = $val['demand_number'];
            }
        }
        return $res;
    }

    /**
     * 通过备货单获取id
     * @params $demand_number  array  备货单号
     * @author:luxu
     * @time:2021年1月16号
     * 'id' => $prevIds,
    'demand_number' => $add_data['demand_number'],//备货单号
    'audit_status' => SUGGEST_AUDITED_PASS,//审核通过
    'audit_time' => date('Y-m-d H:i:s',time()),//审核时间
    'business_line' => $add_data['purchase_type_id'],//业务线
     **/
    public function get_demand_number_ids($demand_number){
        $result =$this->purchase_db->from("purchase_suggest")->where_in("demand_number",$demand_number)->select("id,demand_number,audit_status,purchase_type_id AS business_line")->get()->result_array();
        return $result;
    }

    /**
     * 获取需求单配置信息 30708 一键合单(9)基础配置增加备货单自动生成
     * @param
     * @author:luxu
     **/

    public function get_demand_config(){

        return $this->purchase_db->from("purchase_demand_config")->get()->result_array();
    }

    public function save_demand_cofig($id,$status){

        $update =[

            'status' => $status,
            'create_time' => date("Y-m-d H:i:s",time()),
            'username' =>getActiveUserName()
        ];

        return $this->purchase_db->where("id",$id)->update('purchase_demand_config',$update);
    }

    /**
     * 40401 计划系统推送需求效率优化202108版
     * 计划系统推送手工单存储到MONGDB中
     * @author:luxu
     * @time:2021年8月12号
     **/
    public function receive_suggest_data_from_erp_mongodb($data){

        $ci = get_instance();
        //获取redis配置
        $ci->load->config('mongodb');
        $host = $ci->config->item('mongo_host');
        $port = $ci->config->item('mongo_port');
        $user = $ci->config->item('mongo_user');
        $password = $ci->config->item('mongo_pass');
        $author_db = $ci->config->item('mongo_db');
        $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
        $author_db = $author_db;
        $success_list = [];
        foreach($data as $data_key=>&$data_value){
            $data_value['is_use'] = 0;
            $data_value['create_time'] = date("Y-m-d H:i:s",time());
            $data_value['demand_number'] = get_prefix_new_number('RD');

            $bulk = new MongoDB\Driver\BulkWrite();
            $mongodb_result = $bulk->insert($data_value);
            $result = $mongodb->executeBulkWrite("{$author_db}.plan_erp_datas", $bulk);
            $success_list[$data_value['erp_id']] = $data_value['demand_number'];
        }

        $return['code'] = true;
        $return['data']['success_list'] = $success_list;
        $return['data']['error_list'] = [];
        return $return;
    }

    /**
     * 作废需求单
     * @author:luxu
     * @time:2021年9月3号
     **/
    public function del_purchase_demand($ids,$where=array()){
        if(!empty($where)) {
       $result = $this->purchase_db->from("purchase_demand")->where_in("id",$ids)->where($where)->get()->result_array();
        }else{
            $result = $this->purchase_db->from("purchase_demand")->where_in("id", $ids)->get()->result_array();
        }
       return $result;
    }

    public function update_purchase_demand($ids,$cancel_reason_category){

        $result = $this->purchase_db->where_in("id",$ids)->update('purchase_demand',['demand_status'=>7,'cancel_reason_category'=>$cancel_reason_category]);
       return $result;
    }

    public function get_cancel_string($cancel_reason_category){
        $reasonString = $this->purchase_db->from("reason_config")->where("id",$cancel_reason_category)->select("reason_name")->get()->row_array();
        return $reasonString;
    }
    public function get_demand_data($demandNumber,$lock_type){
        $result = $this->purchase_db->from("purchase_suggest")->where_in("demand_number",$demandNumber)
            ->where("lock_type",$lock_type)->select("demand_number")->get()->result_array();
        return $result;
    }
}