<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/29
 * Time: 14:14
 */
class Purchase_suggest_model extends Api_base_model
{

    //api conf  /modules/api/conf/supplier_sys_supplier_audit.php
    protected $_baseUrl = "";
    protected $_statusListApi = "";
    protected $_getOneApi = "";
    protected $_getListApi = "";
    protected $_getToRejectListApi = "";
    protected $_createPurchaseOrderApi = "";
    protected $_quickCreatePurchaseOrderApi = "";
    protected $_addSalesNote = '';


    public function __construct()
    {
        parent::__construct();

        $this->init();
        $this->setContentType('');
    }

    public function demand_export_csv($data){


        $url = $this->_baseUrl . $this->_demand_export_csv . "?" . http_build_query($data);
        
        $result = $this->httpRequest($url, "", 'GET');

        return ['data_list' => isset($result['data_list']) ? $result['data_list'] : []];
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

    public function transferToStandbyOrder($params){

        $url = $this->_baseUrl.$this->_transferToStandbyOrder;
        $result = parent::httpRequest($url, $params, 'POST');
        return $result;
    }

    public function mergetransferToStandbyOrder($params){

        $url = $this->_baseUrl.$this->_mergetransferToStandbyOrder;
        $result = parent::httpRequest($url, $params, 'POST');
        return $result;
    }

    public function demand_lock($params){

        $url = $this->_baseUrl.$this->_demand_lock;
        $result = parent::httpRequest($url, $params, 'POST');
        return $result;
    }

    public function deadline_lock($params){

        $url = $this->_baseUrl.$this->_deadline_lock;
        $result = parent::httpRequest($url, $params, 'POST');
        return $result;
    }


    public function get_demand_datas($data){

        //调用服务层api
        $url = $this->_baseUrl . $this->_get_demand_datas . "?" . http_build_query($data);
        $result = $this->httpRequest($url, "", 'GET');
        return ['data_list' => isset($result['data_list']) ? $result['data_list'] : []];
    }

    /**
     *
     * @author liwuxue
     * @date 2019/1/29 14:43
     * @param $url
     * @param $param
     * @param $method
     * @throws Exception
     * @return mixed|array
     */
    public function httpRequest($url, $param = '', $method = "POST",$curlOption = [])
    {
        $result = parent::httpRequest($url, $param, $method);
        $this->verifyApiResp($result);
        return $result;
    }

    /**
     * 处理服务层返回结果
     * @author liwuxue
     * @date 2019/1/29 14:21
     * @param $api_resp
     * @throws Exception
     */
    private function verifyApiResp($api_resp)
    {
        if (!isset($api_resp['status']) || $api_resp['status'] !== 1) {
            if(isset($api_resp['errorMess']) and $api_resp['errorMess']){
                throw new Exception($api_resp['errorMess'], -1);
            }else{
                throw new Exception(json_encode($api_resp, JSON_UNESCAPED_UNICODE), -1);
            }
        }
    }

    /**
     * 采购需求 相关状态 下拉框列表
     * @author liwuxue
     * @date 2019/1/29 14:20
     * @param array $req = [type:is_include_tax, get_all:1]
     * @throws Exception
     * @return mixed|array
     */
    public function get_status_list($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_statusListApi . "?" . http_build_query($req);
        $result = $this->httpRequest($url, "", 'GET');
        return ['data_list' => isset($result['data_list']) ? $result['data_list'] : []];
    }

    /**
     * 根据 ID 或 备货单号 获取一个需求信息
     * @author liwuxue
     * @date 2019/1/29 15:11
     * @param array $req = [id:1, demand_number:1]
     * @throws Exception
     * @return mixed
     */
    public function get_one($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getOneApi . "?" . http_build_query($req);;
        $result = $this->httpRequest($url, "", 'GET');
        return ['data_list' => isset($result['data_list']) ? $result['data_list'] : []];
    }

    /**
     * 备货单作废到需求单
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=20664
     */
    public function cancel_suggest_to_demand($query)
    {
        $url = $this->_baseUrl.$this->_cancelSuggestToDemand;
        return parent::httpRequest($url, $query, 'POST');
    }

    /**
     * 获取列表
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $req
     * @return mixed|array
     * @throws Exception
     */
    public function get_list($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getListApi;
        //print_r($req);die();
        $result = $this->httpRequest($url, $req, 'POST');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
        ];
    }


    /**
     * 获取列表
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $req
     * @return mixed|array
     * @throws Exception
     */
    public function get_sum($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getSumApi . "?" . http_build_query($req);;
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_sum' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
        ];
    }


    /**
     * 待驳回需求列表
     * @author liwuxue
     * @date 2019/1/30 9:39
     * @param $get
     * @throws Exception
     * @return mixed
     */
    public function get_to_reject_list($get)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getToRejectListApi . "?" . http_build_query($get);;
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
        ];
    }

    /**
     * 根据 需求 ID 创建采购单
     * @author liwuxue
     * @date 2019/1/30 9:39
     * @param $get
     * @throws Exception
     * @return mixed
     */
    public function create_purchase_order($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_createPurchaseOrderApi;
        $result = parent::httpRequest($url, $post, 'POST');
        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:".json_encode($result,256),
                -1
            );
        }
    }

    public function preview_create_purchase_order($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_previewCreatePurchaseOrder;
        $result = parent::httpRequest($url, $post, 'POST');
        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:".json_encode($result,256),
                -1
            );
        }
    }


    /**
     * 采购需求导出
     * @author Jaden
     * 2019-1-16
     */
    public function web_purchase_export_list($params){
        $url = $this->_baseUrl . $this->_exportUrl;
        $url .= '?' . http_build_query($params);
        $data = getCurlData($url, "","POST", "",false,array('time_out'=>600,'conn_out'=>600));
        $result = json_decode($data,true);
        return $result;
//        if (empty($result) || !isset($result['status'])) {
//            $this->_errorMsg = "Request Error:" . json_encode($result);
//            return null;
//        }
//        if (0==$result['status']) {
//            $this->_errorMsg = $result['data_list'];
//        }
//        if (!$result['status']) {
//            return null;
//        }
//        // End
//        return $result;
    }


    /**
     * 一键生成采购单-根据查询条件
     * @author liwuxue
     * @date 2019/1/30 10:17
     * @param $post
     * @throws Exception
     * @return mixed
     */
    public function create_purchase_order_onekey($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_quickCreatePurchaseOrderApi;
        $result = parent::httpRequest($url, $post, 'POST');
        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:".json_encode($result,256),
                -1
            );
        }
    }


    /**
     * 采购需求添加备注
     * @author Jolon
     * @date 2019/3/15 15:11
     * @param array $post
     * @return mixed
     */
    public function add_sales_note($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_addSalesNote;
        $result = parent::httpRequest($url, $post, 'POST');
        return $result;
    }

    /**
     * @desc 需求单作废
     * @author Jeff
     * @Date 2019/4/18 17:12
     * @param $post
     * @return array
     * @throws Exception
     * @return
     */
    public function demand_order_cancel($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_cancelDemandOrder;
        $result = parent::httpRequest($url, $post, 'POST');
        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:".json_encode($result,256),
                -1
            );
        }
    }

    /**
     * 获取列表
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $req
     * @return mixed|array
     * @throws Exception
     */
    public function get_un_audit_list($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getUnAuditSuggest . "?" . http_build_query($req);
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
        ];
    }

    /**
     * 未审核采购需求导出
     * @author Jeff
     * 2019-1-16
     */
    public function un_audit_export_list($params){
        $url = $this->_baseUrl . $this->_getUnAuditSuggestExport;
        $url .= '?' . http_build_query($params);

        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }

    /**
     * @desc 需求单审核
     * @author Jeff
     * @Date 2019/4/18 17:12
     * @param $post
     * @return array
     * @throws Exception
     * @return
     */
    public function audit_suggest($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_auditSuggest;
        $result = parent::httpRequest($url, $post, 'POST');
        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:".json_encode($result,256),
                -1
            );
        }
    }


    /**
     * @desc 需求单 导入
     * @author Jeff
     * @param $post
     * @return array
     * @throws Exception
     * @return
     */
    public function import_suggest($post)
    {
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $url = $this->_baseUrl . $this->_importSuggest;//调用服务层api

        $file_path = $post['file_path'];
        $fileExp   = explode('.', $file_path);
        $fileExp   = strtolower($fileExp[count($fileExp) - 1]);//文件后缀

        include APPPATH.'third_party/PHPExcel/IOFactory.php';
        if ($fileExp == 'xls') $PHPReader = new \PHPExcel_Reader_Excel5();
        if ($fileExp == 'xlsx') $PHPReader = new \PHPExcel_Reader_Excel2007();
        if(!isset($PHPReader)){
            $return['code']    = false;
            $return['message'] = "只能导入 xls 或 xlsx 文件 ";
            $return['data']    = '';
            return $return;
        }

        $PHPReader      = $PHPReader->load($file_path);
        $currentSheet   = $PHPReader->getSheet(0);
        $sheetData      = $currentSheet->toArray(null,true,true,true);

        $error_list = [];
        if($sheetData){
            foreach($sheetData as $key => $value){
                if($key <= 1) continue;
                $purchase_type = trim($value['A']);

                if(empty($purchase_type) && empty($value['B'])) continue;
                if(intval($value['C']) <= 0 && intval($value['D']) <= 0){
                    $error_list[$key] = "常规数量和活动数量缺失";
                }elseif(empty($value['F'])){
                    $error_list[$key] = "是否加急缺失";
                }elseif(empty($purchase_type) || !in_array($purchase_type,['FBA','国内仓', 'FBA大货', '海外仓'])){
                    $error_list[$key] = "需求业务线错误";
                }
                if(!empty($purchase_type) && in_array($purchase_type, ['海外仓']) && empty($value['H'])){
                    $error_list[$key] = "海外仓业务是否熏蒸必填";
                }
                if(!empty($purchase_type) && in_array($purchase_type, ['海外仓']) && empty($value['I'])){
                    $error_list[$key] = "海外仓是否海外仓精品必填";
                }
                if($purchase_type == '海外仓'){
                    if(empty($value['K'])){
                        $error_list[$key] = "目的仓必填";
                    }elseif(empty($value['M'])){
                        $error_list[$key] = "物流类型必填";
                    }elseif(empty($value['J'])){
                        $error_list[$key] = "采购仓库缺失";
                    }
                }elseif($purchase_type == 'FBA'){
                    if(empty($value['N'])){
                        $error_list[$key] = "平台必填";
                    }elseif(empty($value['O'])){
                        $error_list[$key] = "站点必填";
                    }elseif(empty($value['P'])){
                        $error_list[$key] = "销售分组必填";
                    }elseif(empty($value['Q'])){
                        $error_list[$key] = "销售名称必填";
                    }elseif(empty($value['R'])){
                        $error_list[$key] = "销售账号必填";
                    }elseif(empty($value['G'])){
                        $error_list[$key] = "是否退税必填";
                    }
                }elseif($purchase_type == '国内仓'){
                    if(empty($value['J'])){
                        $error_list[$key] = "采购仓库缺失";
                    }elseif($value['E']===''){
                        $error_list[$key] = "缺货数量必填";
                    }
                }
            }
        }
//        print_r($error_list);exit;

        if($error_list){// 验证数据 出现错误
            $objPHPExcel   = PHPExcel_IOFactory::load($file_path);
            foreach($error_list as $key => $errorMsg){
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('S'.$key, $errorMsg);
                $objPHPExcel->getActiveSheet()->getStyle("S{$key}")->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
            }
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $file_name = 'Error-'.date('YmdHis').'.xls';
            $error_file_path = dirname(APPPATH). '/download_csv/suggest/'.$file_name;
            $objWriter->save($error_file_path);//文件保存路径
            $error_file_path = 'http://'.$_SERVER['HTTP_HOST']. '/download_csv/suggest/'.$file_name;

            $return['code'] = false;
            $return['message'] = "共有 ".count($error_list)." 条数据导入失败，是否下载报错结果";
            $return['data'] = $error_file_path;
            return $return;
        }

        $uid          = $post['uid'];
        $post['data'] = $sheetData;
//        print_r($post);exit;
        $data_string  = json_encode($post);
        $result = getCurlData($url.'?uid='.$uid,$data_string,'POST',array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ),false,array('time_out' => 900,'conn_out' => 0));
        $result = json_decode($result,true);
        if(empty($result['status'])){
            $error_list    = $result['data_list'];
            if(empty($error_list)){// 程序错误
                $return['code'] = false;
                $return['message'] = isset($result['errorMess'])?$result['errorMess']:'程序发生错误';
                $return['data'] = '';
                return $return;
            }
            $objPHPExcel   = PHPExcel_IOFactory::load($file_path);
            foreach($error_list as $key => $errorMsg){
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('R'.$key, $errorMsg);
                $objPHPExcel->getActiveSheet()->getStyle("R{$key}")->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
            }
            $objWriter       = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $file_name       = 'Error-'.date('YmdHis').'.xls';
            $error_file_path = dirname(APPPATH). '/download_csv/suggest/'.$file_name;
            $objWriter->save($error_file_path);//文件保存路径
            $error_file_path = 'http://'.$_SERVER['HTTP_HOST']. '/download_csv/suggest/'.$file_name;

            $return['code'] = false;
            $return['message'] = "共有 ".count($error_list)." 条数据导入失败，是否下载报错结果";
            $return['data'] = $error_file_path;
            return $return;
        }else{
            $return['code'] = true;
            $return['message'] = '导入成功';
            $return['data'] = '';
            return $return;
        }
    }


    /** 变更采购员
     * @param $post
     * @return array|mixed
     */
    public function change_purchaser($post){
        $url = $this->_baseUrl.$this->_changePurchaser;
        $result = parent::httpRequest($url, $post, 'POST');
        return $result;
    }


    /**
     * @desc 需求单解锁
     * @author Jeff
     * @Date 2019/4/18 17:12
     * @param $post
     * @return array
     * @throws Exception
     * @return
     */
    public function unlock_suggest($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_unlockSuggestApi;
        $result = parent::httpRequest($url, $post, 'POST');
        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:".json_encode($result,256),
                -1
            );
        }
    }

    /**
     * 获取列表
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $req
     * @return mixed|array
     * @throws Exception
     */
    public function get_not_entities_lock_list($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getNotEntitiesLockListApi . "?" . http_build_query($req);;
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
        ];
    }


    /**
     * 获取列表
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $req
     * @return mixed|array
     * @throws Exception
     */
    public function get_not_entities_lock_list_sum($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getNotEntitiesLockListSumApi . "?" . http_build_query($req);;
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_sum' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
        ];
    }


    /**
     * 采购需求非实体锁单列表导出
     * @author Jaden
     * 2019-1-16
     */
    public function not_entities_lock_export($params){
        $url = $this->_baseUrl . $this->_notEntitiesLockExportApi;
        $url .= '?' . http_build_query($params);
        $data = getCurlData($url, "","POST", "",false,array('time_out'=>600,'conn_out'=>600));
        $result = json_decode($data,true);
        return $result;
    }


    /**
     * 获取列表
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $req
     * @return mixed|array
     * @throws Exception
     */
    public function get_entities_lock_list($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getEntitiesLockListApi . "?" . http_build_query($req);;
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
        ];
    }


    /**
     * 获取列表
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $req
     * @return mixed|array
     * @throws Exception
     */
    public function get_entities_lock_list_sum($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getEntitiesLockListSumApi . "?" . http_build_query($req);;
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_sum' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
        ];
    }


    /**
     * 采购需求实体锁单列表导出
     * @author Jaden
     * 2019-1-16
     */
    public function entities_lock_export($params){
        $url = $this->_baseUrl . $this->_EntitiesLockExportApi;
        $url .= '?' . http_build_query($params);
        $data = getCurlData($url, "","POST", "",false,array('time_out'=>600,'conn_out'=>600));
        $result = json_decode($data,true);
        return $result;
    }
    
    /**
     * @desc 导入 优化需求单 文件(计划部修改采购数量和仓库用)
     * @author Jeff
     * @param $post
     * @return array
     * @throws Exception
     * @return
     */
    public function import_change_suggest($post)
    {
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $url = $this->_baseUrl . $this->_importChangeSuggest;//调用服务层api

        $file_path = $post['file_path'];
        $fileExp   = explode('.', $file_path);
        $fileExp   = strtolower($fileExp[count($fileExp) - 1]);//文件后缀

        include APPPATH.'third_party/PHPExcel/IOFactory.php';
        if ($fileExp == 'xls') $PHPReader = new \PHPExcel_Reader_Excel5();
        if ($fileExp == 'xlsx') $PHPReader = new \PHPExcel_Reader_Excel2007();
        if(!isset($PHPReader)){
            $return['code']    = false;
            $return['message'] = "只能导入 xls 或 xlsx 文件 ";
            $return['data']    = '';
            return $return;
        }

        $PHPReader      = $PHPReader->load($file_path);
        $currentSheet   = $PHPReader->getSheet(0);
        $sheetData      = $currentSheet->toArray(null,true,true,true);

        $error_list = [];
        if($sheetData){
            foreach($sheetData as $key => $value){
                if($key <= 1) continue;

                $purchase_sku = trim($value['A']);

                if (empty($purchase_sku)){
                    $error_list[$key] = "sku必填";
                }

                if (empty($value['B'])){
                    $error_list[$key] = "采购数量缺失";
                }

                if (empty($value['C'])){
                    $error_list[$key] = "采购仓库缺失";
                }

            }
        }
//        print_r($error_list);exit;

        if($error_list){// 验证数据 出现错误
            $objPHPExcel   = PHPExcel_IOFactory::load($file_path);
            foreach($error_list as $key => $errorMsg){
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$key, $errorMsg);
                $objPHPExcel->getActiveSheet()->getStyle("E{$key}")->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
            }
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $file_name = 'Error-'.date('YmdHis').'.xls';
            $error_file_path = dirname(APPPATH). '/download_csv/suggest/'.$file_name;
            $objWriter->save($error_file_path);//文件保存路径
            $error_file_path = 'http://'.$_SERVER['HTTP_HOST']. '/download_csv/suggest/'.$file_name;

            $return['code'] = false;
            $return['message'] = "共有 ".count($error_list)." 条数据导入失败，是否下载报错结果";
            $return['data'] = $error_file_path;
            return $return;
        }

        $uid          = $post['uid'];
        $post['data'] = $sheetData;
        $data_string  = json_encode($post);
        $result = getCurlData($url.'?uid='.$uid,$data_string,'POST',array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ),false,array('time_out' => 900,'conn_out' => 0));
        $result = json_decode($result,true);

        if(empty($result['status'])){
            $error_list    = $result['data_list'];
            if(empty($error_list)){// 程序错误
                $return['code'] = false;
                $return['message'] = isset($result['errorMess'])?$result['errorMess']:'程序发生错误';
                $return['data'] = '';
                return $return;
            }
            $objPHPExcel   = PHPExcel_IOFactory::load($file_path);
            foreach($error_list as $key => $errorMsg){
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$key, $errorMsg);
                $objPHPExcel->getActiveSheet()->getStyle("E{$key}")->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
            }
            $objWriter       = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $file_name       = 'Error-'.date('YmdHis').'.xls';
            $error_file_path = dirname(APPPATH). '/download_csv/suggest/'.$file_name;
            $objWriter->save($error_file_path);//文件保存路径
            $error_file_path = 'http://'.$_SERVER['HTTP_HOST']. '/download_csv/suggest/'.$file_name;

            $return['code'] = false;
            $return['message'] = "共有 ".count($error_list)." 条数据导入失败，是否下载报错结果";
            $return['data'] = $error_file_path;
            return $return;
        }else{
            $return['code'] = true;
            $return['message'] = '导入成功'.$result['errorMess'];
            $return['data'] = '';
            return $return;
        }
    }

    /**
     * 删除备注或作废原因
     * @author jeff
     * @date 2019/3/15 15:11
     * @param array $post
     * @return mixed
     */
    public function delete_sales_note($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_deleteSalesNote;
        $result = parent::httpRequest($url, $post, 'POST');
        return $result;
    }

    /**
     * @desc 需求单作废
     * @author Jeff
     * @Date 2019/4/18 17:12
     * @param $post
     * @return array
     * @throws Exception
     * @return
     */
    public function demand_order_cancel_confirm($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_demandOrderCancelConfirm;
        $result = parent::httpRequest($url, $post, 'POST');
        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:".json_encode($result,256),
                -1
            );
        }
    }


    /**
     * 海外仓金额列表
     * @return mixed|array
     */
    public function oversea_refund_list($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_overseaRefundList . "?" . http_build_query($req);;
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
        ];
    }


    /**
     * 海外仓金额设置
     * @param array $post
     * @return mixed
     */
    public function oversea_refund_set($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_overseaRefundSet;
        $result = parent::httpRequest($url, $post, 'POST');
        return $result;
    }

    public function get_demand_config($params){

        $url = $this->_baseUrl . $this->_get_demand_config."?uid=".$params['uid'];
        $result = $this->httpRequest($url, "", 'GET');
        return ['data_list' => isset($result['data_list']) ? $result['data_list'] : []];
    }

    public function save_demand_config($params){

        //调用服务层api
        $url = $this->_baseUrl . $this->_save_demand_config;
        //echo $url;die();
        $result = parent::httpRequest($url, $params, 'POST');
        return $result;
    }
    public function del_purchase_demand($params){
        $url = $this->_baseUrl . $this->_del_purchase_demand;
        $result = parent::httpRequest($url, $params, 'POST');
        return $result;
    }
}