<?php
/**
 * Created by PhpStorm.
 * User: Totoro
 * Date: 2020-05-29
 * Time: 9:55
 */
require_once APPPATH . "libraries/Rsa.php";
require_once APPPATH . "libraries/Aes.php";

class Lakala{
    private  $aesKey ='';
    private  $ivValue = '';
    private  $fileBatchNo = '';
    private  $custNo  = '';//商户号
    private  $privateKey = '';
    private  $publicKey = '';
    private  $submitDate = '';
    private  $api_host = '';
    private  $url = '';
    private  $rsa = '';

    private  $headers = ['Content-Type:application/json;charset=utf-8'];

    public $lakala_state = [// 批次状态码
        "0" => "初始化",
        "1" => "批次处理完成",
        "2" => "批次处理失败-其他原因",
        "3" => "批次处理中",
        "4" => "批次处理失败-解析送盘文件失败",
        "5" => "批次处理失败-风控拦截",
        "6" => "批次处理失败-扣款失败",
        "7" => "拉卡拉文件受理成功"
    ];

    public function __construct(){
        $ci = &get_instance();
        $ci->load->config("lakala_config");
        if (!empty($ci->config->item('lakala'))) {
            $lakala_config = (object)$ci->config->item('lakala');
        }
        $this->custNo =$lakala_config->cust_no;
        $this->privateKey = $lakala_config->private_key;
        $this->fileBatchNo = date('YmdHis');
        $this->publicKey = $lakala_config->public_key;
        $this->submitDate = date('YmdHis');
        $this->api_host = $lakala_config->api_host;
        $this->rsa = new Rsa($this->privateKey,$this->publicKey);
    }

    /**
     * 生成 商户订单号
     * @return string
     */
    public function get_trans_no(){
        $cust = 'YBLKL';
        return $cust.date("YmdHis").rand(100, 999);
    }

    /**
     * 文件批次号
     * @return int
     */
    public function get_file_batch_no(){
        return rand('100000', '999999');
    }

    /**
     * 数据转换成跨境收汇明细文件所需要的TXT内容
     * @param array $arr
     * @return string
     */
    public function toTxt($arr = array(),$s_data){
        $content = '';
        foreach ($arr as $k => $v) {
            if($k==0){
                $content.="代收付标志|@|代付批次号|@|交易总笔数|@|交易总金额|@|结汇批次号|@|". "\n";
                $content .= implode('|@|', $s_data)."|@|" . "\n";
                $content.="商户订单号|@|账户类型|@|账号|@|户名|@|手机号|@|金额|@|证件号|@|银行代码|@|联行行号|@|摘要|@|出款方账户名|@|出款方账号|@|商户保留域|@|代付用途|@|". "\n";
            }
            $Data = $v;
            $content .= implode('|@|', $Data) ."|@|". "\n";
        }
        return $content;
    }

    /**
     * 数据转换成跨境收汇明细文件所需要的TXT内容
     * @param array $arr
     * @return string
     */
    public function FileToTxt($arr = array()){
        $content = '';
        foreach ($arr as $k => $v) {
            $Data = $v;
            $content .= implode('|@|', $Data) . "\n";
        }
        return $content;
    }

    /**
     * 加密文件 说明:当交易注册时，必填。字节数组，该文件需先bz2加压缩，AES加密
     * @param $orderDetailInfo
     * @param $order_no
     * @return bool
     */
    public function  uploadFile($orderDetailInfo,$s_data){
        //文本内容
        $fileContent =  $this->toTxt($orderDetailInfo,$s_data);
        //计入请求的日志
        //aes加密
        $aes = new Aes();
        $ciphertext = $aes->shopenEncrypt($fileContent);
        //文件加密key
        $this->aesKey = $aes->encryptKey;
        //偏移量ivValue
        $this->ivValue = $aes->localIV;
        //将aes加密后的内容转为字节数组
        return $aes->getBytes($ciphertext);
    }

    /**
     * 下载失败
     * @param $orderDetailInfo
     * @return array
     */
    public function FileToTxtStr($orderDetailInfo){
        $fileContent =  $this->FileToTxt($orderDetailInfo);
        //aes加密
        $aes = new Aes();
        $ciphertext = $aes->shopenEncrypt($fileContent);
        //文件加密key
        $this->aesKey = $aes->encryptKey;
        //偏移量ivValue
        $this->ivValue = $aes->localIV;
        //将aes加密后的内容转为字节数组
        return $aes->getBytes($ciphertext);
    }

    /**
     * 注册请求方法
     * @param $data
     * @param $uploadFile
     * @return mixed
     */
    public function registry($data, $uploadFile){
        $this->urlRequest('/gate/batchPay/registry');
        $this->uploadFile = $uploadFile;
        return $this->requestMessage($data);
    }

    /**
     * 批量代付文件批次查询接口
     * @param $data
     * @return bool|mixed|string
     */
    public function queryStatus($data){
        $this->urlRequest('/gate/batchPay/queryBatchFileStatus');
        $this->uploadFile = '';
        return $this->requestMessage($data);
    }

    /**
     * 批量代付解析回盘文件下载接口
     */
    public function  downloadErrorBackFileUrl($data,$uploadFile){
        $this->urlRequest('/gate/batchPay/downloadErrorBackFile');
        $this->uploadFile = $uploadFile;
        return $this->requestMessage($data);
    }

    /**
     * 批量代付电子回单文件下载接口
     * @param $data
     * @return bool|mixed|string
     */
    public function queryDownloadReceipt($fileBatchNo,$custOrderNo,$submitDate){
        $this->urlRequest('/gate/batchPay/downloadReceipt');
        $data = array(
            'fileBatchNo'=> $fileBatchNo,
            'custOrderNo'=> $custOrderNo,
            'submitDate'=> $submitDate
        );
        $this->uploadFile = '';
        $result = $this->requestMessage($data);


        $aes = new Aes();
        if(isset($result['code']) && $result['code'] === '0000') {
            $encData = json_decode($this->rsa->privateDecrypt($result['encData']),true);//响应返回数据
            $aes->encryptKey = $encData['aesKey'];
            $aes->localIV = $encData['ivValue'];
            $result['downloadFile'] = $aes->shopenDecrypt($result['downloadFile']);//响应返回数据
        }

        return $result;
    }

    /**
     * 提交平台url
     * @param $mehtod
     */
    private function urlRequest($urlName){
        $this->url = $this->api_host.$urlName;
    }

    /**
     * 返回含有毫秒的当前时间（毫秒数四舍五入）
     */
    public function getMilliSecond(){
        // $usec 微秒部分（*1000取整即为毫秒） $sec 秒数部分
        list($usec, $sec) = explode(" ", microtime());
        return array(
            'msec' => date('YmdHis') . sprintf('%03d', round($usec * 1000)),// 求得3位长度的毫秒值（不足3位前面补0）
            'sec' => $sec
        );
    }

    /**
     * 组装请求的数据
     * @param $data
     * @return bool|mixed|string
     */
    private function requestMessage($data){
        $millSec = $this->getMilliSecond();
        $millSec = $millSec['msec'];// 毫秒
        $random = str_pad(mt_rand(0, 99999), 5, "0", STR_PAD_BOTH);
        $custNo = $this->custNo;
        $timeStamp = date('YmdHis').$random;
        $encData = $this->rsa->publicEncrypt(json_encode($data));
        $params  = [
            'sid' => $millSec.$random,
            'custNo' => $custNo,
            'timeStamp' => $timeStamp,
            'uploadFile' => $this->uploadFile,
            'encData' => $encData,
            'sign' => $this->rsa->sign($encData),
            'resv' => 'CBC', //定义加密方式为CBC
        ];
        $params = json_encode($params);
        $result = $this->curl_post($this->url, $params);
        return $result;
    }

    /**
     * curl-post提交
     * @param $url
     * @param $data
     * @return bool|mixed|string
     */
    public function curl_post($url , $data=array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $headers = $this->headers;
        if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output,true);
        return $output;
    }

    /**
     * 批量代付注册接口
     * @param $data
     * @return bool|mixed
     */
    public function batchPay_registry($data){
        if(!empty($data)){
            $detailInfo  = [];
            $totalAmount = 0;

            if(count($data) > 1){
                return false;// 每次只能提交一个支付单
            }
            foreach($data as $key=>$val){// 多个 拉卡拉请款单 合并生成一个 拉卡拉代付订单，不同供应商的可以合并
                $detailInfo[] = [
                    'custOrderNo'  => $val['cust_order_no'],//商户订单号
                    'accType'      => 1,// 账户类型 "1":"对私借记账户" "3":"对公账户 默认对私人
                    'accNo'        => $val['acc_no'], // 账户号
                    'accName'      => $val['acc_name'],//账户名
                    'phoneNo'      => empty($val['phone_no']) ? '' : $val['phone_no'],//  手机号
                    'amount'       => $val['amount'],//金额
                    'certNo'       => $val['cert_no'],// 证件号
                    'bankCode'     => '', // 对公账户必填，银行代码见附录 5.6《银行代码》； 若不填，注意后补“|@|”间隔符
                    'cnapsCode'    => '', //对公账户必填； 若不填，注意后补“|@|”间隔符；
                    'summary'      => '',
                    'payerAccName' => "NA",
                    'payerAccNo'   => "NA",
                    'remark'       => "结汇下发".$val['acc_name'],
                    'paySource'    => "A"
                ];
                $totalAmount  += $val['amount'];

                $fileBatchNo = $val['file_batch_no'];
            }

            if(empty($fileBatchNo)){
                return false;
            }

            //$fileBatchNo = rand('100000', '999999');# toDo 文件批次号，每次会生成新的（不能生成新的，否则可能导致重复支付）

            $heade       = [
                'payMark'     => 'F',
                'fileBatchNo' => $fileBatchNo,
                'totalCount'  => count($detailInfo),
                'totalAmount' => $totalAmount,
                'fxBatchNo'   => '202007311435'
            ];
            $uploadFile  = $this->uploadFile($detailInfo, $heade);
            $data        = array(
                'aesKey'  => $this->aesKey,
                'ivValue' => $this->ivValue
            );
            $result      = $this->registry($data, $uploadFile);
            if(isset($result['code']) && $result['code'] === '0000'){
                $result['encData'] = json_decode($this->rsa->privateDecrypt($result['encData']), true);//响应返回数据
            }
            apiRequestLogInsert(
                [
                    'record_number' => 'LAKALA_REGISTRY',
                    'record_type' => '/gate/batchPay/registry',
                    'post_content' => ['head' => $heade,'detailInfo' => $detailInfo],
                    'response_content' => $result
                ],
                'api_request_log'
            );
            return $result;
        }else{
            return false;
        }
    }

    /**
     * 批量代付文件批次查询接口
     */
    public function queryBatchFileStatus($fileBatchNo,$submitDate){
        $data = array(
            'fileBatchNo'=> $fileBatchNo,
            'submitDate'=> $submitDate
        );
        $result = $this->queryStatus($data);
        if(isset($result['code']) && $result['code'] === '0000') {
            $result['encData'] = json_decode($this->rsa->privateDecrypt($result['encData']),true);//响应返回数据
        }
        return $result;
    }

    /**
     * 批量代付解析回盘文件下载接口
     * @param string $fileBatchNo 批次
     * @param string $submitDate 提交时间
     */
    public function downloadErrorBackFile($fileBatchNo,$submitDate){
        $upload[] = [
            'fileBatchId' => $this->fileBatchNo,
            'processDate' => $submitDate,
            'fileBatchNo' => $fileBatchNo,
            'batchFileName' => $this->custNo.'-'.$fileBatchNo.'-'.$submitDate.'.txt',
        ];
        $data =[
            'fileBatchNo' => $fileBatchNo,
            'submitDate' => $submitDate,
        ];

        $uploadFile = $this->FileToTxtStr($upload);
        $result = $this->downloadErrorBackFileUrl($data, $uploadFile);
        $aes = new Aes();
        if(isset($result['code']) && $result['code'] === '0000') {
            $encData = json_decode($this->rsa->privateDecrypt($result['encData']),true);//响应返回数据
            $aes->encryptKey = $encData['aesKey'];
            $aes->localIV = $encData['ivValue'];
            $result['downloadFile'] = $aes->shopenDecrypt($result['downloadFile']);//响应返回数据
        }
        return $result;
    }

}