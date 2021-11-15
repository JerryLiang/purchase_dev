<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * [function desc]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Supplier extends MY_ApiBaseController
{
    /** @var  Supplier_model */
    private $_modelObj;
    private $_images;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/Supplier_model');
        $this->_modelObj = $this->Supplier_model;
    }

    /**
     * @desc 供应商信息管理分页列表
     * @author jackson
     * @Date 2019-01-21 15:26:00
     * @param1 $url 链接地址
     * @param2 $value 传入的参数
     * @return array()
     */
    public function index()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->getList($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function supplier_data() {

        $params = $this->_requestParams;
        $data = $this->_modelObj->getsupplier($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * @desc 获取下拉供应商列表
     * @author Jackson
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function get_supplier_list()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->get_supplier_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);

    }

    /**
     * @desc 获取静态下拉列表信息(supplier_settlement: 供应商结算方式，supplier_level:供应商等级、review_status:审核成功、cross_border:是否为跨境宝)
     * @author Jackson
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function get_static_list()
    {

        $data = array();
        $data['supplier_settlement'] = $this->_modelObj->get_supplier_settlemant();
        $data['supplier_level'] = $this->_modelObj->get_supplier_level();
        $data['review_status'] = $this->_modelObj->get_review_status();
        $data['cross_border'] = $this->_modelObj->get_cross_border();

        $this->sendData($data);
    }

    /**
     * @desc 获取供应商-联系方式
     * @author Jackson
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function supplier_contact()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->supplier_contact($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * @desc 获取详情
     * @author Jackson
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function details()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->get_details($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * @desc 更新供应商信息
     * @author Jackson
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function update_supplier()
    {
        $params = $this->_requestParams;
        list($status, $msg) = $this->_modelObj->update_supplier_info($params);
        $this->_code = $status ? 0 : $this->getServerErrorCode();
        $this->_msg = $msg;
        $this->sendData();
    }
   /**
     * 供应商采购员批量修改
     * @author harvin
     * @date 2019-4-20
     * @return  array
     */
    public function supplier_batch_buyer(){
         $params = $this->_requestParams;
         list($status, $msg) = $this->_modelObj->get_supplier_batch_buyer($params);
         $this->_code = $status ? 0 : $this->getServerErrorCode();
         $this->_msg = $msg;
         $this->sendData();    
    }
    /**
     * @desc 供应商审核数据预览
     * @author Jackson
     * @Date 2019-01-25 16:01:00
     * @return array()
     **/
    public function supplier_review_detail()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->supplier_review_detail($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * @desc 供应商审核
     * @author Jackson
     * @Date 2019-01-25 16:01:00
     * @return array()
     **/
    public function supplier_review()
    {

        $params = $this->_requestParams;
        list($status, $msg) = $this->_modelObj->supplier_review($params);

        $this->_code = $status ? 0 : $this->getServerErrorCode();
        $this->_msg = $msg;

        $this->sendData();
    }

    /**
     * @desc 禁用记录
     * @author Jackson
     * @Date 2019-01-25 16:01:00
     * @return array()
     **/
    public function is_desable()
    {
        $params = $this->_requestParams;
        list($status, $msg) = $this->_modelObj->supplier_disable($params);

        $this->_code = $status ? 0 : $this->getServerErrorCode();
        $this->_msg = $msg;

        $this->sendData();
    }

    /**
     * @desc 启用记录
     * @author Jackson
     * @Date 2019-01-25 16:01:00
     * @return array()
     **/
    public function is_enable()
    {
        $params = $this->_requestParams;
        list($status, $msg) = $this->_modelObj->supplier_enable($params);

        $this->_code = $status ? 0 : $this->getServerErrorCode();
        $this->_msg = $msg;

        $this->sendData();
    }

    /**
     * @desc 供应商支付帐号信息删除
     * @author Jackson
     * @Date 2019-01-30 14:01:00
     * @return array()
     **/
    public function delete_payment_account()
    {
        $params = $this->_requestParams;
        list($status, $msg) = $this->_modelObj->delete_payment_account($params);

        $this->_code = $status ? 0 : $this->getServerErrorCode();
        $this->_msg = $msg;

        $this->sendData();
    }

    /**
     * 获取供应商模糊查询
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param string supplier_name
     * @method get
     */
    public function supplier_list()
    {
        try {
            //  $this->_init_request_param("GET");
            $data = $this->_modelObj->supplier_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }



    /**
     * 获取某个sku历史供应商信息
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param string supplier_name
     * @method get
     */
    public function history_supplier_list()
    {
        try {
            //  $this->_init_request_param("GET");
            $data = $this->_modelObj->history_supplier_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取供应商管理创建人 模糊查询
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param string supplier_name
     * @method get
     */
    public function get_create_user()
    {
      //  $this->_init_request_param("GET");
        try {
            $data = $this->_modelObj->get_create_user($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取供应商账期授信信息
     * @author Jeff
     * @Date 2019/03/14 11:22
     * @method post
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1708
     */
    public function get_supplier_quota()
    {
        $params = $this->_requestParams;
        //目前只能获取 1688 账号管理表的 yibaisuperbuyers 的token 来查询accessToken,appKey,secKey信息

        try {
            $data = $this->_modelObj->get_supplier_quota($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }

    }

    /**
     * @desc 获取操作日志列表
     * @author bigfong
     * @Date 2019-04-09
     * @return array()
     **/
    public function get_op_log_list()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_op_log_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);

    }


    /**
     * @desc 获取操作日志列表
     * @author bigfong
     * @Date 2019-04-09
     * @return array()
     **/
    public function get_op_log_pretty_list()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_op_log_pretty_list($params);
        $this->sendData($data);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);

    }

    /**
     * @desc 获取支行信息
     * @author harvin
     * #date 2019-4-23
     * @return array
     */
    public function supplier_opening_bank(){
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_supplier_opening_bank($params);
        $this->sendData($data);
    }

    /**
     * @desc 验证供应商名称是否和启用的供应商重复
     * @author Jeff
     * @Date 2019/6/21 10:17
     */
    public function validate_supplier_name()
    {
        try{
            $params = $this->_requestParams;
            $this->_modelObj->validate_supplier_name($params);
            $this->sendData();
        }catch (Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @desc 删除图片信息 逻辑删除
     * @author Django
     * @Date  2019/6/25
     **/

    public function supplier_image_del()
    {
        try {

            $image_id =  $this->input->get_post('image_id');
            /**
              * @如果HTTP 没有传入图片ID 或者传入图片ID 为空 返回错误提示
             **/
            if( !isset($image_id) || empty($image_id) )
            {
                throw new Exception("请传入图片ID",505);
            }
            $result = $this->_modelObj->del_supplier_image($image_id);
            if( True == $result ) {
                $this->sendData();
            }
        }catch ( Exception $exp ) {
            $this->sendError($exp->getCode(),$exp->getMessage());

        }
    }

    /**
     * @desc  供应商图片保存接口
     * @param $image_id  保存图片ID  选填
     *
     **/
    public function supplier_image_down()
    {
        try {

            $image_id =  $this->input->get_post('image_id');
            /**
             * @如果HTTP 没有传入图片ID 或者传入图片ID 为空 返回错误提示
             **/
            if( !isset($image_id) || empty($image_id) )
            {
                throw new Exception("请传入图片ID",505);
            }
            $result = $this->_modelObj->get_supplier_image($image_id);
            $filename = $result['supplier_name'].$result['image_type'].".".pathinfo($result['image_url'])['extension'];
            header('Content-Disposition:attachment;filename=' . $filename);
            header('Content-Length:' . filesize($result['image_url']));
            readfile($result['image_url']);

        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(),$exp->getMessage());
        }
    }
    /**
     * 天眼接口查询
     * @author harvin
     * @date 2019-07-03
     */
    public function heaven_suppler(){
         try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_heaven_suppler($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /*
     * @desc天眼接口刷新供应商数据
     */
    public function heaven_refresh_supplier()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->heaven_refresh_supplier($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }



    }

    /**
     * 跨境宝批量修改勾选 wangliang
     */
    public function get_cross(){
        try {
            $data = $this->_modelObj->get_cross($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 批量修改跨境宝
     */
    public function update_supplier_cross(){
        try {
            $data = $this->_modelObj->update_supplier_cross($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 根据供应商链接获取店铺ID
     */
    public function get_shop_id(){
        try {
            $data = $this->_modelObj->get_shop_id($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /*
* @desc保存供应商翻译信息
*/
    public function save_trans_info()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->save_trans_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }



    }

    /*
* @desc保存供应商翻译信息
*/
    public function show_trans_info()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->show_trans_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }



    }

    /**
     * 手动刷新，1688是否支持跨境宝
     * /api/Supplier/Supplier/refresh_cross_border
     * @author Justin
     */
    public function refresh_cross_border()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->refresh_cross_border($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @desc 预禁用
     * @author Jackson
     * @Date 2019-01-25 16:01:00
     * @return array()
     **/
    public function pre_disable()
    {
        $params = $this->_requestParams;
        list($status, $msg) = $this->_modelObj->pre_disable($params);

        $this->_code = $status ? 0 : $this->getServerErrorCode();
        $this->_msg = $msg;

        $this->sendData();
    }


    public function get_relation_supplier_info() {

        $params = $this->_requestParams;
        $data = $this->_modelObj->get_relation_supplier_info($params);


        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     *更新供应商等级
     */
    public function update_supplier_level()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->update_supplier_level($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /*
     * @获取历史支付信息
     */
    public function get_history_payment_info()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_history_payment_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }



    }


    /**
     * @desc 供应商黑名单列表
     * @author jackson
     * @Date 2019-01-21 15:26:00
     * @param1 $url 链接地址
     * @param2 $value 传入的参数
     * @return array()
     */
    public function black_list()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->black_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * @desc 加入黑名单
     * @author Dean
     * @Date 2019-01-25 16:01:00
     * @return array()
     **/
    public function supplier_opr_black_list()
    {



        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->supplier_opr_black_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }




    }



    ///黑名单明细
    public function black_list_detail()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->black_list_detail($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * @desc 编辑关联供应商
     * @author Dean
     * @Date 2020-12-10 16:01:00
     * @return array()
     **/
    public function modify_relation_supplier()
    {



        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->modify_relation_supplier($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }




    }


    public function show_all_relation_supplier()
    {



        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->show_all_relation_supplier($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }




    }



    /**
     *更新供应商等级
     */
    public function update_supplier_product_line()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->update_supplier_product_line($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    public function audit_supplier_list() {

        $params = $this->_requestParams;
        $data = $this->_modelObj->audit_supplier_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }



    /*
 * @获取禁用供应商待确认绑定sku
 */
    public function get_confirm_sku_info()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_confirm_sku_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }



    }


    //供应商等级列表

    public function audit_supplier_level_grade_list() {

        $params = $this->_requestParams;
        $data = $this->_modelObj->audit_supplier_level_grade_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }



    //获取供应商分数等级审核历史记录
    public function get_audit_level_grade_log() {

        $params = $this->_requestParams;
        $data = $this->_modelObj->get_audit_level_grade_log($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     *level_grade_review等级审核
     */
    public function level_grade_review()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->level_grade_review($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     *申请供应商等级审核
     */
    public function modify_supplier_level_grade()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->modify_supplier_level_grade($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    //获取结算方式变更记录
    public function get_settlement_change() {

        $params = $this->_requestParams;
        $data = $this->_modelObj->get_settlement_change($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }



    /**
     * @desc 供应商信息管理分页列表
     * @author jackson
     * @Date 2019-01-21 15:26:00
     * @param1 $url 链接地址
     * @param2 $value 传入的参数
     * @return array()
     */
    public function supplier_visit_list()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->supplier_visit_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /*
* @desc 拜访供应商详情
*/
    public function get_visit_detail_info()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_visit_detail_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }



    }

    /**
     *申请拜访供应商
     */
    public function apply_visit()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->apply_visit($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @desc 拜访供应商审核列表
     * @author jackson
     * @Date 2019-01-21 15:26:00
     * @param1 $url 链接地址
     * @param2 $value 传入的参数
     * @return array()
     */
    public function visit_supplier_audit_list()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->visit_supplier_audit_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     *申请拜访供应商
     */
    public function audit_visit_supplier()
    {

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->audit_visit_supplier($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     *上传拜访报告
     */
    public function upload_visit_report()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->upload_visit_report($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }



    /**
     * 导出CSV-
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param
     * @method get
     * @doc
     */
    public function supplier_visit_list_csv()
    {
        try {
            $this->_init_request_param("GET");
            $this->_modelObj->supplier_visit_list_csv($this->_requestParams);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @desc 拜访供应商审核列表
     * @author jackson
     * @Date 2019-01-21 15:26:00
     * @param1 $url 链接地址
     * @param2 $value 传入的参数
     * @return array()
     */
    public function get_visit_op_log_list()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_visit_op_log_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 下载报告
     * @author 刘凯  20200730
     * @return  mixed
     */
    public function download_visit_report()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->download_visit_report($params);


        if ($params['type'] == 1) {
            $this->sendData($data);

        } else {
            try{
                if(!empty($data['data_list'])){
                    $this->load->model('compact/Print_pdf_model');
                    $this->Print_pdf_model->writePdfOnDom($data['data_list'], '', '', 'D', '');
                }else{
                    echo '未获取到下载数据';exit;
                }
            }catch(Exception $e){
                echo $e->getMessage();exit;
            }

        }





    }

    public function add_supplier_users() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->add_supplier_users($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function upd_supplier_users(){

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->upd_supplier_users($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }



    public function del_supplier_users(){



        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->del_supplier_users($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    public function show_supplier_users(){

        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->show_supplier_users($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }






















}