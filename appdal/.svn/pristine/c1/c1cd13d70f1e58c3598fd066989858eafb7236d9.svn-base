<?php
/**
 * Created by PhpStorm.
 * User: 昆极
 * Date: 2019/5/12
 * Time: 14:15
 */

class ESignFlows
{

    // JAVA接口地址
    // http://192.168.71.156/web/#/118?page_id=23871
    // http://192.168.71.156/web/#/118?page_id=23874
    // http://192.168.71.156/web/#/118?page_id=23876
    // http://192.168.71.156/web/#/118?page_id=23913
    // e签宝对接
    const STEP_START = JAVA_API_URL.'/provider/yibaiSupplierStatement/receivePurchaseoneStepStart';// 发起盖章--采购发起
    const RUSH_SIGN =  JAVA_API_URL.'/provider/yibaiSupplierStatement/receivePurchaseoneRushSign';// 流程签署人催签--采购
    const REVOKE_FLOW = JAVA_API_URL.'/provider/yibaiSupplierStatement/receivePurchaseoneRevokeFlow';// 签署流程撤销--采购
    const DOWN_FLOW_DOC = JAVA_API_URL .'/provider/yibaiSupplierStatement/downloadFlowDocPurchase';// 获取流程文档下载路径 -- 采购

    private $_java_access_taken = null;
    protected $_errorMsg = null;

    // 签署流程任务状态
    public $flowStatus = [
        '0' => '草稿',
        '1' => '签署中',
        '2' => '已完成',// 所有签署人完成签署
        '3' => '已撤销',// 发起方撤销签署任务
        '4' => '终止',// 签署流程设置了文件有效截止日期，到期后触发
        '5' => '已过期',// 签署截止日到期后触发
        '7' => '已拒签',
    ];

    public function __construct(){

        $this->_java_access_taken = getOASystemAccessToken();
    }

    /**
     * 转换 采购主体为 全名称
     * @param $purchaseName
     * @return array
     */
    public function convertPurchaseNameCn($purchaseName){
        // 转化为公司名称
        $company_info = compactCompanyInfo($purchaseName);
        if(empty($company_info) or !isset($company_info['name'])){
            $this->_errorMsg = '获取【付款申请书】采购主体【'.$purchaseName.'】失败';
            return $this->returnData();
        }else{
            return $this->returnData($company_info['name']);
        }
    }

    /**
     * 组装返回的数据
     * @param null $data
     * @return array
     */
    public function returnData($data = null){
        if(is_null($data) and $this->_errorMsg){
            return [
                'code'     => false,
                'errorMsg' => $this->_errorMsg
            ];
        }else{
            return [
                'code'     => true,
                'errorMsg' => $this->_errorMsg,
                'data'     => $data
            ];
        }
    }

    /**
     * 获取 固定的头部信息
     * @return array
     */
    public function getHeaders(){
        $header = ['Content-Type: application/json'];
        return $header;
    }

    /**
     * POST 方式发送 CURL 请求
     * @param string $url 请求链接
     * @param array $post_data  发送的数据
     * @return mixed|string
     */
    public function curlPost($url,$post_data = array()){
        $header = $this->getHeaders();


        if(stripos($url,'access_token') === false ) $url .= "?access_token=".$this->_java_access_taken;
        $result = getCurlData($url,$post_data,'post',$header);

        // 记录日志
        $logPathFile = get_export_path('logs/esign').'log'.date('Ymd').'.txt';
        file_put_contents($logPathFile,PHP_EOL.PHP_EOL.PHP_EOL,FILE_APPEND);
        file_put_contents($logPathFile,$url.PHP_EOL.PHP_EOL,FILE_APPEND);
        file_put_contents($logPathFile,$post_data.PHP_EOL.PHP_EOL,FILE_APPEND);
        file_put_contents($logPathFile,$result.PHP_EOL.PHP_EOL,FILE_APPEND);

        return $result;
    }

    /**
     * 一步发起签署
     * @param string $statementNumber 单据号（PFB-DZ000021）
     * @param string $supplierCode 供应商code（A976306395）
     * @param string $instockMonth 月份2021年6月",
     * @param string $filePath 签署合同地址（URL）
     * @param string $purchaseName 采购主体（SZYB/HKYB）
     * @param string $subsidiaryFile 附属文件（URL）
     * @return array
     */
    public function createFlowOneStep($statementNumber,$supplierCode,$instockMonth,$filePath,$purchaseName,$subsidiaryFile = ''){
        $nameData = $this->convertPurchaseNameCn($purchaseName);
        if($nameData['code'] ==  false){
            $this->_errorMsg = $nameData['errorMsg'];
            return $this->returnData();
        }else{
            $purchaseName = $nameData['data'];
        }

        $instockMonth = date('Y年m月',strtotime($instockMonth));
        $flowData = [
            'statementNumber' => $statementNumber,
            'supplierCode' => $supplierCode,
            'instockMonth' => $instockMonth,
            'filePath' => $filePath,
            'purchaseName' => $purchaseName
        ];
        if(!empty($subsidiaryFile)){
            $flowData['subsidiaryFile'] = $subsidiaryFile;
        }


        $return_content = $this->curlPost(self::STEP_START, json_encode($flowData));

        $result = json_decode($return_content, true);
        if($result['code'] == 200){
            return $this->returnData($result['data']);
        }else{
            $this->_errorMsg = $result['code'];
            if(isset($result['error'])) $this->_errorMsg .= '-'.$result['error'];
            if(isset($result['message'])) $this->_errorMsg .= '-'.$result['message'];
            if(isset($result['msg'])) $this->_errorMsg .= '-'.$result['msg'];

            return $this->returnData();
        }
    }


    /**
     * 催办
     * @param string $flowId 签署流程ID
     * @param string $purchaseName 采购主体（SZYB/HKYB）
     * @return array
     */
    public function signFlowsRushSign($flowId,$purchaseName){
        $nameData = $this->convertPurchaseNameCn($purchaseName);
        if($nameData['code'] ==  false){
            $this->_errorMsg = $nameData['errorMsg'];
            return $this->returnData();
        }else{
            $purchaseName = $nameData['data'];
        }

        $flowData = ['flowId' => $flowId,'purchaseName' => $purchaseName];

        $return_content = $this->curlPost(self::RUSH_SIGN, json_encode($flowData));

        $result = json_decode($return_content, true);
        if($result['code'] == 200){
            return $this->returnData($result['data']);
        }else{
            $this->_errorMsg = $result['code'];
            if(isset($result['error'])) $this->_errorMsg .= '-'.$result['error'];
            if(isset($result['message'])) $this->_errorMsg .= '-'.$result['message'];
            if(isset($result['msg'])) $this->_errorMsg .= '-'.$result['msg'];

            return $this->returnData();
        }
    }

    /**
     * 撤销
     * @param string $flowId 签署流程ID
     * @param string $purchaseName 采购主体（SZYB/HKYB）
     * @return array
     */
    public function signFlowsRevoke($flowId,$purchaseName){
        $nameData = $this->convertPurchaseNameCn($purchaseName);
        if($nameData['code'] ==  false){
            $this->_errorMsg = $nameData['errorMsg'];
            return $this->returnData();
        }else{
            $purchaseName = $nameData['data'];
        }

        $flowData = ['flowId' => $flowId,'purchaseName' => $purchaseName];

        $return_content = $this->curlPost(self::REVOKE_FLOW, json_encode($flowData));

        $result = json_decode($return_content, true);
        if($result['code'] == 200){
            return $this->returnData($result['data']);
        }else{
            $this->_errorMsg = $result['code'];
            if(isset($result['error'])) $this->_errorMsg .= '-'.$result['error'];
            if(isset($result['message'])) $this->_errorMsg .= '-'.$result['message'];
            if(isset($result['msg'])) $this->_errorMsg .= '-'.$result['msg'];

            return $this->returnData();
        }
    }

    /**
     * （签署完成）流程文档下载
     * @param string $flowId 签署流程ID
     * @param string $purchaseName 采购主体（SZYB/HKYB）
     * @return array
     */
    public function getSignFlowsDocument($flowId,$purchaseName){
        $nameData = $this->convertPurchaseNameCn($purchaseName);
        if($nameData['code'] ==  false){
            $this->_errorMsg = $nameData['errorMsg'];
            return $this->returnData();
        }else{
            $purchaseName = $nameData['data'];
        }

        $flowData = ['flowId' => $flowId,'purchaseName' => $purchaseName];

        $return_content = $this->curlPost(self::DOWN_FLOW_DOC, json_encode($flowData));

        $result = json_decode($return_content, true);
        if($result['code'] == 200){
            return $this->returnData($result['data']);
        }else{
            $this->_errorMsg = $result['code'];
            if(isset($result['error'])) $this->_errorMsg .= '-'.$result['error'];
            if(isset($result['message'])) $this->_errorMsg .= '-'.$result['message'];
            if(isset($result['msg'])) $this->_errorMsg .= '-'.$result['msg'];

            return $this->returnData();
        }
    }

}