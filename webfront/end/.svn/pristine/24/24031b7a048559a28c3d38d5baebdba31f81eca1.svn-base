<?php
/**
 * Created by PhpStorm.
 * 产品基础信息表
 * User: Jolon
 * Date: 2018/12/29 0029 11:50
 */
class Product_model extends Api_base_model {
    protected $table_name   = 'product';// 数据表名称


    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    public function push_product_import($post){

        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $file_path = $post['file_path'];
        $fileExp   = explode('.', $file_path);
        $fileExp   = strtolower($fileExp[count($fileExp) - 1]);//文件后缀

        include APPPATH.'third_party/PHPExcel/IOFactory.php';
        if ($fileExp == 'csv') $PHPReader = new \PHPExcel_Reader_CSV();
        if(!isset($PHPReader)){
            $return['code']    = false;
            $return['message'] = "只能导入 csv 文件 ";
            $return['data']    = '';
            return $return;
        }

        $url = $this->_baseUrl. $this->_push_product_import.'?uid='.$post['uid'];
        $result = getCurlData($url,$post,'post');


        $result = json_decode($result,True);
        if( isset($result['status']) && $result['status'] == 0 && $result['errorMess'])
        {
            $result = array(

                'status' => 0,
                'errorMess' => isset($result['errorMess']['error_message'])?$result['errorMess']['error_message']:'',
                'data_list' => isset($result['errorMess']['error_list'])?$result['errorMess']['error_list']:''
            );
        }
        return $result;

    }

    /**
     * @desc 需求单 导入
     * @author Jeff
     * @param $post
     * @return array
     * @throws Exception
     * @return
     */
    public function import_product($post)
    {
        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $file_path = $post['file_path'];
        $fileExp   = explode('.', $file_path);
        $fileExp   = strtolower($fileExp[count($fileExp) - 1]);//文件后缀

        include APPPATH.'third_party/PHPExcel/IOFactory.php';
        if ($fileExp == 'csv') $PHPReader = new \PHPExcel_Reader_CSV();
        if(!isset($PHPReader)){
            $return['code']    = false;
            $return['message'] = "只能导入 csv 文件 ";
            $return['data']    = '';
            return $return;
        }
        $PHPReader = PHPExcel_IOFactory::createReader('CSV')
            ->setDelimiter(',')
            ->setInputEncoding('GBK') //不设置将导致中文列内容返回boolean(false)或乱码
            ->setEnclosure('"')
            ->setSheetIndex(0);

        $PHPReader      = $PHPReader->load($file_path);

        $currentSheet   = $PHPReader->getSheet();
        $sheetData      = $currentSheet->toArray(null,true,true,true);
        $out = array ();
        $n = 0;
        foreach($sheetData as $data){

            $num = count($data);
            $i =0;
            foreach($data as $data_key=>$data_value){
                $out[$n][$i] = trim($data_value);
                ++$i;
            }
            $n++;
        }

        $header = ['Content-Type: application/json'];
       // $url    = $this->_baseUrl . $this->_saveBatchEditOrderUrl.'?uid='.$params['uid'];
       // $result = getCurlData($url,json_encode($params),'post',$header);

        $params['import_arr'] = $out;
        $url = $this->_baseUrl. $this->_import_product.'?uid='.$post['uid'];
        $result = getCurlData($url,json_encode((object)$params,JSON_UNESCAPED_SLASHES),'post',$header);
        $result = json_decode($result,True);
        if( isset($result['status']) && $result['status'] == 0 && $result['errorMess'])
        {
            $result = array(

                'status' => 0,
                'errorMess' => isset($result['errorMess']['error_message'])?$result['errorMess']['error_message']:'',
                'data_list' => isset($result['errorMess']['error_list'])?$result['errorMess']['error_list']:''
            );
        }
        return $result;
    }


    public function import_product_1($result)
    {
        $url = $this->_baseUrl. $this->_import_product;
        $url .= '?' . http_build_query($result);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }
    /**
     * 获取产品的基本信息
     * @author Jolon
     * @param string $sku SKU
     * @return array|bool
     */
    public function get_product_info($sku){
        if(empty($sku)) return false;

        $where = ['sku' => $sku];
        $product_info = $this->purchase_db->where($where)->get($this->table_name)->row_array();

        return $product_info;

    }

    /**
     * 获取 SKU销量（同步自 产品中心）
     * @author Jolon
     * @param  string $sku SKU
     * @param int     $day 指定天数
     * @return array|int|mixed
     */
    public function get_sku_days_sales($sku,$day = null){
        $days_sales_list = [
            7 => '10',
            15 => '20',
            30 => '35'
        ];
        if(isset($day)) return isset($days_sales_list[$day])?$days_sales_list[$day]:0;

        return $days_sales_list;
    }

    /**
     * 更改 SKU的默认供应商
     * @author Jolon
     * @param  string $sku            SKU
     * @param string  $supplier_code  目的供应商代码
     * @param float   $supplier_price 新采购价（供应商报价）
     * @return array
     */
    public function change_supplier($sku,$supplier_code = null,$supplier_price = null){
        $return = ['code' => false,'data' => '','msg' => ''];

        $product_info = $this->get_product_info($sku);
        if(empty($product_info)){
            $return['msg'] = '产品不存在';
            return $return;
        }

        if(empty($product_info)){
            $return['msg'] = '供应商不存在';
            return $return;
        }

        $detail = '';
        $update_data = [];

        $update_log = [// 产品更新日志
            'sku'                => $sku,
            'product_name'       => $product_info['product_name'],
            'old_supplier_code'  => $product_info['supplier_code'],
            'old_supplier_name'  => $product_info['supplier_name'],
            'old_supplier_price' => $product_info['purchase_price'],
            'create_remark'      => 'SKU屏蔽申请列表替换供应商',
            'audit_status'       => 2,
            'create_user_id'     => getActiveUserId(),
            'create_user_name'   => getActiveUserName(),
            'create_time'        => date('Y-m-d H:i:s'),
        ];
        if($supplier_code and $supplier_code != $product_info['supplier_code']){// 更新产品 供应商
            $this->load->model('supplier_model','',false,'supplier');
            $supplier_info = $this->supplier_model->get_supplier_info($supplier_code);

            if(empty($supplier_info)){
                $return['msg'] = '供应商不存在';
                return $return;
            }
            $update_data['supplier_code'] = $supplier_code;
            $update_data['supplier_name'] = $supplier_info['supplier_name'];

            $update_log['new_supplier_code'] = $supplier_code;
            $update_log['new_supplier_name'] = $supplier_info['supplier_name'];
            $detail .= '变更供应商，从【'.$product_info['supplier_code'].'】改为【'.$supplier_code.'】 ';
        }
        if($supplier_price and $supplier_price != $product_info['purchase_price']){// 最新采购价（供应商报价）
            $update_data['purchase_price'] = $supplier_price;

            $update_log['new_supplier_price'] = $supplier_price;
            $detail .= '变更采购价，从【'.$product_info['purchase_price'].'】改为【'.$supplier_price.'】 ';
        }
        if(empty($update_data)){
            $return['msg'] = '数据未发生改变';
            return $return;
        }

        $result = $this->purchase_db->where('sku',$sku)->update($this->table_name,$update_data);
        if($result){
            $this->purchase_db->insert('product_update_log',$update_log);
            operatorLogInsert(
                ['id'      => $sku,
                 'type'    => $this->table_name,
                 'content' => '默认供应商与采购价',
                 'detail'  => $detail
                ]);

            $return['code'] = true;
        }else{
            $return['msg'] = '修改SKU供应商数据时出错';
        }

        return $return;
    }


    /**
     * 获取 产品列表
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function get_product_list($params){
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_listUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = (isset($result['errorMess']) and $result['errorMess'])?$result['errorMess']:$result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }


    /**
     * 修改货源状态
     * @author Jaden
     * @param $params
     * @return array
     * 2019-1-8
     */
    public function web_update_supply_status($params){
        // 2.调用接口
        $url = $this->_baseUrl . $this->_updatesupplystatusUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        // 3.确认返回的数据是否与预期一样
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
     * 产品导出
     * @author Jaden
     * 2019-1-16
     */
    public function product_export_list($params){
        $url = $this->_baseUrl . $this->_exportUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '','GET');
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
     * 产品管理列表导出excel
     * @author sinder
     * @date 2019-05-27
     */
    public function product_export_excel_list($params){

        $this->load->helper('export_excel');
        $url = $this->_baseUrl . $this->_exportExcelUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');

        //$result = $this->_curlWriteHandleApi($url, $params, "POST");
        if( !empty($result) && isset($result['data_list']) ) {
            $data_list = $result['data_list'];

            if ( isset($result['status']) && $result['status']) {  //正常下载
                export_excel($data_list['heads'], $data_list['data_values'], $data_list['file_name'], $data_list['field_img_name'], $data_list['field_img_key']);
            } else {  //超出限制下载固定模板
                header('location:' . $result['errorMess']);
            }
        }else{
           echo "导出错误";

        }

    }

    /**
     * 产品数据修改
     * @author Jaden
     * 2019-1-16
     */
    public function modify_products($params){
        $url = $this->_baseUrl . $this->_updateproductUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (empty($result['status'])) {
            $this->_errorMsg = $result['errorMess'];
            return null;
        }
        // End
        return $result;
    }

    /**
     * 获取 产品详情页
     * @author Jolon
     * 2019-4-10
     */
    public function get_sku_detail_page($params)
    {
        $url  = constant('CG_API_HOST_' . static::MODULE_NAME).$this->_getSkuDetailPageUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');

        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (empty($result['status'])) {
            $this->_errorMsg = $result['errorMess'];
            return null;
        }
        // End
        return $result;
    }

    /**
     * 获取产系统SKU 图片
     * @params $sku   string  商品SKU
     * @author:luxu
     * @time: 2020/9/9
     **/

    public function get_product_image($sku){

        $url  = constant('CG_API_HOST_' . static::MODULE_NAME)."/product_api/getProductSystemImage";

        $url .= '?sku=' .$sku;
        $result = $this->httpRequest($url, '', 'GET');

        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (empty($result['status'])) {
            $this->_errorMsg = $result['errorMess'];
            return null;
        }

        if(!isset($result['data_list']['data'][$sku])){

            return NULL;
        }
        // End
        return $result['data_list']['data'][$sku];
    }

    /**
      * sku详情页，增加商品参数，需从新产品系统获取
     **/
    public function get_selectbysku($params){

        $url = $this->_baseUrl . $this->_get_selectbysku;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        if(isset($result['data_list']['goodsParams'])){

            return array(
                'goodsParams'=>$result['data_list']['goodsParams'],
                'productData'=>$result['data_list']['productData']);
        }
        return '';
    }

    /**

     **/
    public function get_attribute($post)
    {
        $url = $this->_baseUrl . $this->_get_produt_attribute;
        $resp = $this->_curlWriteHandleApi($url, $post, 'POST');
        return $resp;
    }



    /**
     * 显示产品修改页面
     * @param type $get
     * @return array
     */
    public function update_product_list($get){       
        //调用服务层api
        $url = $this->_baseUrl . $this->_updateProductApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');   
    }
    /**
     * 保存数据
     * @param type $post
     * @return array
     */
    public function update_product_save($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_updateProducsavetApi;
        $resp = $this->_curlWriteHandleApi($url, $post, 'POST');
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }

    /**
     * @desc 获取sku列表
     * @author Jackson
     * @parame array $parames 请求参数
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function get_sku_list(array $params)
    {

        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_getSkuListApi, $params, 'POST');

        //2.判断返回结果
        if (is_null($result)) {
            $result['status'] = 0;
            $result['errorMess'] = $this->_errorMsg;
            return $result;
        }
        // End

        return $result;
    }


    /**
     * @desc 获取产品最小起订量变更的日志记录
     * @author Jolon
     * @Date 2019-09-18 16:01:00
     * @return array()
     **/
    public function get_min_order_qty_log(array $params)
    {
        $result = $this->_curlRequestInterface($this->_getMinOrderQtyLog, $params, 'POST');

        if (is_null($result)) {
            $result['status'] = 0;
            $result['errorMess'] = $this->_errorMsg;
            return $result;
        }
        return $result;
    }

    /**
     * @desc:  获取商品SKU 的下单记录
     * @param:  $params    array    客户端请求产生
     * @author:luxu
     * @return: array
     **/
    public function get_product_purchase( $params )
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_get_product_purchase;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        $resp['status'] = 1;
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }

    /**
     * @desc: 获取产品SKU 单价日志
     * @param:  $params    array    客户端请求产生
     * @author:luxu
     * @return: array
     **/
    public function get_product_price_log($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_get_product_price_log . "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 获取字段列表
     * @author:luxu
     **/
    public function get_product_config($params)
    {
        $url = $this->_baseUrl . $this->_get_product_config."?uid=".$params['uid'];
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 获取原因列表
     * @author:luxu
     **/

    public function get_product_reason($params)
    {
        $url = $this->_baseUrl . $this->_get_product_reason."?uid=".$params['uid'];
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    public function add_product_reason($params)
    {
        $url = $this->_baseUrl . $this->_add_product_reason;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $resp;
    }

    public function add_reason($params)
    {
        $url = $this->_baseUrl . $this->_add_reason;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $resp;
    }

    public function get_reason_list( $params )
    {
        $url = $this->_baseUrl . $this->_get_reason_list;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $resp;
    }

    public function getProductSize($params){

        $url = $this->_baseUrl . $this->_getProductSize."?uid=".$params['uid'];

        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');

        return $resp;
    }

    /**
     * 更新外箱尺寸的配置信息
     * @author:luxu
     * @time:2020/4/13
     **/
    public function updateProductSize($params){

        $url = $this->_baseUrl . $this->_updateProductSize;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $resp;

    }

    /**
     * 外箱尺寸的配置信息(产品修改列表)
     * @author:luxu
     * @time:2020/4/13
     **/

    public function getSize($params){

        $url = $this->_baseUrl . $this->_getSize;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $resp;
    }

    /**
     * 获取 历史供应商数据
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function get_product_list_history($params){
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_historyListUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        // 3.确认返回的数据是否与预期一样
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
     * 历史sku导出
     * @author Jaden
     * 2019-1-16
     */
    public function history_product_export_list($params){
        $url = $this->_baseUrl . $this->_historyExportUrl;
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

    public function get_drop_box($params){
        $url = $this->_baseUrl . $this->_get_drop_box;
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
     * @function:商品管理SKU 退税率，修改日志。
     * 采购系统并不修改退税率，数据来自物流系统
     * @author:luxu
     * @time:2020年7月14号
     **/

    public function rateLogs($params){

        $url = $this->_baseUrl . $this->_rateLogs;
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
     * @function:商品管理SKU 开票点修改人在
     * 采购系统是可以修改开票点
     * @author:luxu
     * @time:2020年7月14号
     **/

    public function ticketedPoint($params){

        $url = $this->_baseUrl . $this->_ticketedPoint;
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


    public function get_set_table_header($params){

        $url = $this->_baseUrl . $this->_get_set_table_header;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }

    public function save_table_list($params){

        $url = $this->_baseUrl . $this->_save_table_list;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $resp;
    }

    public function get_headerlog($params){

        $url = $this->_baseUrl . $this->_get_headerlog;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $resp;
    }

}