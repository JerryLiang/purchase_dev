<?php
/**
 * 1688 商品数据模型
 * User: Jolon
 * Date: 2019/03/20 10:00
 */
class Ali_product_model extends Purchase_model {

    protected $table_name = 'ali_product';

    public function __construct(){
        parent::__construct();
        $this->load->library('alibaba/AliProductApi');
        $this->load->helper('common');

    }

    /**
     * 根据查询条件  获取已经管理的SKU信息
     * @param $where
     * @return array
     */
    public function get_ali_product_one($where){
        foreach($where as $key => $value){
            if(is_array($value)){
                $this->purchase_db->where_in($key,$value);
            }else{
                $this->purchase_db->where($key,$value);
            }
        }
        $product = $this->purchase_db->get($this->table_name)
            ->row_array();
        return $product;
    }

    /**
     * 根据商品 ID 获取1688 平台商品信息
     * @param $product_id
     * @return array
     */
    public function _parse_product($product_id){
        $return = ['code' => false,'message' => '','data' => []];
        if(empty($product_id)){
            $return['message'] = '商品ID缺失';
            return $return;
        }

        // 获取商品信息
        $ali_product = $this->aliproductapi->getProductInfo($product_id,true);
        if(isset($ali_product['code']) and empty($ali_product['code'])){
            $return['message'] = $ali_product['errorMsg'];
            return $return;
        }
        if(!isset($ali_product['data']['productInfo']) or empty($ali_product['data']['productInfo'])){
            $return['message'] = "商品[$product_id]信息获取失败";
            return $return;
        }
        $ali_product = $ali_product['data']['productInfo'];
        if(!array_key_exists('skuInfos',$ali_product)){// 商品属性不存在
            $return['message'] = "商品[$product_id]信息商品属性缺失";
            return $return;
        }
        if(empty($ali_product['skuInfos'])){// 自动生成一个属性
            $skuInfosTmp             = [];
            $saleInfo                = isset($ali_product['saleInfo']) ? $ali_product['saleInfo'] : [];
            $skuInfo['price']        = '';
            $skuInfo['skuId']        = $product_id;
            $skuInfo['specId']       = $product_id;
            $skuInfo['skuCode']      = $product_id;
            $skuInfo['amountOnSale'] = isset($saleInfo['amountOnSale']) ? $saleInfo['amountOnSale'] : 0;
            $skuInfo['retailPrice']  = isset($saleInfo['retailprice']) ? $saleInfo['retailprice'] : 0;
            $skuInfo['attributes']   = [
                ['attributeValue' => '单属性'],
                ['attributeValue' => '主商品'],
            ];

            $skuInfosTmp[] = $skuInfo;
        }
        $ali_product['description'] = '';// 不需要描述信息（文本信息，太大了）
        if(isset($ali_product['image']['images'])){
            $images = $ali_product['image']['images']?$ali_product['image']['images']:[];
            $images = "|".ALI_IMAGES_DOMAIN.implode("|".ALI_IMAGES_DOMAIN,$images);
            $images = array_filter(explode("|",$images));
            $ali_product['image']['images'] = array_values($images);
        }


        // 获取 商品的属性列表
        $skuInfos         = $ali_product['skuInfos']?$ali_product['skuInfos']:$skuInfosTmp;
        $saleInfo         = isset($ali_product['saleInfo'])?$ali_product['saleInfo']:[];
        $priceRanges      = isset($saleInfo['priceRanges'])?$saleInfo['priceRanges']:'';
        unset($ali_product['skuInfos']);
        $skuAttributeList = [];
        foreach($skuInfos as $skuInfo){
            $now_price = isset($skuInfo['price'])?$skuInfo['price']:'';
            $now_price = !empty($now_price)?$now_price:$this->calculateUnitPriceOfSteps($priceRanges,1);// 如果 商品没有自己特有的报价 则取阶梯价的第一个值

            $now_sku                 = [];
            $now_sku['productId']    = $product_id;
            $now_sku['skuId']        = isset($skuInfo['skuId'])?$skuInfo['skuId']:'';
            $now_sku['specId']       = isset($skuInfo['specId'])?$skuInfo['specId']:'';
            $now_sku['skuCode']      = isset($skuInfo['skuCode'])?$skuInfo['skuCode']:'';
            $now_sku['price']        = $now_price;
            $now_sku['priceRange']   = $priceRanges;
            $now_sku['amountOnSale'] = isset($skuInfo['amountOnSale'])?$skuInfo['amountOnSale']:'';
            $now_sku['retailPrice']  = isset($skuInfo['retailPrice'])?$skuInfo['retailPrice']:'';

            // 解析属性
            $attribute_tmp = '';
            $attributes    = isset($skuInfo['attributes'])?$skuInfo['attributes']:[];
            foreach($attributes as $attribute){
                $attribute_tmp .= $attribute['attributeValue'].'-';
            }
            $attribute_tmp        = trim($attribute_tmp, '-');
            $now_sku['attribute'] = $attribute_tmp;

            $skuAttributeList[] = $now_sku;
        }
        $ali_product['main_image']       = empty($images) ? '' : current($images);
        $ali_product['skuAttributeList'] = $skuAttributeList;

        // 1688供应商数据
        $ali_supplier = $this->aliproductapi->getSupplierByProductId($product_id);
        if(empty($ali_supplier['code'])){
            $return['message'] = $ali_supplier['errorMsg'];
            return $return;
        }
        $ali_supplier['data'] = $ali_supplier['data']['supplierName'];

        $info = ['ali_product' => $ali_product,'ali_supplier' => $ali_supplier];
        $return['data'] = $info;
        $return['code'] = true;
        return $return;
    }


    /**
     * 根据 数量所属阶梯 计算阶梯价
     * @param $priceRanges
     * @param $quantity
     * @return mixed|null
     */
    public function calculateUnitPriceOfSteps($priceRanges,$quantity){
        if(empty($priceRanges) or !is_array($priceRanges) or empty($quantity)) return null;

        $priceRanges = array_column($priceRanges, 'price', 'startQuantity');
        ksort($priceRanges);
        $price = current($priceRanges);
        foreach($priceRanges as $start_qty => $step_price){
            if($quantity >= $start_qty){
                $price = $step_price;
            }
        }

        return $price;
    }

    /**
     * 关联1688商品 - 保存提交的数据
     * @param array $productIds
     * @param array $skuIds
     * @param array $specIds
     * @param int $level 级别：1.产品列表关联操作，2.采购单页面【1688下单】【编辑链接】操作
     * @return array
     */
    public function relate_ali_sku($productIds,$skuIds,$specIds,$level = 1){
        $return = ['code' => false,'message' => '','data' => ''];

        try{
            $this->purchase_db->trans_begin();
            $skus = array_keys($productIds);

            foreach($skus as $sku){
                $sku       = strval($sku);// 数字类型转成字符串
                // 验证必须的数据
                $productId = isset($productIds[$sku]) ? $productIds[$sku] : '';
                $skuId     = isset($skuIds[$sku]) ? $skuIds[$sku] : '';
                $specId    = isset($specIds[$sku]) ? $specIds[$sku] : '';

                $product_relate_url = $productId;
                $productId = $this->aliproductapi->parseProductIdByLink($productId);
                if(empty($productId['code'])){
                    throw new Exception("SKU[$sku]".$productId['errorMsg']);
                }else{
                    $productId = $productId['data'];
                }
                if(empty($skuId)){
                    throw new Exception("SKU[$sku]参数[skuId]缺失");
                }
                if(empty($specId)){
                    throw new Exception("SKU[$sku]参数[specId]缺失");
                }
                // 验证是否已经关联了 1688商品（不能再次关联）
                $have = $this->get_ali_product_one(['sku' => $sku]);
                if($have){
                    $result_rm = $this->remove_relate_ali_sku(null,$sku);// 根据SKU取消
                    if(empty($result_rm['code'])){
                        throw new Exception($result_rm['message']);
                    }
                }

                // 验证SKU是否存在
                $product_info = $this->purchase_db->where('sku',$sku)->get('product')->row_array();
                if(empty($product_info)){
                    throw new Exception("SKU[$sku]不存在");
                }

                // 获取 1688平台商品信息
                $ali_product = $this->_parse_product($productId);
                if(empty($ali_product) or $ali_product['code'] == false){
                    throw new Exception($ali_product['message']);
                }
                $ali_supplier     = $ali_product['data']['ali_supplier'];
                $ali_product      = $ali_product['data']['ali_product'];
                $skuAttributeList = $ali_product['skuAttributeList'];
                $skuAttributeList = arrayKeyToColumn($skuAttributeList, 'specId');

                // 阶梯价格
                $priceRange         = isset($skuAttributeList[$specId]['priceRange']) ? $skuAttributeList[$specId]['priceRange'] : '';
                $min_order_qty      = isset($ali_product['saleInfo']['minOrderQuantity'])?$ali_product['saleInfo']['minOrderQuantity']:'0';
                $min_order_qty_unit = isset($ali_product['saleInfo']['unit'])?$ali_product['saleInfo']['unit']:'';
                $insert_data = [
                    'sku'               => $sku,
                    'product_name'      => $product_info['product_name'],
                    'product_img_url'   => erp_sku_img_sku($product_info['product_img_url']),
                    'supplier_code'     => $product_info['supplier_code'],
                    'supplier_name'     => $product_info['supplier_name'],
                    'ali_supplier_name' => $ali_supplier['data'],
                    'supplier_login_id' => $ali_product['supplierLoginId'],
                    'product_id'        => $productId,
                    'sku_id'            => $skuId,
                    'spec_id'           => $specId,
                    'sku_code'          => isset($skuAttributeList[$specId]['skuCode']) ? $skuAttributeList[$specId]['skuCode'] : '',
                    'price'             => isset($skuAttributeList[$specId]['price']) ? $skuAttributeList[$specId]['price'] : '',
                    'price_range'       => !empty($priceRange) ?json_encode($priceRange,JSON_UNESCAPED_UNICODE):'',
                    'amount_on_sale'    => isset($skuAttributeList[$specId]['amountOnSale']) ? $skuAttributeList[$specId]['amountOnSale'] : '',
                    'retail_price'      => isset($skuAttributeList[$specId]['retailPrice']) ? $skuAttributeList[$specId]['retailPrice'] : '',
                    'attribute'         => isset($skuAttributeList[$specId]['attribute']) ? $skuAttributeList[$specId]['attribute'] : '',
                    'product_type'      => isset($ali_product['productType'])?$ali_product['productType']:'',
                    'category_id'       => isset($ali_product['categoryID'])?$ali_product['categoryID']:'',
                    'category_name'     => isset($ali_product['categoryName'])?$ali_product['categoryName']:'',
                    'status'            => isset($ali_product['status'])?$ali_product['status']:'',
                    'biz_type'          => isset($ali_product['bizType'])?$ali_product['bizType']:'',
                    'min_order_qty'     => $min_order_qty,
                    'min_order_qty_unit'=> $min_order_qty_unit,
                    'sale_info'         => isset($ali_product['saleInfo'])?json_encode($ali_product['saleInfo'],JSON_UNESCAPED_UNICODE):'',
                    'quote_type'        => isset($ali_product['saleInfo']['quoteType'])?$ali_product['saleInfo']['quoteType']:'',
                    'subject'           => isset($ali_product['subject'])?$ali_product['subject']:'',
                    'image'             => json_encode($ali_product['image'],JSON_UNESCAPED_UNICODE),
                    'main_image'        => $ali_product['main_image'],
                    'product_relate_url'=> $product_relate_url,
                    'create_user_id'    => getActiveUserId(),
                    'create_user_name'  => getActiveUserName(),
                    'create_time'       => date('Y-m-d H:i:s'),
                ];

                // 保存关联数据
                $res = $this->purchase_db->insert($this->table_name,$insert_data);
                if(empty($res)){
                    throw new Exception('创建ALI关联记录时发生错误');
                }

                $this->load->model('product_model');
                $this->product_model->update_one($sku, ['is_relate_ali' => 1, 'product_cn_link' => $product_relate_url, 'is_invalid' => 0]);// 标记产品已经关联1688
                // 无需审核的 更新最新起订量（不要自动更新）
                // $this->product_model->change_min_order_qty($sku,$min_order_qty,$min_order_qty_unit,$product_info['starting_qty'],$product_info['starting_qty_unit'],'关联1688商品');

                if($level == 2){// 插入一条 SKU更新记录
                    $productIdOld = $this->aliproductapi->parseProductIdByLink($product_info['product_cn_link']);
                    $productIdOld = empty($productIdOld['code'])?'':$productIdOld['data'];
                    if(trim($productId) != trim($productIdOld)){
                        $this->load->model('product/product_update_log_model','product_update_log');
                        $this->load->model('product/product_line_model','product_line');

                        $params = [
                            'sku'                   => $sku,
                            'product_name'          => $product_info['product_name'],
                            'product_line_name'     => $this->product_line->get_product_line_name($product_info['product_line_id']),
                            'old_supplier_price'    => $product_info['purchase_price'],
                            'new_supplier_price'    => $product_info['purchase_price'],
                            'old_supplier_code'     => $product_info['supplier_code'],
                            'new_supplier_code'     => $product_info['supplier_code'],
                            'old_supplier_name'     => $product_info['supplier_name'],
                            'new_supplier_name'     => $product_info['supplier_name'],
                            'old_ticketed_point'    => $product_info['ticketed_point'],
                            'new_ticketed_point'    => $product_info['ticketed_point'],
                            'old_product_link'      => $product_info['product_cn_link'],
                            'new_product_link'      => $product_relate_url,
                            'old_starting_qty'      => $product_info['starting_qty'],
                            'old_starting_qty_unit' => $product_info['starting_qty_unit'],
                            'new_starting_qty'      => $min_order_qty,
                            'new_starting_qty_unit' => $min_order_qty_unit,
                            'old_ali_ratio_own'     => $product_info['ali_ratio_own'],
                            'old_ali_ratio_out'     => $product_info['ali_ratio_out'],
                            'new_ali_ratio_own'     => $product_info['ali_ratio_own'],
                            'new_ali_ratio_out'     => $product_info['ali_ratio_out'],
                            'create_user_id'        => !empty(getActiveUserId()) ? getActiveUserId() : '',
                            'create_user_name'      => !empty(getActiveUserName()) ? getActiveUserName() : '',
                            'create_time'           => date('Y-m-d H:i:s'),
                            'audit_status'          => PRODUCT_UPDATE_LIST_AUDIT_PASS,
                            'create_remark'         => '采购单页面【关联1688】',
                            'is_sample'             => 0,
                            'audit_level'           => '[]',
                            'old_coupon_rate'       => $product_info['coupon_rate'],
                            'new_coupon_rate'       => $product_info['coupon_rate'],
                        ];
                        $this->product_update_log->save_product_log_info($params);
                    }
                }


                operatorLogInsert(
                    [
                        'id'      => $sku,
                        'type'    => 'product',
                        'content' => '关联1688商品',
                        'detail'  => $productId.'-'.$skuId.'-'.$specId
                    ]
                );
                operatorLogInsert(
                    [
                        'id'      => $sku,
                        'type'    => 'product',
                        'content' => '关联1688商品',
                        'detail'  => '修改产品中文地址:'.$product_info['product_cn_link'].'改为 '.$product_relate_url
                    ]
                );


            }

            if($this->purchase_db->trans_status()){
                $this->purchase_db->trans_commit();

            }else{
                throw new Exception('事务提交出错');
            }
            $return['code'] = true;
            return $return;
        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * 关联1688商品 移除关联
     * @param $id
     * @param $sku
     * @return array
     */
    public function remove_relate_ali_sku($id = null,$sku = null){
        $return = ['code' => false,'message' => '','data' => ''];
        $where = [];
        if($id) $where['id'] = $id;
        if($sku){
            $sku       = strval($sku);// 数字类型转成字符串
            $where['sku'] = $sku;
        }
        if(empty($where)){
            $return['message'] = '参数错误';
            return $return;
        }

        $ali_product = $this->purchase_db->where($where)->get($this->table_name)->row_array();
        if(empty($ali_product)){
            $return['message'] = '记录未找到';
            return $return;
        }

        $sku = $ali_product['sku'];
        $result = $this->purchase_db->where($where)->delete($this->table_name);

        $this->purchase_db->where('sku',$sku)->update('product',['is_relate_ali' => 0]);
        operatorLogInsert(
            [
                'id'      => $sku,
                'type'    => 'product',
                'content' => '解除关联1688商品',
                'detail'  => $ali_product
            ]
        );

        $return['code'] = true;
        return $return;
    }

    /**
     * 1688商品  刷新SKU关联的商品信息
     * @param $sku
     * @return array
     */
    public function refresh_product_info($sku){
        $return = ['code' => false,'message' => '','data' => ''];

        try{
            $relate_data = $this->get_ali_product_one(['sku' => $sku]);
            if(empty($relate_data)){
                throw new Exception('该SKU未关联1688商品');
            }

            $product_id = $relate_data['product_id'];

            // 获取 1688平台商品信息
            $result = $this->_parse_product($product_id);
            if(empty($result) or $result['code'] == false){
                throw new Exception($result['message']);
            }

            $ali_product      = $result['data']['ali_product'];
            $ali_supplier     = $result['data']['ali_supplier'];
            $skuAttributeList = $ali_product['skuAttributeList'];

            $min_order_qty      = isset($ali_product['saleInfo']['minOrderQuantity']) ? $ali_product['saleInfo']['minOrderQuantity'] : '0';
            $min_order_qty_unit = isset($ali_product['saleInfo']['unit']) ? $ali_product['saleInfo']['unit'] : '';

            // 验证 当前 关联的商品属性是否依然存在
            $flag = false;
            foreach($skuAttributeList as $attributeValue){
                if($attributeValue['skuId'] == $relate_data['sku_id']
                    and $attributeValue['specId'] == $relate_data['spec_id'] ){
                    $flag = true;
                    break;
                }
            }
            if($flag === false or empty($attributeValue)){
                throw new Exception("此SKU对应的商品[$product_id]下属性[{$relate_data['spec_id']}]不存在");
            }
            $update = [
                'min_order_qty'      => $min_order_qty,
                'min_order_qty_unit' => $min_order_qty_unit,
            ];
            if($ali_supplier['data']){
                $update['ali_supplier_name'] = $ali_supplier['data'];
            }else{
                $update['ali_supplier_name'] = '';
            };
            if(isset($attributeValue) and isset($attributeValue['price']) and $attributeValue['price']){
                $update['price'] = $attributeValue['price'];
            }else{
                $update['price'] = 0;
            }
            
            if(isset($attributeValue) and $attributeValue){
                $update['attribute']        = $attributeValue['attribute'];
                $update['retail_price']     = $attributeValue['retailPrice'];
                $update['amount_on_sale']   = $attributeValue['amountOnSale'];
                $update['price_range']      = json_encode($attributeValue['priceRange']);
            }else{
                $update['attribute']        = '';
                $update['retail_price']     = 0;
                $update['amount_on_sale']   = 0;
                $update['price_range']      = json_encode([]);
            }

            if( !$this->update_one($relate_data['id'], $update)){
                throw new Exception('商品变更失败');
            }

            $update['price_range'] = $this->convert_price_range($update['price_range'],$update['price'],$update['min_order_qty']);
            $update['ali_min_order_qty'] = $update['min_order_qty'];
            $update['ali_min_order_qty_unit'] = $update['min_order_qty_unit'];
            unset($update['amount_on_sale'],$update['retail_price'],$update['min_order_qty'],$update['min_order_qty_unit']);

            $return['code']    = true;
            $return['data']    = $update;
            $return['message'] = "商品已变更成功";
            return $return;
        }catch(Exception $e){

            $return['message'] = $e->getMessage();
            return $return;
        }
     
    }

    /**
     * 更新 1688商品关联记录
     * @param $id
     * @param $update
     * @return bool
     */
    public function update_one($id,$update){
        $res = $this->purchase_db
            ->where('id',$id)
            ->update($this->table_name,$update);
        return $res;
    }

    /**
     * 阶梯价格转换
     * @param array|string $price_range  阶梯价格
     * @param mixed $special_price       特定的价格
     * @param mixed $min_order_qty       最小起订量
     * @return array
     */
    public function convert_price_range($price_range, $special_price = 0,$min_order_qty = 1){
        if(is_json($price_range)) $price_range = json_decode($price_range,true);

        // Start_1:特定的价格 来生成 阶梯价（针对 某种特殊商品：既有阶梯价又有SKU报价，如果报价 不等于 1688阶梯价的第一个值，则表示该商品有自己特有的价格）
        // 参考 self->relate_ali_sku() 关联商品方法中 price 的取值
        if(!empty($special_price) and (intval($special_price * 100) != 0) ){
            foreach($price_range as $key_sp => $value_sp){
                if($key_sp != 0) continue;
                $price_range_special_tmp = array_column($price_range, 'price', 'startQuantity');
                ksort($price_range_special_tmp);
                $price = current($price_range_special_tmp);

                if(intval($special_price * 100) !== intval($price * 100)){
                    $price_range = [
                        [
                            'startQuantity' => $min_order_qty,
                            'price' => $special_price
                        ]
                    ];
                }
            }
        }
        // End_1

        $price_range_tmp = [];
        if($price_range and is_array($price_range)){
            $startQuantity = array_column($price_range, 'startQuantity');
            $price_range   = array_column($price_range, 'price', 'startQuantity');
            sort($startQuantity);
            ksort($price_range);
            foreach($price_range as $s_qty => $price){
                $min_count_tmp     = $s_qty;
                $price_tmp         = [
                    'startQuantity'  => $min_count_tmp,
                    'endQuantity'    => 0,
                    'price'          => $price,
                    'price_range_cn' => '',
                ];
                $price_range_tmp[] = $price_tmp;
            }

            foreach($price_range_tmp as $key => &$value){
                $key_1 = $key+1;
                $value['endQuantity'] = isset($price_range_tmp[$key_1])?$price_range_tmp[$key_1]['startQuantity']:'infinite';

                if($value['endQuantity'] === 'infinite'){
                    $value['price_range_cn'] = "≥{$value['startQuantity']}";
                }else{
                    $value['price_range_cn'] = "{$value['startQuantity']} - {$value['endQuantity']}";
                }
            }
        }

        return $price_range_tmp;
    }

    /**
     * SKU产品属性转换
     * @param $sale_attribute
     * @return string
     */
    public function convert_sale_attribute($sale_attribute){
        if(is_json($sale_attribute))
            $sale_attribute = json_decode($sale_attribute, true);

        $sale_attribute_tmp = '';
        if($sale_attribute and is_array($sale_attribute)){
            foreach($sale_attribute as $key => $value){
                if(is_array($value)){
                    $sale_attribute_tmp .= current($value)."-";
                }else{
                    $sale_attribute_tmp .= $value."-";
                }
            }
            $sale_attribute_tmp = trim($sale_attribute_tmp,"-");
        }
        return $sale_attribute_tmp;

    }


    /**
     * 自动刷新 1688产品信息
     * @param mixed $last_date
     * @param mixed $sku
     */
    public function autoUpdateAliProduct($last_date = null,$sku = null){
        if(empty($last_date)){
            $last_date = date('Y-m-d',strtotime('-1 days'));
        }
        $product_list = $this->purchase_db->select('id,sku,product_id,spec_id')
            ->where("update_time<=",$last_date)
            ->get($this->table_name,100)
            ->result_array();
        if($sku){// 查询指定SKU
            $product_list = $this->purchase_db->select('id,sku,product_id,spec_id')
                ->where("sku",$sku)
                ->get($this->table_name)
                ->result_array();
        }

        if(empty($product_list)){
            echo '没有需要更新的数据';exit;
        }

        $count   = count($product_list);
        $success = 0;
        foreach($product_list as $product){
            try{
                $id        = $product['id'];
                $productId = $product['product_id'];
                $sku       = $product['sku'];
                $specId    = $product['spec_id'];

                // 获取商品信息
                $ali_product = $this->aliproductapi->getProductInfo($productId);
                if(isset($ali_product['code']) and empty($ali_product['code'])){
                    throw new Exception($ali_product['errorMsg']);
                }
                if(!isset($ali_product['data']['productInfo']) or empty($ali_product['data']['productInfo'])){
                    throw new Exception("商品[$productId]信息获取失败");
                }

                $ali_product = $ali_product['data']['productInfo'];
                if(isset($ali_product['attributes']))   unset($ali_product['attributes']);
                if(isset($ali_product['skuInfos']))     unset($ali_product['skuInfos']);
                if(isset($ali_product['description']))  unset($ali_product['description']);


                if(!isset($ali_product['saleInfo'])){
                    throw new Exception("商品[$productId]信息获取失败");
                }

                $min_order_qty      = isset($ali_product['saleInfo']['minOrderQuantity']) ? $ali_product['saleInfo']['minOrderQuantity'] : '0';
                $min_order_qty_unit = isset($ali_product['saleInfo']['unit']) ? $ali_product['saleInfo']['unit'] : '';

                $update_data = [
                    'min_order_qty'      => $min_order_qty,
                    'min_order_qty_unit' => $min_order_qty_unit,
                    'update_user_id'     => '1',
                    'update_user_name'   => 'system',
                    'update_time'        => date('Y-m-d H:i:s'),
                ];
                // 保存关联数据
                $res = $this->purchase_db->update($this->table_name, $update_data, "id={$id}");

                $this->purchase_db->where('sku', $sku)->update('product',
                       [
                           'is_relate_ali'      => 1,
                           'starting_qty'       => $min_order_qty,
                           'starting_qty_unit'  => $min_order_qty_unit,
                       ]);// 标记产品已经关联1688

                $success++;
            }catch(\Exception $exception){
                $update_data = [
                    'update_user_id'     => '1',
                    'update_user_name'   => 'system',
                    'update_time'        => date('Y-m-d H:i:s'),
                ];
                $this->purchase_db->update($this->table_name, $update_data, "id={$id}");
                echo $exception->getMessage().'<br/>';
            }
        }

        echo '更新完毕：总数 '.$count.'，成功  '.$success;exit;
    }


    /**
     * 根据查询条件  获取已经管理的SKU信息
     * @param $where
     * @return array
     */
    public function get_ali_product_group_concat($where){
        $product = $this->purchase_db->select('GROUP_CONCAT(sku) sku,GROUP_CONCAT(attribute) attribute')
            ->where($where)
            ->get($this->table_name)
            ->row_array();
        return $product;
    }

    /**
     * 刷新 供应商名称、供应商ID不一致的问题
     * @param null $sku
     * @return bool
     */
    public function verify_supplier_equal($sku = null){
        $session_key = 'verify_supplier_equal';
        $latest_id = $this->rediss->getData($session_key);
        if (empty($latest_id)) {
            $latest_id = 1;
        }

        $this->purchase_db->select('P.id,AP.sku,AP.product_id,AP.ali_supplier_name,AP.supplier_login_id,P.supplier_code,SU.supplier_name,SU.shop_id')
            ->from('ali_product AS AP')
            ->join('product AS P','P.sku=AP.sku','LEFT')
            ->join('supplier AS SU','SU.supplier_code=P.supplier_code','LEFT')
            ->where('P.is_relate_ali',1);

        if($sku){
            $this->purchase_db->where('AP.sku',$sku);
            $sku_list = $this->purchase_db->order_by('P.id ASC')->get()->result_array();
            if(empty($sku_list)){// 该SKU还没有关联 1688商品，则要根据采购链接调取 1688供应商信息
                $sku_info = $this->purchase_db->select('P.sku,P.supplier_code,P.product_cn_link,SU.supplier_name,SU.shop_id')
                    ->from('product AS P')
                    ->join('supplier AS SU','SU.supplier_code=P.supplier_code','LEFT')
                    ->where('P.sku',$sku)
                    ->get()
                    ->row_array();
                if(empty($sku_info)){
                    return false;
                }

                $productId = $this->aliproductapi->parseProductIdByLink($sku_info['product_cn_link']);
                if($productId['code'] === false){
                    $sku_info['product_id']        = '';
                    $sku_info['ali_supplier_name'] = '';
                    $sku_info['supplier_login_id'] = '';
                }else{
                    $sku_info['product_id'] = $productId['data'];
                    $supplierInfo = $this->aliproductapi->getSupplierByProductId($productId['data']);
                    if($supplierInfo['code'] === false){
                        $sku_info['ali_supplier_name'] = '';
                        $sku_info['supplier_login_id'] = '';
                    }else{
                        $sku_info['supplier_login_id'] = isset($supplierInfo['data']['loginId'])?$supplierInfo['data']['loginId']:'';
                        $sku_info['ali_supplier_name'] = isset($supplierInfo['data']['supplierName'])?$supplierInfo['data']['supplierName']:'';
                    }
                }
                $sku_list = [$sku_info];// 组装数据格式
            }
        }else{
            $sku_list = $this->purchase_db->where('P.id >',$latest_id)
                ->order_by('P.id ASC')
                ->limit(1000)
                ->get()
                ->result_array();

            $id_list = array_column($sku_list,'id');
            sort($id_list);
            $now_latest_id = end($id_list);
            if($now_latest_id){
                $this->rediss->setData($session_key, $now_latest_id, 86400); //设置缓存和有效时间
            }
        }

        if(empty($sku_list)){
            return false;
        }

        $id_error_list = [];
        $id_success_list = [];
        $name_error_list = [];
        $name_success_list = [];
        foreach($sku_list as $sku_value){
            if(empty($sku_value['product_id'])) continue;

            // 1688供应商必须调接口重新查一遍更新数据
            $supplierInfo = $this->aliproductapi->getSupplierByProductId($sku_value['product_id']);
            if($supplierInfo['code'] === false){
                $sku_value['ali_supplier_name'] = '';
                $sku_value['supplier_login_id'] = '';
            }else{
                $sku_value['supplier_login_id'] = isset($supplierInfo['data']['loginId'])?$supplierInfo['data']['loginId']:'';
                $sku_value['ali_supplier_name'] = isset($supplierInfo['data']['supplierName'])?$supplierInfo['data']['supplierName']:'';
            }

            // 信息为空的 设置为未验证（跳过更新）
            if(empty($sku_value['supplier_name'])) continue;
            if(empty($sku_value['shop_id'])) continue;
            if(empty($sku_value['ali_supplier_name'])) continue;
            if(empty($sku_value['supplier_login_id'])) continue;

            if(trim($sku_value['ali_supplier_name']) == trim($sku_value['supplier_name'])){
                $name_success_list[] = $sku_value['sku'];
            }else{
                $name_error_list[] = $sku_value['sku'];
            }

            if(trim($sku_value['supplier_login_id']) == trim($sku_value['shop_id'])){
                $id_success_list[] = $sku_value['sku'];
            }else{
                $id_error_list[] = $sku_value['sku'];
            }
        }

        // 1688供应商名称是否一致（0.未验证,1.一致,2.不一致）
        if($name_success_list){
            $this->purchase_db->where_in('sku',$name_success_list)->update('product',['is_equal_sup_name' => 1]);
        }
        if($name_error_list){
            $this->purchase_db->where_in('sku',$name_error_list)->update('product',['is_equal_sup_name' => 2]);
        }
        // 1688供应商ID是否一致（0.未验证,1.一致,2.不一致）
        if($id_success_list){
            $this->purchase_db->where_in('sku',$id_success_list)->update('product',['is_equal_sup_id' => 1]);
        }
        if($id_error_list){
            $this->purchase_db->where_in('sku',$id_error_list)->update('product',['is_equal_sup_id' => 2]);
        }

        return true;
    }


}