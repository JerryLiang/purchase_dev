<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * 采购需求控制器
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */

class Product extends MY_ApiBaseController{
    private $_modelObj;
    public function __construct(){
        parent::__construct();
        $this->load->model('product/Product_model');
        $this->_modelObj = $this->Product_model;

        /*
        parent::__construct();
        $this->load->model('product_model');
        $this->load->model('purchase_user_model','product_user',false,'user');
        $this->load->model('product_line_model','product_line',false,'product');
        $this->load->model('supplier_buyer_model','supplier_buyer_model',false,'supplier');
        $this->load->model('product_update_log_model','product_update_log',false,'product');
        */
    }

    /**
     * 产品相关状态 下拉列表
     * @author Jolon
     */
    public function get_status_list(){
        $status_type  = $this->input->get_post('type');
        $this->load->helper('status_order');

        switch(strtolower($status_type)){
            default :
                $status_type_name = '未知的状态类型';
                $data_list        = null;
        }

        if($data_list){
            http_response(response_format(1,$data_list));
        }else{
            http_response(response_format(0,[],$status_type_name));
        }
    }

    /**
     * 导入 需求单 文件
     */
    public function product_import(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        if(!isset($params['packag_file']) or empty($params['packag_file'])){
            $this->sendError(1, "文件地址参数缺失");
        }
        $file_path = $params['packag_file'];
        if(!file_exists($file_path)){
            $this->sendError(1, "文件不存在[{$file_path}]");
        }
        $params['file_path'] = $file_path;

        try {
            $data = $this->_modelObj->import_product($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 导入 需求单 文件
     */
    public function push_product_import(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        if(!isset($params['packag_file']) or empty($params['packag_file'])){
            $this->sendError(1, "文件地址参数缺失");
        }
        $file_path = $params['packag_file'];
        if(!file_exists($file_path)){
            $this->sendError(1, "文件不存在[{$file_path}]");
        }
        $params['file_path'] = $file_path;

        try {
            $data = $this->_modelObj->push_product_import($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 产品列表
     /product/product/product_list
     * @author Jaden
     */
    public function product_list(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_product_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);


    }

    /**
     * 产品导出
     /product/product/product_export
     * @author Jaden
     */
    public function product_export(){
        set_time_limit(0);
        try {
            $this->load->helper('export_csv');
            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->product_export_list($params);
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
            $this->sendData($data);
        } catch( Exception $exp ) {

            $this->sendData(array('status' => 0, 'errorMessage' => $exp->getMessage()));
        }
//        $tax_list_tmp = $data['data_list'];
//        header('location:'.$tax_list_tmp);
    }

    /**
     * 产品导出excel
     * @url /product/product/product_export_excel
     * @author sinder
     * @date 2019-05-27
     */
    public function product_export_excel(){
        set_time_limit(0);
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $this->_modelObj->product_export_excel_list($params);

    }

     /**
     * 修改产品货源状态
     /product/product/update_supply_status
     * @author Jaden
     */
    public function update_supply_status(){
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_update_supply_status($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }
   /**
    * 批量修改
    * @author harvin
    * @date 2019-06-06
    */
    public function update_product_list(){
         try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->update_product_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }
       /**
     * 保存修改数据
     * @author harvin
     * @date 2019-0606
     */
    public function update_product_save(){
         try {
            $data = $this->_modelObj->update_product_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }
    /**
     * 修改
     /product/product/update_products
     * @author Jaden
     */
    public function update_products(){
        $params = $this->_requestParams;
        $data = $this->_modelObj->modify_products($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);


    }


    /**
     * 获取 产品详情页
     * @author Jolon
     * 2019-4-10
     */
    public function get_sku_detail_page(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_sku_detail_page($params);
        $attribute = $this->_modelObj->get_attribute($params);
        $data['attribute'] = isset($attribute['data_list'])?$attribute['data_list']:null;
        $productData =$this->_modelObj->get_selectbysku($params);

        $productImage = $this->_modelObj->get_product_image($params['sku']);

        $imagesData = [];

        if(isset($productImage['dev_image']) and !empty($productImage['dev_image'])){

            $imagesData = $productImage['dev_image'];
        }
        if(isset($productImage['assistant_no_logo']) and !empty($productImage['assistant_no_logo'])){
            $imagesData = $productImage['assistant_no_logo'];
        }
        if(isset($productImage['assistant']) and !empty($productImage['assistant'])){

            $imagesData = $productImage['assistant'];
        }

        if(!empty($productData)) {
            $data['selectbysku'] = $productData['goodsParams'];
            $data['productData'] = $productData['productData'];
        }else{
            $data['selectbysku'] = NULL;
            $data['productData'] = NULL;
        }

        if(!empty($imagesData)){
            $data['imageData'] =$imagesData;
        }else {
            $data['imageData'] = $imagesData;
        }

        $this->sendData($data);
    }

    /**
     * @desc 获取sku列表
     * @author jeff
     * @Date 2019-07-30 16:01:00
     * @return array()
     **/
    public function get_sku_list()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->get_sku_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);

    }


    /**
     * @desc 获取产品最小起订量变更的日志记录
     * @author Jolon
     * @Date 2019-09-18 16:01:00
     * @return array()
     **/
    public function get_min_order_qty_log()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_min_order_qty_log($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * @desc  获取SKU 产品信息
     * @author : luxu
     * @return jsonstring
     **/
    public function get_product_purchase()
    {
        try
        {
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $data = $this->_modelObj->get_product_purchase($params);
            $this->sendData($data);
        }catch ( Exception $exp )
        {
            $this->sendError(0, $exp->getMessage());
        }
    }


    /**
     * @desc : 产品开发单价日志
     * @author:luxu
     * @return jsonstring
     **/

    public function get_product_price_log()
    {
        try
        {
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $data = $this->_modelObj->get_product_price_log($params);
            $this->sendData($data);
        }catch ( Exception $exp )
        {
            $this->sendError(0, $exp->getMessage());
        }
    }

    /**
     * 上传多张文件 （一次一张）
     * $_FILES['userfile'] 上传参数
     *@author harvin 2019-2-13
     * /api/product/product/do_upload
     **/
    public function do_upload() {
        $this->load->library('file_operation');
        $this->load->model('finance/Upload_receipt_model');

        $upload = $this->file_operation;

        //缓存本地
        $res = $upload->upload_file($_FILES['images'], 'product_related');
        if (isset($res['errorCode']) && $res['errorCode'] == 1) {
            $path =dirname(dirname(dirname(dirname(dirname(__FILE__))))).$res['file_info']['file_path'];

            $result= $this->Upload_receipt_model->upload_picture($path);
            $result= json_decode($result, TRUE);

            if($result && isset($result['code']) && $result['code']==1000){
                $java_img   = $result['data'][0]['fullUrl'];

                // 生成缩略图，
                $image_data = file_get_contents($path);
                $thumbnail  = $this->image_resize($image_data, 160, 120);
                if ($thumbnail != false) {
                    // 上传缩略图
                    $result_t = $this->Upload_receipt_model->upload_picture($thumbnail);
                    $result_t = json_decode($result_t, TRUE);
                    if ($result_t && isset($result_t['code']) && $result_t['code']==1000) {
                        $thumbnail_img = $result_t['data'][0]['fullUrl'];
                    } else {
                        $thumbnail_img = '缩略图上传失败:'.$thumbnail;
                    }

                    //删除本地
                    @unlink($thumbnail);
                }

                //删除本地
                @unlink($path);

                $this->sendData(['url_img'=>$java_img, 'thumbnail'=>$thumbnail_img]);
            }else{
                $data = ['status' => 0, 'errorMess' =>"上传fastdfs文件系统失败"];
                $this->sendData($data);
            }
        } else {
            $data = ['status' => 0, 'errorMess' => isset($res['errorMess']) ? $res['errorMess'] : '请求参数错误'];
            $this->sendData($data);
        }
    }

    /**
     * 生成缩略图，大小在10k以下--参考大小：4:3的情况下 160*120
     *
     * @author Martin
     * @date  2019-10-21
     * @param string    $image_data   图像数据，file_get_contents($path)得到的值
     * @param int       $width        缩放宽度
     * @param int       $height       缩放高度
     * @param int|float $scale        缩放比例 0 为不缩放；设置此参数大于0时，将忽略$width和$height
     * @param string    $reference    缩略图参考命名图片(按此图片名添加前缀作为缩略图的名称)
     * @return mixed                  成功返回生成缩略图路径，失败返回false
     */
    public function image_resize($image_data, $width, $height, $scale=0, $reference='')
    {
        // 图片类型
        // 1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，6 = BMP，7 = TIFF(intel byte order)，8 = TIFF(motorola byte order)
        // 9 = JPC，10 = JP2，11 = JPX，12 = JB2，13 = SWC，14 = IFF，15 = WBMP，16 = XBM

        // 获取原图片信息
        list($big_width, $big_height, $big_type) = getimagesizefromstring($image_data);
        if ($big_width == 0 || $big_height == 0) {
            return false;
        }

        // 获取缩放信息
        if ($scale > 0) {
            $width  = $big_width * $scale;
            $height = $big_height * $scale;
        }

        // 创建缩略图画板
        $im = imagecreatetruecolor($width, $height);
        if (!$im) {
            return false;
        }

        // 启用混色模式
        imagealphablending($im, true);

        // 保存png alpha通道信息
        imagesavealpha($im, true);

        // 创建原图画板
        $big_im = imagecreatefromstring($image_data);
        if (!$big_im) {
            return false;
        }

        // 缩放原图
        imagecopyresized($im, $big_im, 0, 0, 0, 0, $width, $height, $big_width, $big_height);

        // 保存文件路径
        $thumbnail_path = './end/upload/thumbnails/product/';
        if (!file_exists($thumbnail_path)) {
            mkdir($thumbnail_path, 0755, true);
        }

        if (empty($reference)) {
            $reference = 'product_'.date('YmdHis').rand(9999, 99999);
        }
        $savePath = $thumbnail_path .'thumbnail_'. $reference;

        // 保存生成的缩略图
        $res = false;
        switch ($big_type) {
            case 1:
                $savePath .= '.gif';
                $res = imagegif($im, $savePath);
                break;
            case 2:
                $savePath .= '.jpg';
                $res = imagejpeg($im, $savePath);
                break;
            case 3:
                $savePath .= '.png';
                $res = imagepng($im, $savePath);
                break;
        }

        // 销毁
        imagedestroy($im);

        if ($res) {
            return $savePath;
        }

        return false;
    }

    /**
     * 获取字段列表
     * @author:luxu
     **/

    public function get_product_config()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $configs = $this->_modelObj->get_product_config($params);
        $this->sendData($configs);
    }

    /**
     * 获取原因列表
     * @author:luxu
     **/

    public function get_product_reason()
    {
        try{

            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $reasons = $this->_modelObj->get_product_reason($params);
            $this->sendData($reasons);
        }catch ( Exception $exp )
        {
            $this->sendData('没有找到记录');
        }
    }

    /**
     * 添加原因
     * @author:luxu
     **/
    public function add_product_reason()
    {
        try {

            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $reasons = $this->_modelObj->add_product_reason($params);
            $this->sendData($reasons);
        }catch ( Exception $exp )
        {
            $this->sendData($exp->getMessage());
        }
    }

    /**
     * 添加字段原因
     * @author:luxu
     **/
    public function add_reason()
    {
        try{

            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $result = $this->_modelObj->add_reason($params);
            $this->sendData($result);
        }catch ( Exception $exp )
        {
            $this->sendData($exp->getMessage());
        }
    }

    /**
     * 获取产品列表修改原因
     **/
    public function get_reason_list()
    {
        try{

            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $reasons = $this->_modelObj->get_reason_list($params);
            $this->sendData($reasons);
        }catch ( Exception $exp )
        {
            $this->sendData($exp->getMessage());
        }
    }

    /**
     * 获取外箱尺寸的配置信息
     * @author:luxu
     * @time:2020/4/13
     **/

    public function getProductSize(){

        try{

            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $configs = $this->_modelObj->getProductSize($params);
            $this->sendData($configs);
        }catch ( Exception $exp ){

            $this->sendData($exp->getMessage());
        }

    }

    /**
     * 更新外箱尺寸的配置信息
     * @author:luxu
     * @time:2020/4/13
     **/
    public function updateProductSize(){

        try{

            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $reasons = $this->_modelObj->updateProductSize($params);
            $this->sendData($reasons);
        }catch ( Exception $exp )
        {
            $this->sendData($exp->getMessage());
        }

    }

    /**
     * 外箱尺寸的配置信息(产品修改列表)
     * @author:luxu
     * @time:2020/4/13
     **/
    public function getSize(){

        try{

            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $reasons = $this->_modelObj->getSize($params);
            $this->sendData($reasons);
        }catch ( Exception $exp )
        {
            $this->sendData($exp->getMessage());
        }
    }

    /**
     * 历史sku列表
     */
    public function history_product_list(){
            $params = $this->_requestParams;
        $data = $this->_modelObj->get_product_list_history($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);


        }


    /**
     * 历史sku导出
     */
    public function history_product_export(){
        set_time_limit(0);
        $this->load->helper('export_csv');
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
        $data = $this->_modelObj->history_product_export_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
            $this->sendData($data);
        }
        $this->sendData($data);
    }

    public function get_drop_box(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_drop_box($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * @function:商品管理SKU 退税率，修改日志。
     * 采购系统并不修改退税率，数据来自物流系统
     * @author:luxu
     * @time:2020年7月14号
     **/

    public function rateLogs(){

        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->rateLogs($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }


    /**
     * @function:商品管理SKU 开票点修改人在
     * 采购系统是可以修改开票点
     * @author:luxu
     * @time:2020年7月14号
     **/
    public function ticketedPoint(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->ticketedPoint($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);

    }

    public function get_set_table_header(){

        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_set_table_header($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);

    }

    public function save_table_list(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->save_table_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    public function get_headerlog(){


        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_headerlog($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

}