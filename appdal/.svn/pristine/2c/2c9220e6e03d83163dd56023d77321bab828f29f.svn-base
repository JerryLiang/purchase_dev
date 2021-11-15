<?php
/**
 * Created by PhpStorm.
 * User: Jeff
 * Date: 2019/5/5
 * Time: 11:59
 */
class Suggest_expiration_set extends MY_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->helper('status_order');
        $this->load->model('suggest_expiration_set_model');
    }

    /**
     * @desc 获取过期时间配置列表
     * @author Jeff
     * @Date 2019/5/5 11:52
     * @param $params
     * @return
     */
    public function get_list(){
        $result=$this->suggest_expiration_set_model->get_list();
        $key_arr = ['序号','业务线','过期时间（天）','创建人/创建时间','更新人/更新时间','SKU','备注','操作'];
        if (!empty($result)){
            foreach ($result as &$value){
                $value['purchase_type_id'] = getPurchaseType($value['purchase_type_id']);
            }
        }

        $this->success_json(['key'=>$key_arr,'values'=>$result]);
    }

    /**
     * @desc 编辑过期时间
     * @author Jeff
     * @Date 2019/5/5 13:38
     * @param $id
     * @param $expiration
     * @return
     */
    public function edit_expiration()
    {
        $id=$this->input->get_post('id');//id
        $expiration=$this->input->get_post('expiration');//过期时间
        $remark=$this->input->get_post('remark');//备注

        if (empty($id)||empty($expiration)||empty($remark)){
            $this->error_json('必填参数为空');
        }

        $result=$this->suggest_expiration_set_model->edit_expiration($id, $expiration, $remark);
        if($result['code']){
            $this->success_json([],null,'操作成功');
        }else{
            $this->error_json($result['msg']);
        }
    }
}
