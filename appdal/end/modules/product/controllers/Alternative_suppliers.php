<?php
/**
 * Created by PhpStorm.
 * 备选供应商控制器
 * User: 鲁旭
 * Date: 2020/4/22 0027 11:17
 */

class Alternative_suppliers extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Alternative_suppliers_model');
        $this->load->model('product_line_model');
        $this->load->model('purchase_user_model');
        $this->load->model('supplier/Supplier_settlement_model', 'settlementModel');
        $this->load->helper('status_supplier');

    }

    /**
     * 添加备选供应商接口
     * @METHOD POST
     * @author:luxu
     * @time:2021年4月22号
     **/

    public function add_alternative_supplier(){

        try{

            $alternativeDatas = $this->input->get_post('alternativedatas'); // 备选供应商数据
            $alternativeDatas = json_decode($alternativeDatas,True);

            if(empty($alternativeDatas)){
                throw new Exception("客户端传入数据错误");
            }
            foreach($alternativeDatas as $datas){
                if(is_array($datas['sku']) && !isset($datas['id'])){
                    $skus = $datas['sku'];
                    foreach($skus as $skusdata){
                        $datas['sku'] = $skusdata;
                        $result = $this->Alternative_suppliers_model->add_alternative_supplier($datas);
                    }
                }else{
                    $result = $this->Alternative_suppliers_model->update_alternative_supplier($datas);
                }

            }

            $this->success_json();
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 返回下拉框数据
     * @param 无
     * @author:luxu
     * @time:2021年4月23号
     **/
    private function alternative_boxdata(){

        $product_line_list = $this->product_line_model->get_product_line_list(0);
        $drop_down_box['product_line_id'] =array_column($product_line_list, 'linelist_cn_name','product_line_id');
        $settlement = $this->settlementModel->get_settlement();
        //$down_settlement = array_column($settlement,NULL,'settlement_code');
        return [
            'line_data'       => array_column($product_line_list, 'linelist_cn_name','product_line_id'),// 产品线选项
            'supplier_source' => [1=>'常规',2=>'海外',3=>'临时'], // 供应商来源
            'is_shipping'   =>  [1=>'是',2=>'否'], //是否包邮
            'product_status'  => getProductStatus(), // 产品状态
            'buyer_message'   => $this->Purchase_user_model->get_list(), // 采购员
            'is_purchasing'   =>  [2=>'否',1=>'是'], // 是否代采
            'create_user_name' => $this->purchase_user_model->get_all_user_by_dept_id(1079231,'development_person'), // 开发人员
            // 1正常,2禁用,3删除,4待审,5审核不通过
            'cooper_status' => [1=>'正常',2=>'禁用',3=>'删除',4=>'待审',5=>'审核不通过'], // 供应合作状态
            'supplier_level' =>getSupplierLevel(), // 供应商等级
            'change_type' =>get_enum(NULL),
            'settlement' =>$settlement, // 结算方式
            //变更类型 1：表示新增加,2表示 修改
            'change_type' => [1=>'新增',2=>'修改']
        ];
    }

    public function get_alternative_boxdata(){

        $result = $this->alternative_boxdata();
        $this->success_json($result);
    }

    /**
     * 获取备选供应商接口
     * @METHOD GET
     * @author:luxu
     * @time:2021年4月22号
     **/
    public function get_alternative_supplier(){
        try{
           $clientDatas = []; // 查询数据缓冲区
           foreach($_GET as $key=>$value){
               $datas = $this->input->get_post($key);
               if(!empty($datas)){
                   $clientDatas[$key] = $datas;
               }
           }
           $clientDatas['limit'] = isset($clientDatas['limit'])?$clientDatas['limit']:20;
           $clientDatas['offset'] = isset($clientDatas['offset'])?$clientDatas['offset']:1;
           $clientDatas['offset'] = ($clientDatas['offset'] - 1) * $clientDatas['limit'];
           $result = $this->Alternative_suppliers_model->get_alternative_supplier($clientDatas);
          // $result['drop_down_box'] = $this->alternative_boxdata();
           $this->success_json($result);
       }catch ( Exception $exp ){
           $this->error_json($exp->getMessage());
       }
    }

    /**
     * 修改SKU 备选供应商信息
     * @METHOD POST
     * @author:luxu
     * @time:2021年4月23号
     **/

    public function save_alternative_supplier(){
        try{
            $clientDatas = []; // 查询数据缓冲区
            foreach($_POST as $key=>$value){
                $datas = $this->input->get_post($key);
                if(!empty($datas)){
                    $clientDatas[$key] = $datas;
                }
            }
            if(empty($clientDatas)){
                throw new Exception("请传入参数");
            }
            $datas = json_decode($clientDatas['datas'],true);
            // 判断SKU+供应商是否存在,如果存在会抛出异常
            $alternativeDatas = $this->Alternative_suppliers_model->get_alternative_datas($datas);
            // 开始修改备选供应商数据
            $result = $this->Alternative_suppliers_model->save_alternative_supplier($datas);
            if(!$result){
                throw new Exception("修改失败");
            }
            $this->success_json();
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取备选供应商审核数据接口，33100 备选供应商列表新增子页面"备选供应商待审核"
     * 新增子页面"备选供应商待审核"
        1.备选供应商列表,新增子页面"备选供应商待审核",显示字段和筛选项如图,
        2.勾选数据进行审核,审核通过或驳回的,页面都不再显示,将相关信息记录到供货关系的变更日志中
        3.审核通过时,备选供应商列表的数据同步更新
        4.审核驳回的,通过消息中心,弹窗通知申请人,弹窗信息:"备选供应商列表,SKU****供应商******审核被驳回,驳回原因 *******"
     * @author:luxu
     * @time:2021年4月24号
     **/
    public function alternative_supplier_examine(){

        try{

            $clientDatas = []; // 查询数据缓冲区
            foreach($_GET as $key=>$value){
                $datas = $this->input->get_post($key);
                if(!empty($datas)){
                    $clientDatas[$key] = $datas;
                }
            }
            $clientDatas['limit'] = isset($clientDatas['limit'])?$clientDatas['limit']:20;
            $clientDatas['offset'] = isset($clientDatas['offset'])?$clientDatas['offset']:1;
            $clientDatas['offset'] = ($clientDatas['offset'] - 1) * $clientDatas['limit'];
            $result = $this->Alternative_suppliers_model->alternative_supplier_examine($clientDatas);
            $result['drop_down_box'] = $this->alternative_boxdata();
            $this->success_json($result);
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 备选供应商审核
        3.审核通过时,备选供应商列表的数据同步更新
        4.审核驳回的,通过消息中心,弹窗通知申请人,弹窗信息:"备选供应商列表,SKU****供应商******审核被驳回,驳回原因 *******"
     * @author:luxu
     * @time:2021年4月24号
     **/

    public function audit_alternative_supplier(){

        try{
            $clientDatas = []; // 查询数据缓冲区
            foreach($_POST as $key=>$value){
                $datas = $this->input->get_post($key);
                if(!empty($datas)){
                    $clientDatas[$key] = $datas;
                }
            }
            if($clientDatas['audit_status'] == 2 && empty($clientDatas['remark'])){
                throw new Exception("驳回请填写备注");
            }
            $result = $this->Alternative_suppliers_model->audit_alternative_supplier($clientDatas);
            if($result){
                $this->success_json();
            }
            throw new Exception("审核失败");
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 备选供应商日志获取
     * @method:get
     * @author:luxu
     * @time:2021年4月27号
     **/
    public function get_alternative_log(){
        $skus = $this->input->get_post('sku');
        if(empty($skus)){
            $this->error_json("请传入SKU");
        }

        $logDatas = $this->Alternative_suppliers_model->get_alternative_log($skus);

        // 获取SKU 换绑数据
       // $productLogs = $this->Alternative_suppliers_model->get_sku_product_update($skus);
        $this->success_json($logDatas);
    }

    /**
      * 备选供应商导出
     **/
    public function alternative_import(){
        try{
            $this->load->model('system/Data_control_config_model');
            $clientDatas = []; // 查询数据缓冲区
            foreach($_GET as $key=>$value){
                $datas = $this->input->get_post($key);
                if(!empty($datas)){
                    $clientDatas[$key] = $datas;
                }
            }

            $clientDatas['limit'] = 1;
            $clientDatas['offset'] = 1;
            $clientDatas['offset'] = ($clientDatas['offset'] - 1) * $clientDatas['limit'];
            $result = $this->Alternative_suppliers_model->get_alternative_supplier($clientDatas);
            $total = $result['page']['total'];
            if($total>=150000){
                $this->error_json("导出数据不能超过15万");
            }
            try {
                $result = $this->Data_control_config_model->insertDownData($clientDatas, 'ALTERNATIVE', '备选供应商', getActiveUserName(), 'csv', $total);
            } catch (Exception $exp) {
                $this->error_json($exp->getMessage());
            }
            if ($result) {
                $this->success_json("添加到下载中心");
            } else {
                $this->error_json("添加到下载中心失败");
            }
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }

    }
}