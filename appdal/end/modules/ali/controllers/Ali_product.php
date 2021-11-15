<?php
/**
 * 1688 商品操作控制器
 * User: Jolon
 * Date: 2019/03/20 10:00
 */
class Ali_product extends MY_Controller {

    public function __construct(){
        parent::__construct();

        $this->load->library('alibaba/AliProductApi');
        $this->load->library('alibaba/AliOrderApi');
        $this->load->library('alibaba/AliSupplierApi');
        $this->load->model('product/Product_model');
        $this->load->model('Ali_product_model');
        $this->load->helper('common');
    }

    /**
     * 关联1688商品  加载默认数据
     */
    public function preview_product_info(){
        $skus  = $this->input->get_post('skus');
        $level = $this->input->get_post('level');// 级别：1.产品列表关联操作，2.采购单页面【1688下单】【编辑链接】操作
        $level = empty($level) ? 1 : $level;

        if(empty($skus)){
            $this->error_json('参数[skus]缺失');
        }
        if(!is_array($skus)){
            $this->error_json('参数[skus]必须是数组');
        }

        $this->load->model('product/product_update_log_model','product_update_log');
        foreach($skus as $sku){
            // 获取 已关联的 商品信息
            $have = $this->Ali_product_model->get_ali_product_one(['sku' => $sku]);
            if($have){
                $current_relate = $have;
            }else{
                $current_relate = null;
            }

            $product_info = $this->Product_model->get_product_info($sku);
            if(empty($product_info)){
                $this->error_json("SKU[$sku]不存在");
            }
            if ($level == 2 and $this->product_update_log->check_in_audit($sku)) {
                $this->error_json( $sku . ' 的基本信息在产品列表中处于审核中，请直接前往产品列表中修改');
            }

            $my_sku_data = [// 己方SKU信息
                'sku'              => $sku,
                'product_img_url'  => erp_sku_img_sku($product_info['product_img_url']),
                'product_name'     => $product_info['product_name'],
                'sales_attributes' => $this->Ali_product_model->convert_sale_attribute($product_info['sale_attribute']),// 销售属性
                'supplier_code'    => $product_info['supplier_code'],
                'supplier_name'    => $product_info['supplier_name'],
                'product_cn_link'  => $product_info['product_cn_link'],// 采购链接
            ];
            $returnData[$sku]['current_relate'] = $current_relate;
            $returnData[$sku]['my_sku_data']  = $my_sku_data;

            $product_id = $have?$have['product_id']:'';
            $ali_product = $this->Ali_product_model->_parse_product($product_id);
            if($ali_product['code']){
                $ali_supplier = $ali_product['data']['ali_supplier'];
                $ali_product  = $ali_product['data']['ali_product'];

                $ali_sku_data = [// 阿里 对应的 商品信息
                    'productId'    => $product_id,
                    'productType'  => isset($ali_product['productType'])?$ali_product['productType']:'',
                    'categoryID'   => isset($ali_product['categoryID'])?$ali_product['categoryID']:'',
                    'image'        => isset($ali_product['image'])?$ali_product['image']:'',
                    'main_image'   => isset($ali_product['main_image'])?$ali_product['main_image']:'',
                    'supplierName' => isset($ali_supplier['data'])?$ali_supplier['data']:'',
                    'productName'  => isset($ali_product['subject'])?$ali_product['subject']:'',
                ];
                $ali_sku_data['skuAttributeList'] = isset($ali_product['skuAttributeList'])?$ali_product['skuAttributeList']:[];
            }else{
                $ali_sku_data['skuAttributeList'] = null;
            }

            $returnData[$sku]['ali_sku_data'] = $ali_sku_data;
        }

        $this->success_json($returnData);
    }

    /**
     * 关联1688商品 根据商品ID查询1688商品信息
     */
    public function get_product_info(){
        $productIds = $this->input->get_post('productIds');
        if(empty($productIds) or !is_json($productIds)){
            $this->error_json('参数[ProductIds]缺失或不是JSON');
        }
        $productIds = json_decode($productIds,true);
        if(!is_array($productIds)){
            $this->error_json('参数[productIds]数据转化为数组失败');
        }
        $skus = array_keys($productIds);
        $returnData = [];
      
        foreach($skus as $sku){
            $sku  = strval($sku);
            $have = $this->Ali_product_model->get_ali_product_one(['sku' => $sku]);
            if($have){
                $current_relate = $have;
            }else{
                $current_relate = null;
            }
            $returnData[$sku]['current_relate'] = $current_relate;

            $product_id = isset($productIds[$sku])?$productIds[$sku]:'';
            $product_id = $this->aliproductapi->parseProductIdByLink($product_id);
            if(empty($product_id['code'])){
                $this->error_json("SKU[$sku] ".$product_id['errorMsg']);
            }else{
                $product_id = $product_id['data'];
            }
      
            $product_info = $this->Product_model->get_product_info($sku);
            if(empty($product_info)){
                $this->error_json("SKU[$sku]不存在");
            }
            $sale_attribute = isset($product_info['sale_attribute'])?$product_info['sale_attribute']:'';
            $sale_attribute = $this->Ali_product_model->convert_sale_attribute($sale_attribute);

            $ali_product = $this->Ali_product_model->_parse_product($product_id);
            if(empty($ali_product) or $ali_product['code'] == false){
                $this->error_json($ali_product['message']);
            }

            $ali_supplier = $ali_product['data']['ali_supplier'];
            $ali_product  = $ali_product['data']['ali_product'];

            $my_sku_data = [// 己方SKU信息
                'sku'              => $sku,
                'product_img_url'  => erp_sku_img_sku($product_info['product_img_url']),
                'product_name'     => $product_info['product_name'],
                'sales_attributes' => $sale_attribute,// 销售属性
                'supplier_code'    => $product_info['supplier_code'],
                'supplier_name'    => $product_info['supplier_name'],
                'product_cn_link'  => $product_info['product_cn_link'],// 采购链接
            ];

            $ali_sku_data = [// 阿里 对应的 商品信息
                'productId'    => $product_id,
                'productType'  => isset($ali_product['productType'])?$ali_product['productType']:'',
                'categoryID'   => isset($ali_product['categoryID'])?$ali_product['categoryID']:'',
                'image'        => isset($ali_product['image'])?$ali_product['image']:'',
                'main_image'   => isset($ali_product['main_image'])?$ali_product['main_image']:'',
                'supplierName' => isset($ali_supplier['data'])?$ali_supplier['data']:'',
                'productName'  => isset($ali_product['subject'])?$ali_product['subject']:'',
            ];

            $ali_sku_data['skuAttributeList'] = isset($ali_product['skuAttributeList'])?$ali_product['skuAttributeList']:[];

            $returnData[$sku]['my_sku_data']  = $my_sku_data;
            $returnData[$sku]['ali_sku_data'] = $ali_sku_data;

        }

        $this->success_json($returnData);
    }


    /**
     * 关联1688商品  提交数据-关联产品
     */
    public function relate_ali_sku(){
        $productIds = $this->input->get_post('productIds');
        $skuIds     = $this->input->get_post('skuIds');
        $specIds    = $this->input->get_post('specIds');
        $level      = $this->input->get_post('level');// 级别：1.产品列表关联操作，2.采购单页面【1688下单】【编辑链接】操作
        $level      = empty($level) ? 1 : $level;

        if(empty($productIds) or !is_json($productIds)){
            $this->error_json('参数[productIds]缺失或不是JSON');
        }
        if(empty($skuIds) or !is_json($skuIds)){
            $this->error_json('参数[skuIds]缺失或不是JSON');
        }
        if(empty($specIds) or !is_json($specIds)){
            $this->error_json('参数[specIds]缺失或不是JSON');
        }
        $productIds = json_decode($productIds,true);
        $specIds = json_decode($specIds,true);
        $skuIds = json_decode($skuIds,true);

        if($level == 2){
            // 验证 SKU 是否在审核中
            $this->load->model('product/product_update_log_model','product_update_log');
            $this->load->model('supplier/supplier_model');
            foreach($productIds as $sku => $product_link){
                if ($this->product_update_log->check_in_audit($sku)) {
                    $this->error_json( $sku . ' 的基本信息在产品列表中处于审核中，请直接前往产品列表中修改');
                }
                $ali_product = $this->aliproductapi->parseProductIdByLink($product_link);
                if(empty($ali_product['code'])){
                    $this->error_json( $sku.' '.$ali_product['errorMsg']);
                    continue;
                }else{
                    $product_id = $ali_product['data'];
                }
                // 验证 该链接的供应商店铺ID是不是与采购系统的供应商的店铺ID是同一个
                // 获取 产品信息的供应商
                $productInfo = $this->Product_model->get_product_info($sku);
                $supplierInfo = $this->supplier_model->get_supplier_info($productInfo['supplier_code'],false);
                $ali_supplier = $this->aliproductapi->getSupplierByProductId($product_id);
                if(empty($ali_supplier['code'])){
                    $this->error_json( $sku.' '.$ali_supplier['errorMsg']);
                }
                if(empty($supplierInfo['shop_id']) or $supplierInfo['shop_id'] != $ali_supplier['data']['loginId']){
                    $this->error_json( $sku.' 不是同一个供应商[loginId]，变更供应商请直接前往产品列表中修改');
                }
            }
        }

        $result = $this->Ali_product_model->relate_ali_sku($productIds,$skuIds,$specIds,$level);
        if($result['code']){
            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 关联1688商品  解除关联的产品信息
     */
    public function remove_relate_ali_sku(){
        $id     = $this->input->get_post('id');
        $result = $this->Ali_product_model->remove_relate_ali_sku($id);
        if($result['code']){
            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 关联1688商品 刷新商品信息
     */
    public function refresh_product_info(){
        $sku = $this->input->get_post('sku');
        if(empty($sku)){
            $this->error_json('参数[sku]缺失');
        }
        $result = $this->Ali_product_model->refresh_product_info($sku);
        if($result['code']){
            $data = $result['data'];
            $this->success_json($data,null,$result['message']);
        }else{
            $this->error_json($result['message']);
        }
    }


    /**
     * 获取 1688产品  简略信息
     * @param mixed $sku
     */
    public function get_ali_sample_product_info(){
        $link = $this->input->get_post('link');

        $return_data = [];

        $productId = $this->aliproductapi->parseProductIdByLink($link);
        if(empty($productId['code'])){
            $this->error_json($productId['errorMsg']);
        }else{
            $productId = $productId['data'];
        }
        
        $ali_product = $this->Ali_product_model->_parse_product($productId);
        if(empty($ali_product) or $ali_product['code'] == false){
            $this->error_json($ali_product['message']);
        }
        $ali_product      = $ali_product['data']['ali_product'];

        // 最小起订量
        $return_data['starting_qty']      = isset($ali_product['saleInfo']['minOrderQuantity'])?$ali_product['saleInfo']['minOrderQuantity']:'';
        $return_data['starting_qty_unit'] = isset($ali_product['saleInfo']['unit'])?$ali_product['saleInfo']['unit']:'';

        $this->success_json($return_data);
    }

    /**
     * 产品——跨境产品开发工具同款开发
     */
    public function get_pdt_tongkuan(){
        $product_link = $this->input->get_post('product_link');
        if(empty($product_link)){
            $this->error_json('参数[product_link]缺失');
        }
        $result = $this->aliproductapi->getPdtTongKuan($product_link);
        if($result['code']){
            $data = $result['data'];
            $this->success_json($data,null,$result['errorMsg']);
        }else{
            $this->error_json($result['errorMsg']);
        }
    }
}