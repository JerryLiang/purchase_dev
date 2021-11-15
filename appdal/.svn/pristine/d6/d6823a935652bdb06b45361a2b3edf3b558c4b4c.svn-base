<?php

/**
 * Created by PhpStorm.
 * 采购需求控制器
 * User: Jackson
 * Date: 2018/12/27 0027 11:17
 */
class Supplier_address extends MY_Controller
{

    protected $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Supplier_address_model');
        $this->_modelObj = $this->Supplier_address_model;
    }

    /**
     * @desc 获取产品线
     * @author Jackson
     * @Date 2019-01-21 17:01:00
     **/
    public function get_address()
    {
        $params = gp();
        if (isset($params['region_type']) && !is_numeric($params['region_type'])) {
            $this->send_data(NULL, '查询类型不是数字', false);
        }
        $params = gp();
        $level = isset($params['region_type']) ? $params['region_type'] : REGION_TYPE_PROVINCE;
        $params['pid'] = isset($params['pid']) && $params['pid'] ? $params['pid'] : 1;

        $levels=array(1=>'province',2=>'city',3=>'area');

        $data[$levels[$level]] = $this->_modelObj->get_address_by_Level($level, $params);//产品线级别
        $this->send_data(['list' => $data], '城市-省份-区县列表', true);

    }

    /**
     * 根据 获取 对应省、市、区 名称
     */
    public function get_address_list(){
        $region_type = $this->input->get_post('region_type');
        $pid  = $this->input->get_post('pid');
        if(empty($pid)){
            $this->error_json("父级编码不允许为空");
        }
        if(empty($region_type)){
            $region_type=1;
        }
        $data = $this->_modelObj-> get_address_list($region_type,$pid);

        if(count($data)>0){
            $this->success_json($data);
        }else{
            $this->success_json($data);
        }
    }

    /**
     * 下拉列表
     * @author Manson
     */
    public function get_drop_down_list()
    {
        $this->load->helper('status_order');
        //返回下拉
        $drop_down_box['purchase_type_id'] = getPurchaseType();
        $data['drop_down_box'] = $drop_down_box;
        $this->success_json($data);
    }

}