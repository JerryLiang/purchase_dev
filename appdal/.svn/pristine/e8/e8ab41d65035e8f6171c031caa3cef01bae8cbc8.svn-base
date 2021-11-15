<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AliBaseApi.php';

/**
 * Class AliProductApi
 * 阿里巴巴 商品相关信息
 * @author:Jolon
 * @date 2019-03-18
 */
class AliProductApi extends AliBaseApi {

    protected $_webSite = '1688';


    /**
     * 从商品链接中提取 商品ID
     * @param $productId
     * @return mixed
     */
    public function parseProductIdByLink($productId){
        if(empty($productId)){
            $this->_errorMsg = '采购链接缺失';
            return $this->returnData();
        }
        if(strpos($productId,'http') !== false){
            preg_match("/offer\/[\w]+\.html/",$productId,$out);
            if($out){
                $productId = $out[0];
                $productId = str_replace(array('offer/','.html'),'',$productId);
                return $this->returnData($productId);
            }else{
                preg_match("/offerdetail\/[\w]+\.html/",$productId,$out_2);
                if($out_2){
                    $productId = $out_2[0];
                    $productId = str_replace(array('offerdetail/','.html'),'',$productId);
                    return $this->returnData($productId);
                }else{
                    $this->_errorMsg = '采购链接不是1688商品链接，链接错误，请修改链接';
                    return $this->returnData();
                }
            }
        }else{
            $this->_errorMsg = '采购链接不是网址';
            return $this->returnData();
        }
    }

    /**
     * 将商品加入铺货列表
     * @param string $productId
     * @return mixed|string
     */
    public function commodityDistribution($productId){
        if(empty($productId)){
            $this->_errorMsg = 'Product Id不能为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams();
        $post_data['productId'] = [$productId];

        $quota_url = getConfigItemByName('api_config','alibaba','crossBorder-commodityDistribution');
        $result    = $this->curlPost($quota_url,$post_data);

        // 解析 执行结果
        if(empty($result) or !is_json($result)){
            $this->_errorMsg = "商品[$productId]铺货失败[结果非JSON数据]";
            return $this->returnData();
        }
        $res_str = $result;
        $result = json_decode($result,true);
        if(($java_result = $this->analysisResult($result)) !== false){
            $this->_errorMsg = "商品[$productId]加入铺货列表失败[$res_str]";
            return $this->returnData();
        }
        if(isset($result['data']['result']['success']) and empty($result['data']['result']['success'])){
            $_aliMsg = isset($result['data']['result']['errorMsg'])?$result['data']['result']['errorMsg']:'';
            $_errorMsg = "商品[$productId]加入铺货列表失败[1688错误{$_aliMsg}]";
            $this->_errorMsg = $_errorMsg;
            $data = $this->returnData();
        }else{
            $this->_errorMsg = null;
            $data = $this->returnData();
        }

        return $data;
    }


    /**
     * 获取商品详情信息
     * @link https://open.1688.com/api/apidocdetail.htm?id=com.alibaba.product:alibaba.cross.productInfo-1
     * @param string $productId     商品ID
     * @param bool $autoDistribute  是否自动加入铺货列表（必须加入铺货列表才能获取到商品信息）
     * @return mixed|string
     */
    public function getProductInfo($productId,$autoDistribute = false){
        if(empty($productId)){
            $this->_errorMsg = 'Product Id不能为空';
            return $this->returnData();
        }

        if($autoDistribute === true){// 是否自动加入铺货列表（必须加入铺货列表才能获取到商品信息）
            // 加入铺货列表
            $result = $this->commodityDistribution($productId);
            if(empty($result['code'])){
                $this->_errorMsg = $result['errorMsg'];
                return $this->returnData();
            }
        }

        $post_data = $this->getFixedParams();
        $post_data['productId'] = $productId;
        $post_data['webSite'] = $this->_webSite;

        $quota_url = getConfigItemByName('api_config','alibaba','crossBorder-getProductInfo');
        $result    = $this->curlPost($quota_url,$post_data);
        $result    = json_decode($result, true);
        return $result;
    }

    /**
     * 验证 1688商品是否下架（根据 获取商品详情成功与否来验证）
     * @param $productIds
     * @return mixed|string
     */
    public function checkProductWhetherOnline($productIds){
        if(!is_array($productIds)){
            $productIds = [$productIds];
        }
        $productIds = array_unique($productIds);
        if(empty($productIds)){
            $this->_errorMsg = '商品ID不存在';
            return $this->returnData();
        }

        $data_online_product = [];
        $data_check_results  = [];
        foreach($productIds as $productId){
            $return = $this->getProductInfo($productId);

            if(isset($return['code']) and $return['code'] === false){
                $data_check_results[$productId] = $return['errorMsg'];
            }

            if(isset($return['code']) and $return['code'] == '200'){
                $product_data = isset($return['data'])?$return['data']:[];

                if(isset($product_data['success']) and $product_data['success']){
                    $data_online_product[$productId] = $productId;
                    $data_check_results[$productId] = '商品在线';
                }else{
                    $data_check_results[$productId] = '商品已经下架';
                }
            }
        }

        $data = [
            'online_product' => $data_online_product,
            'check_results'  => $data_check_results
        ];

        return $this->returnData($data);
    }


    /**
     * 获取商品对应的供应商公司名称
     * @param string $productId
     * @return mixed|string
     */
    public function getSupplierByProductId($productId){
        if(empty($productId)){
            $this->_errorMsg = 'Product Id不能为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams();
        $post_data['productId'] = $productId;
        $post_data['webSite'] = $this->_webSite;

        $quota_url = getConfigItemByName('api_config','alibaba','product-getSupplierByProductId');
        $result    = $this->curlPost($quota_url,$post_data);

        // 解析 执行结果
        if(empty($result) or !is_json($result)){
            $this->_errorMsg = "商品[$productId]查询供应商失败[结果非JSON数据]";
            return $this->returnData();
        }
        $result = json_decode($result,true);
        if(($java_result = $this->analysisResult($result)) !== false){
            $this->_errorMsg = "商品[$productId]查询供应商失败[$java_result]";
            return $this->returnData();
        }
        if(isset($result['data']) and !empty($result['data'])){
            $result_tmp = isset($result['data']['result'])?$result['data']['result']:[];
            if(empty($result_tmp) and is_string($result['data'])){
                $result_tmp = [
                    'supplierName' => $result['data'],
                ];
            }

            $result = $result_tmp;

            $supplierInfo = [
                'loginId'           => isset($result['loginId']) ? $result['loginId'] : '',
                'companyName'       => isset($result['companyName']) ? $result['companyName'] : '',
                'supplierName'      => isset($result['supplierName']) ? $result['supplierName'] : '',
                'memberId'          => isset($result['memberId']) ? $result['memberId'] : '',
                'shopUrl'           => isset($result['shopUrl']) ? $result['shopUrl'] : '',
                'categoryId'        => isset($result['categoryId']) ? $result['categoryId'] : '',
                'categoryName'      => isset($result['categoryName']) ? $result['categoryName'] : '',
                'email'             => isset($result['email']) ? $result['email'] : '',
                'sellerName'        => isset($result['sellerName']) ? $result['sellerName'] : '',
                'saleRate'          => isset($result['saleRate']) ? $result['saleRate'] : '',
                'maturity'          => isset($result['maturity']) ? $result['maturity'] : '',
                'memo'              => isset($result['memo']) ? $result['memo'] : '',
                'modifyDate'        => isset($result['modifyDate']) ? $result['modifyDate'] : '',
                'trustScore'        => isset($result['trustScore']) ? $result['trustScore'] : '',
                'userId'            => isset($result['userId']) ? $result['userId'] : '',
                'enterpriseAccount' => isset($result['enterpriseAccount']) ? $result['enterpriseAccount'] : '',
                'createDate'        => isset($result['createDate']) ? $result['createDate'] : '',
                'communityLevel'    => isset($result['communityLevel']) ? $result['communityLevel'] : '',
                'joinFrom'          => isset($result['joinFrom']) ? $result['joinFrom'] : '',
                'rateNum'           => isset($result['rateNum']) ? $result['rateNum'] : '',
                'gmtPaidJoin'       => isset($result['gmtPaidJoin']) ? $result['gmtPaidJoin'] : '',
                'buyRate'           => isset($result['buyRate']) ? $result['buyRate'] : '',
                'personAccount'     => isset($result['personAccount']) ? $result['personAccount'] : '',
                'homepageUrl'       => isset($result['homepageUrl']) ? $result['homepageUrl'] : '',
                'saleKeywords'      => isset($result['saleKeywords']) ? $result['saleKeywords'] : '',
                'tpYear'            => isset($result['tpYear']) ? $result['tpYear'] : '',
                'buyKeywords'       => isset($result['buyKeywords']) ? $result['buyKeywords'] : '',
                'memberBizType'     => isset($result['memberBizType']) ? $result['memberBizType'] : '',
                'rateSum'           => isset($result['rateSum']) ? $result['rateSum'] : '',
                'domainInPlatforms' => isset($result['domainInPlatforms']) ? $result['domainInPlatforms'] : '',
                'icon'              => isset($result['icon']) ? $result['icon'] : '',
                'phoneNo'           => isset($result['phoneNo']) ? $result['phoneNo'] : '',
                'industry'          => isset($result['industry']) ? $result['industry'] : '',
                'product'           => isset($result['product']) ? $result['product'] : '',
                'department'        => isset($result['department']) ? $result['department'] : '',
                'mobileNo'          => isset($result['mobileNo']) ? $result['mobileNo'] : '',
                'addressLocation'   => isset($result['addressLocation']) ? $result['addressLocation'] : '',

            ];
            return $this->returnData($supplierInfo);
        }else{
            $this->_errorMsg = "商品[$productId]查询供应商失败[1688返回的数据为空]";
            return $this->returnData();
        }
    }



    /**
     * 产品——跨境产品开发工具同款开发
     * @param $productLink
     * @return array
     */
    public function getPdtTongKuan($productLink){
        $result = $this->parseProductIdByLink($productLink);
        if(empty($result['code'])){
            $this->_errorMsg = $result['errorMsg'];
            return $this->returnData();
        }
        $productId = $result['data'];
        $post_data = $this->getFixedParams();
        $post_data['productUrl'] = $productLink;

        $quota_url = getConfigItemByName('api_config','alibaba','product_pdt_product_gen');
        $result    = $this->curlPost($quota_url,$post_data);

        // 解析 执行结果
        if(empty($result) or !is_json($result)){
            $this->_errorMsg = "商品[$productId]同款开发失败";
            return $this->returnData();
        }
        $result = json_decode($result,true);
        if(isset($result['data']) and !empty($result['data'])){
            return $this->returnData($result['data']);
        }else{
            $this->_errorMsg = "商品[$productId]同款开发失败[1688返回的数据为空]";
            return $this->returnData();
        }
    }


}