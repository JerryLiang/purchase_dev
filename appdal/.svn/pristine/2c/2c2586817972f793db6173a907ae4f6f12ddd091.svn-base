<?php

/**
 * Created by PhpStorm.
 * 光大银行 API 对接类库
 *
 * 类库说明：
 *
 *
 * 光大银行接口返回值说明：
 *  B2E000010=通讯异常,请查证 >>> 可能原因是 BatchID 和 ClientBatchID 重复了
 *
 * @author : Jolon
 * @Date   : 2020-8-01 9:55
 */

class CebBank {
    // 代理服务主机
    private $agencyHost   = '';
    
    // 用户信息
    private $usrID        = '';
    private $userPassword = '';
    private $Sigdata      = '';
    private $OPERUserID   = '';
    private $OPERActNo    = '';

    // 报文头
    private $TransCode = '';// 交易码
    private $BatchID   = '';// 交易流水号,要求长度<=50，具体格式建议：企业10位客户号+八位日期+八位顺序号
    private $JnlDate   = '';// 请求日期 YYYYMMDD
    private $JnlTime   = '';// 请求时间 HHMISS

    // 报文体
    private $ClientPatchID = '';// 客户端批内序号/客户交易号 要求长度<=50，具体格式建议：批次号+四位顺序号,要求全局唯一


    public $_errorMsg = null;
    private $_pingHost = null;

    /**
     * CebBank constructor.
     * 加载配置文件、初始化对象
     * @throws Exception
     */
    public function __construct(){
        $cebBank = getConfigItemByName('cebbank', 'cebbank');

        if(empty($cebBank) or !is_array($cebBank)){
            throw new Exception('光大银行配置文件缺失');
        }

        foreach($cebBank as $key => $value){// 初始化配置值
            $this->$key = $value;
        }

        $this->JnlDate = date('Ymd');
        $this->JnlTime = date('His');
    }

    /**
     * 测试 银企通代理软件是否开启
     *      通过发送一个下载文件（错误文件）的请求，检验接口是否返回请求成功的报文
     * @return bool|string
     */
    public function pingHost(){
        if(!is_null($this->_pingHost)) return $this->_pingHost;// 批量操作时只需验证一次

        try{
            $queryCallB2e005023ResXml = $this->downloadFile_b2e005023('2450708155_20010101010101.pdf');
            $queryCallB2e005023Resp   = $this->curlPostXml($queryCallB2e005023ResXml['data']['url'],$queryCallB2e005023ResXml['data']['RequestXmlStr']);

            if(!empty($queryCallB2e005023Resp) and current($queryCallB2e005023Resp)){
                $this->_pingHost = true;
                return true;
            }else{
                $this->_pingHost = false;
                return false;
            }
        }catch (Exception $e){
            $this->_errorMsg = $e->getMessage();
            $this->_pingHost = false;
            return false;
        }
    }

    /**
     * 生成 交易流水号
     *      字符串，要求长度<=50，具体格式建议：企业10位客户号+八位日期+八位顺序号 如：20038556452006121800000001
     * @return string
     */
    public function createBatchId(){
        $prefix        = $this->usrID.date('YmdHms');
        $serial_number = rand(10000, 99999);
        return $prefix.$serial_number;
    }

    /**
     * 生成 客户端批内序号/客户交易号
     *      字符串，要求长度<=50，具体格式建议：批次号+四位顺序号，要求全局唯一
     * @return string
     */
    public function createClientPatchID(){
        $prefix        = $this->usrID.date('YmdHms');
        $serial_number = rand(10000, 99999);
        return $prefix.$serial_number;
    }

    /**
     * 获取 交易码
     * @return  string
     */
    public function getTransCode(){
        return $this->TransCode;
    }

    /**
     * 设置 交易码
     * @param $TransCode
     */
    public function setTransCode($TransCode){
        $this->TransCode = $TransCode;
    }

    /**
     * 获取 交易流水号
     * @return  string
     */
    public function getBatchID(){
        return $this->BatchID;
    }

    /**
     * 设置 交易流水号
     * @param $BatchID
     */
    public function setBatchID($BatchID){
        $this->BatchID = $BatchID;
    }

    /**
     * 获取 客户交易号
     * @return  string
     */
    public function getClientPatchID(){
       return $this->ClientPatchID;
    }

    /**
     * 设置 客户交易号
     * @param $ClientPatchID
     */
    public function setClientPatchID($ClientPatchID){
        $this->ClientPatchID = $ClientPatchID;
    }

    /**
     * 消息头 - 系统消息 - 固定
     * @return array
     */
    protected function getSystemHead(){
        return [
            'Language'    => 'zh_CN',
            'Encodeing'   => '',
            'Version'     => '',
            'ServiceName' => '',
            'CifNo'       => $this->usrID,
            'UserID'      => $this->OPERUserID,
            'SyMacFlag'   => '',
            'MAC'         => '',
            'SyPinFlag'   => '',
            'PinSeed'     => '',
            'LicenseId'   => '',
            'Flag'        => '',
            'Note'        => ''
        ];
    }

    /**
     * 报文头 - 固定
     * @return array
     */
    protected function getTransHead(){
        return [
            'TransCode' => $this->TransCode,
            'BatchID'   => $this->BatchID,
            'JnlDate'   => $this->JnlDate,
            'JnlTime'   => $this->JnlTime,
        ];
    }

    /**
     * 数组转XML
     * @param array $paramsArray
     * @return string
     */
    public function getXmlStr($paramsArray){
        $str = '';
        if(empty($paramsArray)) return $str;
        foreach($paramsArray as $key => $value){
            if(is_array($value)){
                $str .= "<$key>".$this->getXmlStr($value)."</$key>";
            }else{
                $str .= "<$key>".$value."</$key>";
            }
        }

        return $str;
    }

    /**
     * 数组转成 XML 字符串
     * @param array $Transaction 交易数据
     * @return string
     */
    public function getRequestXmlStr($Transaction){
        $headStr       = $this->getXmlStr($Transaction);
        $RequestXmlStr = '<?xml version="1.0" encoding="GBK"?>'.$headStr;
        //$RequestXmlStr = iconv('UTF-8','GB2312',$RequestXmlStr);
        $RequestXmlStr = iconv('UTF-8','GBK',$RequestXmlStr);
        return $RequestXmlStr;
    }

    /**
     * 单笔转账(b2e004001)
     *      1、网银对单笔转帐的一次处理的请求条数有限制，不超过1个，超过1报：交易请求记录条数超过上限!
     *      2、请求报文头中的batchID要求全局唯一，如果发现有重复，报错：交易流水号重复。
     *      3、如果请求报文的clientPatchID与以前发送的一致。
     *      4、该交易查证需要调用单笔转账查证（b2e004003）交易。
     * @param $data
     * @return array
     */
    public function single_order_b2e004001($data){
        if(!isset($this->b2e004001) or empty($this->b2e004001)){
            $this->_errorMsg = '接口 b2e004001 配置缺失';
            return $this->returnData();
        }
        if($this->pingHost() === false){
            return $this->returnData();
        }

        // 验证数据是否符合要求
        if(($res_flag = $this->checkData($data)) !== true){
            $this->_errorMsg = $res_flag;
            return $this->returnData();
        }

        $url = $this->agencyHost.$this->b2e004001.'?usrID='.$this->usrID.'&userPassword='.$this->userPassword.'&Sigdata='.$this->Sigdata;

        $this->setTransCode('b2e004001');
        $this->setBatchID($data['cust_order_no']);
        $this->setClientPatchID($data['order_number_1']);


        $Transaction = [
            'Transaction' => [
                'SystemHead'   => [],
                'TransHead'    => [],
                'TransContent' => []
            ]
        ];

        $Transaction['Transaction']['SystemHead'] = $this->getSystemHead();// 报文头
        $Transaction['Transaction']['TransHead'] = $this->getTransHead();// 报文头

        if($data['perOrEnt'] == '1'){
            $note = '劳务收入';// 必须是指定的类型
            $this->_errorMsg = '接口 b2e004001 暂不对接对私业务';
            return $this->returnData();
        }else{
            $note = '货款';
        }

        // 报文体
        $ReqData = [
            "ClientPatchID"   => $this->ClientPatchID,
            "transferType"    => $data['transferType'],// 2122---行内转账 2120---对外转帐 2124—跨行互联转账
            "accountNo"       => $data['payer_acc_no'],// 帐户号：转出账号
            "toAccountName"   => $data['acc_name'],// 汇入帐户名称:转入账户名
            "toAccountNo"     => $data['acc_no'],// 汇入帐户号：转入账户
            "toBank"          => $data['bank_name_main'],// 汇入银行名称
            "amount"          => $data['amount'],// 余额/发生额（本处为 付款金额，不用考虑手续费）
            "toLocation"      => '',// 为空即可，网银不判断，不送核心
            "clientSignature" => '',// 为空即可，网银不判断，不送核心
            "checkNo"         => '',// 为空即可，网银不判断，不送核心
            "checkPassword"   => '',// 为空即可，网银不判断，不送核心
            "note"            => $note,// 转帐用途
            "noteOther"       => '',// 用途备注
            "bankNo"          => $data['cnaps_code'],// 联行号
            "isUrgent"        => ($data['transferType'] == '2124')?'0':$data['is_urgent'],// 加急标志 0，不加急 1，加急 注：转账类型为2124时，只能上送0
            "cellphone"       => $data['phone_no'],// 手机号
            "perOrEnt"        => $data['perOrEnt'],// 收款账户对公对私标志  0对公 1对私
            "IsAudit"         => '0',// 银行审核标志 1表示需要银行审核；0或者空表示不需要银行审核。
            "matchRule"       => ($data['transferType'] == '2124')?'':'1',// 联行号匹配规则，转账类型为2124时必须为空
        ];

        $Transaction['Transaction']['TransContent']['ReqData'] = $ReqData;

        $RequestXmlStr = $this->getRequestXmlStr($Transaction);

        $data = [
            'url'           => $url,
            'RequestXmlStr' => $RequestXmlStr,
            'ClientPatchID' => $ReqData['ClientPatchID']
        ];

        return $this->returnData($data);
    }

    /**
     * 单笔转账查证(b2e004003)
     *      1、该交易只能用于查证单笔转账（b2e004001）交易。
     *      2、返回报文中NowFlag为00和TransferFlag为00时，才代表所查证的转账交易是转账成功的。（付款成功）
     * @param string $ClientBchID 单笔转账时送的BatchID（要查证的BatchID 对应字段 --> pur_pay_lakala.cust_order_no）
     * @param string $ClientPchID 单笔转账时的ClientPatchID（要查证的PatchID 对应字段 --> pur_pay_lakala.order_number_1）
     * @return array
     */
    public function single_order_b2e004003($ClientBchID,$ClientPchID){
        if(!isset($this->b2e004003) or empty($this->b2e004003)){
            $this->_errorMsg = '接口 b2e004003 配置缺失';
            return $this->returnData();
        }
        if($this->pingHost() === false){
            return $this->returnData();
        }
        $url = $this->agencyHost.$this->b2e004003.'?userPassword='.$this->userPassword;

        $this->setTransCode('b2e004003');
        $this->setBatchID($this->createBatchId());
        $this->setClientPatchID($this->createClientPatchID());

        $Transaction = [
            'Transaction' => [
                'SystemHead'   => [],
                'TransHead'    => [],
                'TransContent' => []
            ]
        ];
        $Transaction['Transaction']['SystemHead'] = $this->getSystemHead();// 报文头
        $Transaction['Transaction']['TransHead'] = $this->getTransHead();// 报文头

        // 报文体
        $ReqData = [
            "ClientPatchID" => $this->ClientPatchID,// 每次请求都要生成一个新的唯一流水号
            "ClientBchID"   => $ClientBchID,// 待查证的单笔交易的交易流水号（要查证的BatchID）
            "ClientPchID"   => $ClientPchID,// 待查证的单笔交易的客户交易号（要查证的PatchID）
        ];

        $Transaction['Transaction']['TransContent']['ReqData'] = $ReqData;

        $RequestXmlStr = $this->getRequestXmlStr($Transaction);

        $data = [
            'url'           => $url,
            'RequestXmlStr' => $RequestXmlStr,
            'ClientPatchID' => $ReqData['ClientPatchID']
        ];

        return $this->returnData($data);
    }

    /**
     * 查询 单笔转账交易的回单编号
     * @param string $BeginDate  开始日期
     * @param string $EndDate    结束日期
     * @return array[]
     * @throws Exception
     */
    public function query_b2e005023($BeginDate,$EndDate){

        $PageNum = 0;// 当前查询页
        $QueryNum = 50;// 每次查询笔数
        $complete_flag = false;

        $success_list = $error_list = [];
        do{
            $PageNum ++;
            $BeginNum = ($PageNum - 1)*$QueryNum;// 起始笔数（偏移量）
            $BeginNum = $BeginNum <=0 ? 1 :$BeginNum;// 起始笔数-最小为1

            $queryCallB2e005023ResXml = $this->call_b2e005023($BeginDate,$EndDate,$BeginNum,$QueryNum,0,'');
            if(empty($queryCallB2e005023ResXml['code'])){
                $error_list[] = $queryCallB2e005023ResXml['errorMsg'];
                break;
            }
            $queryCallB2e005023Resp = $this->curlPostXml($queryCallB2e005023ResXml['data']['url'],$queryCallB2e005023ResXml['data']['RequestXmlStr']);
            if(isset($queryCallB2e005023Resp['TransContent']['ReturnCode']) and $queryCallB2e005023Resp['TransContent']['ReturnCode'] === '0000'){
                $TC_BRL_RespData = $queryCallB2e005023Resp['TransContent']['BatchRespList']['RespData'];

                // 读取数据内容
                if(count($TC_BRL_RespData) > 0){
                    if(isset($TC_BRL_RespData['BillNumber'])){

                        $BillNumber = $TC_BRL_RespData['BillNumber'];// 回单号(通过 order_number_2 字段关联)
                        $FlowNumber = $TC_BRL_RespData['FlowNumber'];// 流水号(通过 file_batch_no 字段关联)
                        $success_list[$FlowNumber] = $BillNumber;// 流水号->回单号
                    }else{
                        foreach($TC_BRL_RespData as $respValue){
                            // 过滤手续费的面单
                            if(isset($respValue['AcName2']) and $respValue['AcName2'] == '用于证书缴费') continue;
                            if(isset($respValue['purpose']) and $respValue['purpose'] == '网银电子汇划费') continue;

                            $BillNumber = $respValue['BillNumber'];// 回单号(通过 order_number_2 字段关联)
                            $FlowNumber = $respValue['FlowNumber'];// 流水号(通过 file_batch_no 字段关联)
                            $success_list[$FlowNumber] = $BillNumber;// 流水号->回单号
                        }
                    }
                }

            }elseif(isset($queryCallB2e005023Resp['ReturnCode'])){
                $error_list[] = isset($queryCallB2e005023Resp['ReturnMsg'])?$queryCallB2e005023Resp['ReturnMsg']:(isset($queryCallB2e005023Resp['error'])?$queryCallB2e005023Resp['error']:'查询出错未知原因');
                break;
            }else{
                $error_list[] = '查询出错未知原因[响应数据解析失败]';
                break;
            }

        }while(!$complete_flag);

        return ['success_list' => $success_list,'error_list' => $error_list];

    }

    /**
     * 电子回单查询
     *      提供 pdf 格式的电子回单文件下载服务
     * @param string $BeginDate  开始日期
     * @param string $EndDate 结束日期
     * @param int $BeginNum 金额开始值
     * @param int $QueryNum  金额结束值
     * @param int $OperationType 操作类型（0.查询文件，1.生成文件）
     * @param string $BillNumber 回单号
     * @return array
     */
    public function call_b2e005023($BeginDate = null,$EndDate = null,$BeginNum = 1,$QueryNum = 50,$OperationType = 0,$BillNumber = ''){
        if (!isset($this->b2e005023) or empty($this->b2e005023)) {
            $this->_errorMsg = '接口 b2e005023 配置缺失';
            return $this->returnData();
        }
        if($this->pingHost() === false){
            return $this->returnData();
        }
        $this->setTransCode('b2e005023');
        $this->setBatchID($this->createBatchId());
        $this->setClientPatchID($this->createClientPatchID());

        $url = $this->agencyHost.$this->b2e005023.'?userPassword='.$this->userPassword;

        $Transaction = [
            'Transaction' => [
                'SystemHead'   => [],
                'TransHead'    => [],
                'TransContent' => []
            ]
        ];
        $Transaction['Transaction']['SystemHead'] = $this->getSystemHead();// 报文头
        $Transaction['Transaction']['TransHead'] = $this->getTransHead();// 报文头

        // 报文体
        $ReqData = [
            "ClientPatchID" => $this->ClientPatchID,
            "OperationType" => $OperationType,// 操作类型 0-查询,1-下载(文件生成) 注：1-下载 时该交易为查询回单信息，并生成对应文件。需发下载交易进行下载。
            "BillNumber" => $BillNumber,// 回单号
            "ActNo" => $this->OPERActNo,// 操作员的个人账号（非客户号）
            "BeginDate" => $BeginDate,// 交易开始日期
            "EndDate" => $EndDate,// 交易结束日期,终止日期应小于等于当前日期！
            "BeginAmount" => '',// 交易起始金额
            "EndAmount" => '',// 交易终止金额
            "BeginNum" => $BeginNum,// 分页查询：起始笔数（偏移量） >=1
            "QueryNum" => $QueryNum,// 分页查询：查询笔数 >=1 && <=50
        ];

        $Transaction['Transaction']['TransContent']['ReqData'] = $ReqData;

        $RequestXmlStr = $this->getRequestXmlStr($Transaction);

        $data = [
            'url'           => $url,
            'RequestXmlStr' => $RequestXmlStr,
            'ClientPatchID' => $ReqData['ClientPatchID']
        ];

        return $this->returnData($data);
    }

    /**
     * 电子回单查询及下载
     *      提供 pdf 格式的电子回单文件下载服务
     * @param string $file_name 文件名
     * @return array
     */
    public function downloadFile_b2e005023($file_name){
        if(!isset($this->downloadFile) or empty($this->downloadFile)){
            $this->_errorMsg = '接口 downloadFile 配置缺失';
            return $this->returnData();
        }
        /*if($this->pingHost() === false){
            return $this->returnData();
        }*/ // 导致循环调用
        $this->setTransCode('b2e005023');
        $this->setBatchID($this->createBatchId());
        $this->setClientPatchID($this->createClientPatchID());

        $url = $this->agencyHost.$this->downloadFile.'?userPassword='.$this->userPassword.'&transcode='.$this->TransCode.'&fileName='.$file_name;

        $Transaction = [
            'Transaction' => [
                'SystemHead'   => [],
                'TransHead'    => [],
                'TransContent' => []
            ]
        ];
        $Transaction['Transaction']['SystemHead'] = $this->getSystemHead();// 报文头
        $Transaction['Transaction']['TransHead'] = $this->getTransHead();// 报文头


        $RequestXmlStr = $this->getRequestXmlStr($Transaction);

        $data = [
            'url'           => $url,
            'RequestXmlStr' => $RequestXmlStr,
            'ClientPatchID' => $this->ClientPatchID
        ];

        return $this->returnData($data);
    }

    /**
     * 下载 请求 XML 内容到本地文件中
     * @param string $TransCode 交易代码
     * @param string $OrderId   单据编号
     * @param string $RequestXml 请求XML报文
     * @link /home/get_cache_files?dir=cebbank/require/20211027
     */
    private function downloadRequestXmlFile($TransCode,$OrderId,$RequestXml){
        file_put_contents(get_export_path('cebbank/require/'.$TransCode).$OrderId.'.xml',$RequestXml);
    }

    /**
     * 下载 响应 XML 内容到本地文件中
     * @param string $TransCode 交易代码
     * @param string $OrderId   单据编号
     * @param string $ResponseXml 响应XML报文
     * @link /home/get_cache_files?dir=cebbank/response/20211027
     */
    private function downloadResponseXmlFile($TransCode,$OrderId,$ResponseXml){
        file_put_contents(get_export_path('cebbank/response/'.$TransCode).$OrderId.'.xml',$ResponseXml);
    }

    /**
     * 验证数据 是否符合要求
     * @param array $data
     * @return bool|string
     */
    public function checkData($data){
        if(in_array($data['transferType'],['2120',2124]) and empty($data['cnaps_code'])){
            return '联行号：行外必输，跨行互联转账必输';
        }

        $data_content = json_encode($data);
        if(stripos($data_content,'<') !== false or stripos($data_content,'&') !== false){
            return '数据不能含有<和&符号';
        }

        return true;
    }

    /**
     * 上传 XML 报文并解析返回XML结果
     * @param string $url  请求接口地址
     * @param string $xmlData  xml字符串数据
     * @return mixed
     * @throws Exception
     */
    public function curlPostXml($url,$xmlData){
        $this->downloadRequestXmlFile($this->JnlDate,$this->ClientPatchID,$xmlData);

        $ch = curl_init();  // 初始一个curl会话
        curl_setopt($ch, CURLOPT_URL, $url);    // 设置url
        curl_setopt($ch, CURLOPT_POST, 1);  // post 请求
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:text/xml; charset=gb2312"));// 一定要定义content-type为xml，要不然默认是text/html！
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);//post提交的数据包
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // PHP脚本在成功连接服务器前等待多久，单位秒
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);// 不要打印数据
        curl_setopt($ch, CURLOPT_HEADER, 0);

        $result = curl_exec($ch);// 抓取URL并把它传递给浏览器
        $this->downloadResponseXmlFile($this->JnlDate,$this->ClientPatchID,$result);

        if ($error = curl_errno($ch)) {
            throw new Exception('银企通代理软件服务不可连接，请确认启动并登录成功后重新操作！'.$error);
        }
        curl_close($ch);//关闭cURL资源，并且释放系统资源

        // XML是GB2312/GBK格式的，转换为UTF-8再解析
        if(stripos($result,"encoding=\"GB2312\"") !== false){
            $result = str_replace("encoding=\"GB2312\"", "encoding=\"UTF-8\"", $result);
            $result = mb_convert_encoding($result,  'UTF-8','GB2312');
        }elseif(stripos($result,"encoding=\"GBK\"") !== false){
            $result = str_replace("encoding=\"GBK\"", "encoding=\"UTF-8\"", $result);
            $result = mb_convert_encoding($result,  'UTF-8','GBK');
        }

        // XML 内容转换为 数组
        $obj = simplexml_load_string($result, "SimpleXMLElement", LIBXML_NOCDATA);
        $arrayData = json_decode(json_encode($obj), true);

        return $arrayData;
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
}