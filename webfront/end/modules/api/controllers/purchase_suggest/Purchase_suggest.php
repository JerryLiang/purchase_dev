<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/29
 * Time: 14:12
 */
class Purchase_suggest extends MY_ApiBaseController
{
    /** @var Purchase_suggest_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_suggest/Purchase_suggest_model');
        $this->_modelObj = $this->Purchase_suggest_model;
    }

    /**
     * 获取需求单信息接口 需求：30695 一键合单(1)(需求单)备货单审核页面重构
     * @params   array  查询条件
     * @author:luxu
     * @time:2021年3月1号
     **/

    public function get_demand_datas(){

        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_demand_datas($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
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

    public function transferToStandbyOrder(){

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->transferToStandbyOrder($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function mergetransferToStandbyOrder(){

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->mergetransferToStandbyOrder($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function demand_lock(){

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->demand_lock($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function deadline_lock(){

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->deadline_lock($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }

    }

    /**
     * 采购需求 相关状态 下拉框列表
     * @url /api/purchase_suggest/purchase_suggest/get_status_list
     * @author liwuxue
     * @date 2019/1/29 14:19
     * @method GET
     * @param string type:
     * @param int get_all:
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=725
     */
    public function get_status_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_status_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 根据 ID 或 备货单号 获取一个需求信息
     * @author liwuxue
     * @date 2019/1/29 15:10
     * @method GET
     * @param int id  200
     * @param string demand_number   xswqq
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=730
     */
    public function get_one()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_one($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 备货单作废到需求单
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=20664
     */
    public function cancel_suggest_to_demand()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->cancel_suggest_to_demand($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 查询 采购需求列表
     * @author liwuxue
     * @date 2019/1/29 16:03
     * @param
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=731
     */
    public function get_list()
    {
        try {
            $this->_init_request_param("POST");

            $data = $this->_modelObj->get_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 查询 采购需求列表
     * @author liwuxue
     * @date 2019/1/29 16:03
     * @param
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=731
     */
    public function get_sum()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_sum($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 根据 需求 ID 创建采购单
     *      创建采购单 国内仓 | 海外仓 | FBA
     * @author liwuxue
     * @param ids
     * @date 2019/1/30 9:35
     * @method POST
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=736
     */
    public function create_purchase_order()
    {
        try {
            $data = $this->_modelObj->create_purchase_order($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @desc 生成采购单确认页
     * @author Jeff
     * @Date 2019/4/4 10:38
     * @return
     */
    public function preview_create_purchase_order()
    {
        try {
            $data = $this->_modelObj->preview_create_purchase_order($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 一键生成采购单-根据查询条件
     *      国内仓 | 海外仓 | FBA 一键创建采购单（根据查询条件获取可创建采购单的需求）
     * @author liwuxue
     * @param
     * @date 2019/1/30 9:35
     * @method POST
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=739
     */
    public function create_purchase_order_onekey()
    {
        try {
//            $this->load->model('ali/Ali_order_advanced_new_model');
//            $data = $this->Ali_order_advanced_new_model->one_key_create_purchase($this->_requestParams);
            $data = $this->_modelObj->create_purchase_order_onekey($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 一键生成采购单-根据查询条件
     *      国内仓 | 海外仓 | FBA 一键创建采购单（根据查询条件获取可创建采购单的需求）
     * @author liwuxue
     * @param
     * @date 2019/1/30 9:35
     * @method POST
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=739
     */
    public function create_purchase_order_onekey_new()
    {
        try {
            $this->load->model('ali/Ali_order_advanced_new_model');
            $data = $this->Ali_order_advanced_new_model->one_key_create_purchase($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 采购需求导出
     /purchase_suggest/purchase_suggest/purchase_export
     * @author Jaden
     */
    public function purchase_export(){
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_purchase_export_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
            $this->sendData($data);
        }else{
            $this->sendData($data);
        }
//        $tax_list_tmp = $data['data_list'];
      /*  $heads = ['备货单状态','图片','需求业务线','备货单号','SKU','是否新品','一级产品线','二级产品线','产品名称','备货数量',
            '缺货数量', '是否退税','开票点','单价','总金额','供应商','是否跨境','预计断货时间','采购仓库', '目的仓', '物流类型',
            '申请人', '申请时间', '采购员','过期时间','审核时间',
            '关联采购单号','采购单状态','采购单数量','未转在途取消数量','备注','作废原因'];
        csv_export($heads,$tax_list_tmp,'采购需求列表-'.date('YmdH:i:s'));*/

//        $tax_list_tmp = $data['data_list'];
//        header('location:'.$tax_list_tmp);
    }


    /**
     * 采购需求添加备注
     * @author Jolon
     * @date 2019/3/15 15:10
     * @method POST
     */
    public function add_sales_note()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->add_sales_note($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    public function demand_export_csv(){

        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->demand_export_csv($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }


    }

    /**
     * @desc 需求单作废
     * @author Jeff
     * @Date 2019/4/18 17:11
     * @return
     */
    public function cancel_demand_order()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->demand_order_cancel($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 查询 未审核采购需求列表
     * @author Jeff
     * @date 2019/5/05 16:03
     * @param
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=731
     */
    public function get_un_audit_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_un_audit_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 未审核采购需求单列表导出
    /purchase_suggest/purchase_suggest/un_audit_suggest_export
     * @author Jeff
     */
    public function un_audit_suggest_export(){
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->un_audit_export_list($params);
        $tax_list_tmp = $data['data_list'];
        $heads = ['备货单状态','预计到货时间','预计发货时间','发运类型','图片','需求业务线','备货单号','SKU','是否海外首单','是否新品','一级产品线','二级产品线','产品名称','产品状态','备货数量',
            '缺货数量','最小起订量','最小起订量单位', '是否退税','预计断货时间','采购仓库', '目的仓', '物流类型', '申请人', '平台', '站点', '销售分组',
            '销售名称', '销售账号', '销售备注','创建时间','作废原因','作废原因分类','是否精品','开发类型','所属小组'];
        csv_export($heads,$tax_list_tmp,'采购需求列表-'.date('YmdH_i_s'));
    }

    /**
     * @desc 需求单审核
     * @author Jeff
     * @Date 2019/4/18 17:11
     * @return
     */
    public function audit_suggest(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->audit_suggest($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 导入 需求单 文件
     */
    public function import_suggest(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        if(!isset($params['file_path']) or empty($params['file_path'])){
            $this->sendError(1, "文件地址参数缺失");
        }
        $file_path = $params['file_path'];
        if(!file_exists($file_path)){
            $this->sendError(1, "文件不存在[{$file_path}]");
        }
        $params['file_path'] = $file_path;
        $params['sku_status_type'] = (isset($params['sku_status_type']) and $params['sku_status_type'])?$params['sku_status_type']:1;// 备货单导入SKU状态类型，1.正常限制状态，2.正常+允许导入停售状态

        try {
            $data = $this->_modelObj->import_suggest($params);
            if($data['code']){
                $this->_code = 0;
                $this->sendData();
            }else{
                $this->_code = 500;
                $this->_msg = $data['message'];
                $this->sendData(['error_file_path' => $data['data']]);
            }
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 备货单变更采购员
     */
    public function change_purchaser(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->change_purchaser($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @desc 需求单审核
     * @author Jeff
     * @Date 2019/4/18 17:11
     * @return
     */
    public function unlock_suggest(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->unlock_suggest($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 查询 采购需求非实体锁单列表
     * @author liwuxue
     * @date 2019/1/29 16:03
     * @param
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=731
     */
    public function get_not_entities_lock_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_not_entities_lock_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 查询 采购需求非实体锁单列表统计
     * @author liwuxue
     * @date 2019/1/29 16:03
     * @param
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=731
     */
    public function get_not_entities_lock_list_sum()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_not_entities_lock_list_sum($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 采购需求非实体锁单列表导出
    /purchase_suggest/purchase_suggest/purchase_export
     * @author Jaden
     */
    public function not_entities_lock_export(){
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->not_entities_lock_export($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
            $this->sendData($data);
        }

        $tax_list_tmp = $data['data_list'];
        header('location:'.$tax_list_tmp);
    }

    /**
     * 查询 采购需求实体锁单列表
     * @author liwuxue
     * @date 2019/1/29 16:03
     * @param
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=731
     */
    public function get_entities_lock_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_entities_lock_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 查询 采购需求实体锁单列表统计
     * @author liwuxue
     * @date 2019/1/29 16:03
     * @param
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=731
     */
    public function get_entities_lock_list_sum()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_entities_lock_list_sum($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 采购需求实体锁单列表导出
    /purchase_suggest/purchase_suggest/purchase_export
     * @author Jaden
     */
    public function entities_lock_export(){
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->entities_lock_export($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
            $this->sendData($data);
        }

        $tax_list_tmp = $data['data_list'];
        header('location:'.$tax_list_tmp);
    }


    /*
     * desc 导入 优化需求单 文件(计划部修改采购数量和仓库用)
     * jeff 2019-0822
     */
    public function import_change_suggest(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        if(!isset($params['file_path']) or empty($params['file_path'])){
            $this->sendError(1, "文件地址参数缺失");
        }
        $file_path = $params['file_path'];
        if(!file_exists($file_path)){
            $this->sendError(1, "文件不存在[{$file_path}]");
        }
        $params['file_path'] = $file_path;

        try {
            $data = $this->_modelObj->import_change_suggest($params);
            if($data['code']){
                $this->_code = 0;
                $this->_msg = $data['message'];
                $this->sendData();
            }else{
                $this->_code = 500;
                $this->_msg = $data['message'];
                $this->sendData(['error_file_path' => $data['data']]);
            }
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 删除备注或作废原因
     * @author jeff
     * @date 2019/3/15 15:10
     * @method POST
     */
    public function delete_sales_note()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->delete_sales_note($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @desc 需求单作废确认
     * @author Jeff
     * @Date 2019/4/18 17:11
     * @return
     */
    public function demand_order_cancel_confirm()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->demand_order_cancel_confirm($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }




    /**
     * 海外仓需求金额配置
     */
    public function oversea_refund_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->oversea_refund_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 海外仓金额设置
     * @method POST
     */
    public function oversea_refund_set()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->oversea_refund_set($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取需求单配置信息 30708 一键合单(9)基础配置增加备货单自动生成
     * @param
     * @author:luxu
     **/
    public function get_demand_config(){

        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_demand_config($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function save_demand_config(){

        try {
            $this->_init_request_param("POST");
            //echo "1111";die();
            $data = $this->_modelObj->save_demand_config($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function del_purchase_demand(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->del_purchase_demand($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}