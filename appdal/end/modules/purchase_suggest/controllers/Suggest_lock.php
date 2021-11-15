<?php
/**
 * Created by PhpStorm.
 * User: Jeff
 * Date: 2019/5/5
 * Time: 11:59
 */
class Suggest_lock extends MY_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->helper('status_order');
        $this->load->model('Suggest_lock_model');
    }

    /**
     * @desc 获取锁单配置列表
     * @author Jeff
     * @Date 2019/5/5 11:52
     * @param $params
     * @return
     */
    public function get_list(){
        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        $limit         = query_limit_range($limit);
        $offset        = ($page - 1) * $limit;

        $result=$this->Suggest_lock_model->get_list($offset, $limit, $page);
        $key_arr = ['序号','操作人','操作时间','锁单时间','sku持续未降价最小值(天)','采购单金额最小值_国内仓/FBA(万)'
            ,'采购单金额最小值_海外仓(万)'];
        if (!empty($result['list'])){
            foreach ($result['list'] as &$value){

                $start_time = explode(' ', $value['lock_time_start']);
                $start_time = $start_time[1];
                $end_time   = explode(' ', $value['lock_time_end']);
                $end_time   = $end_time[1];

                $value['lock_time'] = $start_time.'-'.$end_time;
                unset($value['lock_time_start']);
                unset($value['lock_time_end']);
            }
        }
        $this->success_json(['key'=>$key_arr,'values'=>$result['list'],'page_data'=>$result['page_data']]);
    }

    /**
     * @desc 生成锁单配置
     * @author Jeff
     * @Date 2019/5/5 13:38
     * @param $id
     * @param $expiration
     * @return
     */
    public function create_lock()
    {
        $lock_time_start=$this->input->get_post('lock_time_start');//锁单开始时间
        $lock_time_end=$this->input->get_post('lock_time_end');//锁单开始时间
        $not_reduce_day=$this->input->get_post('not_reduce_day');//sku未持续降价最小值(天)
        $purchase_total_fba_inside=$this->input->get_post('purchase_total_fba_inside');//采购总金额最小值(FBA/国内仓)万
        $purchase_total_over_sea=$this->input->get_post('purchase_total_over_sea');//采购总金额最小值(海外仓)万

        if (empty($lock_time_start)||empty($lock_time_end)||empty($not_reduce_day)||empty($purchase_total_fba_inside)
            ||empty($purchase_total_over_sea)){
            $this->error_json('必填参数为空');
        }

        if ($not_reduce_day>90) $this->error_json('不能超过90天');
        if ($purchase_total_fba_inside<0) $this->error_json('金额需大于0');
        if ($purchase_total_over_sea<0) $this->error_json('金额需大于0');

        //限制设置锁单时间
        $start_time_hour = explode(':',$lock_time_start);
        $start_time_hour = isset($start_time_hour[0])?$start_time_hour[0]:'';
        if (empty($start_time_hour)){
            $this->error_json('锁单开始时间错误');
        }

        $end_time_hour = explode(':',$lock_time_end);
        $end_time_hour = isset($end_time_hour[0])?$end_time_hour[0]:'';
        if (empty($end_time_hour)){
            $this->error_json('锁单结束时间错误');
        }


        if ( !(18<=(int)$start_time_hour&& (int)$start_time_hour<24) ){
            $this->error_json('锁单开始时间范围为18-23时');
        }

        if ( !(0<=(int)$end_time_hour&& (int)$end_time_hour<12) ){
            $this->error_json('锁单结束时间范围为0-12时');
        }

        $result=$this->Suggest_lock_model->create_lock($lock_time_start, $lock_time_end, $not_reduce_day, $purchase_total_fba_inside, $purchase_total_over_sea);
        if($result['code']){
            $this->success_json([],null,'操作成功');
        }else{
            $this->error_json($result['msg']);
        }
    }
}
