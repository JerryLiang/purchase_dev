<?php
/**
 * 预测计划单反馈模型类
 * User: Jaxton
 * Date: 2019/01/04 14:23
 */

class Forecast_feedback_model extends Purchase_model {

    protected $feedback_table = 'forecast_feedback';//反馈表

    public function __construct(){
        parent::__construct();

        $this->load->helper('user');
    }
    /**
    * 添加反馈
    * @param $feedback_str
    * @param $suggest_id
    * @return bool   
    * @author Jaxton 2019-1-4
    */
    public function add_feedback($feedback_str,$suggest_id){
        //$user_info=getActiveUserInfo();
        $this->load->model('purchase_suggest_model');
        //获取预测单信息
        $order_info=$this->purchase_suggest_model->get_one($suggest_id);
        $add_data=[
            'feedback_str'=>$feedback_str,
            'suggest_id'=>$suggest_id,
            'user_id'=>getActiveUserId(),//$user_info['user_id'],
            'user_name'=>getActiveUserName(),//$user_info['user_name'],
            'create_time'=>date('Y-m-d H:i:s'),
            'suggest_no'=>$order_info['demand_number'],
            'sku'=>$order_info['sku'],
            'warehouse_code'=>$order_info['warehouse_code']
        ];
        $this->purchase_db->trans_begin();
        try{
            //插入反馈表
            $this->purchase_db->insert($this->feedback_table,$add_data);
            //插入操作记录表
            $this->load->model('reject_note_model');
            $log_data=[
                'record_number'=>$suggest_id,
                'record_type'=>'预测计划单反馈',
                'content'=>'预测计划单反馈',
                'content_detail'=>$feedback_str
            ];
            $this->reject_note_model->get_insert_log($log_data);
            //推送到第三方
            
            $this->purchase_db->trans_commit();
            $result=true;
            
        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $result=false;
        }
        
        return $result;
    }

    /**
    * 获取反馈记录
    * @param $suggest_id
    * @return array   
    * @author Jaxton 2019-1-4
    */
    public function get_feedback_list($suggest_id){
        $list=$this->purchase_db->select('user_id,user_name,feedback_str,create_time')
        ->from($this->feedback_table)
        ->where('suggest_id',$suggest_id)
        ->order_by('create_time','DESC')
        ->get()
        ->result_array();
        return $list;
    }

    /**
    * 反馈结果推送
    * @param $data
    * @return array   
    * @author Jaxton 2019-1-5
    */
    public function push_feedback($data){
        // $this->load->model('purchase_suggest_model');
        // //获取预测单信息
        // $order_info=$this->purchase_suggest_model->get_one($suggest_id);
        // $result=[];
        // if($order_info){
        $result=[
            'sku' => $data['sku'],
            'suggest_no' => $data['demand_number'],
            'user_id' => $data['user_id'],
            'user_name' => $data['user_name'],
            'create_time'  => $data['create_time'],
            'feedback_str' => $data['feedback_str']
        ];
        // }
        //调第三方接口
        if(xxxxx($result)){
            return true;
        }else{
            return false;
        }
        //return $result;

    }
}