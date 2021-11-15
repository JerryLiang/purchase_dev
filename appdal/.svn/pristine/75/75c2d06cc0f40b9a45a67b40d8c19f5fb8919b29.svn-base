<?php
/**
 * 报损信息控制器
 * User: Jaxton
 * Date: 2019/01/17 10:00
 */

class Report_loss extends MY_Controller{
	public function __construct(){
        parent::__construct();
        $this->load->model('Report_loss_model','report_loss_model');
        $this->load->helper('abnormal');
    }

    /**
    * 获取报损数据列表
    * /abnormal/report_loss/get_report_loss_list
    * @author Jaxton 2019/01/17
    */
    public function get_report_loss_list(){
    	$params=[
            'sku' => $this->input->get_post('sku'),
            'pur_number' => $this->input->get_post('pur_number'),//采购单号
            'bs_number' => $this->input->get_post('bs_number'),//报损编号
            'apply_person' => $this->input->get_post('apply_person'),//申请人
            'status' => $this->input->get_post('status'),
            'apply_time_start' => $this->input->get_post('apply_time_start'),//申请时间开始
            'apply_time_end' => $this->input->get_post('apply_time_end'),//申请时间截止
            'demand_number' => $this->input->get_post('demand_number'),// 备货单号
            'is_abnormal' => $this->input->get_post('is_abnormal'),// 是否异常
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
            'loss_totalprice_min' => $this->input->get_post('loss_totalprice_min'),// 报损金额最小值
            'loss_totalprice_max' => $this->input->get_post('loss_totalprice_max'),// 报损金额最大值
            'responsible_party' => $this->input->get_post('lossResponsibleParty'),// 承担方式
            'relative_superior_number' => $this->input->get_post('relative_superior_number'),// 关联的取消编码
            'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
            'audit_time_start'          => $this->input->get_post('audit_time_start'),
            'audit_time_end'          => $this->input->get_post('audit_time_end'),
            'approval_time_start'          => $this->input->get_post('approval_time_start'),
            'approval_time_end'          => $this->input->get_post('approval_time_end'),




    	];


        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_unique(array_column($groupids,'label'));
            }

            $params['groupdatas'] = $groupdatas;
        }

        $page_data=$this->format_page_data();
        $result=$this->report_loss_model->get_report_loss_list($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $result['data_list']['value']=$this->report_loss_model->formart_report_loss_list($result['data_list']['value']);
        $this->success_json($result['data_list'],$result['paging_data']);
    }

    public function format_page_data(){
        $page = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        $limit         = query_limit_range($limit);
        $offset        = ($page - 1) * $limit;
        return [
            'offset' => $offset,
            'limit' => $limit,
            'page' => $page
        ];
    }

    /**
    * 弹出审核页面
    * /abnormal/report_loss/approval
    * @author Jaxton 2019/01/17
    */
    public function approval(){
    	$id=$this->input->get_post('id');//主键id
    	if(empty($id)) $this->error_json('ID必须');
    	$result=$this->report_loss_model->get_approval_page($id);
        $key_data=['订单号','备货单号','图片','SKU','产品名称','单价','采购数量','入库数量','报损数量','报损运费','报损加工费','报损金额'];
        if($result){
            $this->load->helper('abnormal');

            foreach ($result as $data){
                if ($data['is_abnormal']==1){
                    $this->error_json('为异常单,需联系IT进行处理');
                }
            }

            $lossResponsibleParty = getReportlossResponsibleParty();
            $down_box_list['lossResponsibleParty'] = $lossResponsibleParty;

            $formart_data=$this->report_loss_model->formart_approval_page($result);
            $this->success_json(['value'=>$formart_data,'key'=>$key_data,'down_box_list' => $down_box_list]);
        }else{
            $this->error_json('需求单没获取到数据');
        }
        
    }

    /**
    * 审核提交
    * /abnormal/report_loss/approval_handle
    * @author Jaxton 2019/01/17
    */
    public function approval_handle(){
        $id                      = $this->input->get_post('id');//主键id
        $approval_type           = $this->input->get_post('approval_type');//1通过，2不通过
        $remark                  = $this->input->get_post('remark');//驳回备注
        $responsible_user        = $this->input->get_post('responsible_user');// 责任人
        $responsible_user_number = $this->input->get_post('responsible_user_number');// 责任人工号
        $responsible_party       = $this->input->get_post('responsible_party');// 责任承担方式
        if(empty($id)) $this->error_json('ID必须');
        if(empty($approval_type) || !in_array($approval_type, [1, 2])){
            $this->error_json('审核类型错误');
        }
        if($approval_type == 2 && empty($remark)) $this->error_json('驳回请填写原因');
        $result = $this->report_loss_model->approval_handle($id, $approval_type, $remark,$responsible_user,$responsible_user_number,$responsible_party);
        if($result['success']){
            $this->success_json([], null, '操作成功');
        }else{
            $this->error_json($result['error_msg']);
        }
    }

    /**
    * 导出
    * /abnormal/report_loss/export_report_loss
    * @author Jaxton 2019/01/18
    */
    public function export_report_loss(){
    	$id=$this->input->get_post('ids');
    	if(!empty($id)){
    		$params['id']=explode(',', $id);
    	}else{
    		$params=[
	    		'sku'=>$this->input->get_post('sku'),
	    		'pur_number'=>$this->input->get_post('pur_number'),//采购单号
	    		'apply_person'=>$this->input->get_post('apply_person'),//申请人
	    		'status'=>$this->input->get_post('status'),
	    		'apply_time_start'=>$this->input->get_post('apply_time_start'),//申请时间开始
	    		'apply_time_end'=>$this->input->get_post('apply_time_end'),//申请时间截止
	    	];
    	}
    	$result=$this->report_loss_model->export_report_loss($params);
    }

    /**
    * 获取导出数据
    * /abnormal/report_loss/get_export_report_loss_data
    * @author Jaxton 2019/01/31
    */
    public function get_export_report_loss_data(){
        $id=$this->input->get_post('id');
        if(!empty($id)){
            $params['id']=explode(',', $id);
        }else{
            $params=[
                'sku'=>$this->input->get_post('sku'),
                'pur_number'=>$this->input->get_post('pur_number'),//采购单号
                'bs_number' => $this->input->get_post('bs_number'),//报损编号
                'apply_person'=>$this->input->get_post('apply_person'),//申请人
                'status'=>$this->input->get_post('status'),
                'apply_time_start'=>$this->input->get_post('apply_time_start'),//申请时间开始
                'apply_time_end'=>$this->input->get_post('apply_time_end'),//申请时间截止
                'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
                'loss_totalprice_min' => $this->input->get_post('loss_totalprice_min'),// 报损金额最小值
                'loss_totalprice_max' => $this->input->get_post('loss_totalprice_max'),// 报损金额最大值
                'responsible_party' => $this->input->get_post('lossResponsibleParty'),// 承担方式
                'relative_superior_number' => $this->input->get_post('relative_superior_number'),// 关联的取消编码
                'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
                'approval_time_start'       => $this->input->get_post('approval_time_start'),//财务审核时间开始
                'approval_time_end'       => $this->input->get_post('approval_time_end'),//财务审核时间结束


            ];
        }

        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'label');
            }

            $params['groupdatas'] = $groupdatas;
        }
        $result=$this->report_loss_model->get_report_loss_list($params);
        $result['data_list']['value']=$this->report_loss_model->formart_report_loss_list($result['data_list']['value']);
        if(!empty($result['data_list']['value'])){
            $this->success_json($result['data_list']['value']);
        }else{
            $this->error_json('没有获取到数据');
        }
    }

    /**
    * 获取审核状态
    * /abnormal/report_loss/get_approval_status
    * @author Jaxton 2019/01/21
    */
    public function get_approval_status(){
        $list=getReportlossApprovalStatus();
        $this->success_json($list);
    }

    public function add_data(){
        $this->report_loss_model->add_data();
    }

    /**
     * @desc 编辑报损数据预览
     * @author Jeff
     * @Date 2019/6/25 19:42
     * @return
     */
    public function preview_edit_data()
    {
        $id=$this->input->get_post('id');
        if(empty($id)) $this->error_json('缺失id');
        $result=$this->report_loss_model->get_preview_edit_data($id);

        if ($result['code']){
            $this->success_json($result['data']);
        }else{
            $this->error_json($result['msg']);
        }
    }

    /**
     * @desc 编辑报损
     * @author Jeff
     * @Date 2019/6/26 9:51
     * @return
     */
    public function edit_report_loss()
    {
        $id                      = $this->input->get_post('id');
        $loss_freight            = $this->input->get_post('loss_freight');//报损运费
        $loss_process_cost       = $this->input->get_post('loss_process_cost');//报损加工费
        $loss_price              = $this->input->get_post('loss_price');//报损金额(不含运费)
        $remark                  = $this->input->get_post('remark');//申请备注
        $responsible_user        = $this->input->get_post('responsible_user');// 责任人
        $responsible_user_number = $this->input->get_post('responsible_user_number');// 责任人工号
        $responsible_party       = $this->input->get_post('responsible_party');// 责任承担方式
        if(empty($id)) $this->error_json('缺失id');
        if($loss_freight=='' || $loss_freight<0) $this->error_json('报损运费错误');
        if($loss_process_cost=='' || $loss_process_cost<0) $this->error_json('报损加工费错误');
        if(empty($loss_price) || $loss_price<0) $this->error_json('报损金额错误');

        if (!is_two_decimal($loss_freight)){
            $this->error_json('报损运费小数最多只能为两位');
        }

        if (!is_two_decimal($loss_process_cost)){
            $this->error_json('报损加工费小数最多只能为两位');
        }

        if (!is_two_decimal($loss_price)){
            $this->error_json('报损金额小数最多只能为两位');
        }

        $result=$this->report_loss_model->update_reportloss_data($id,$loss_freight,$loss_process_cost,$loss_price,$remark,$responsible_user,$responsible_user_number,$responsible_party);

        if ($result['code']){
            $this->success_json();
        }else{
            $this->error_json($result['msg']);
        }
    }


    /**
     * 获取报损数据汇总
     * /abnormal/report_loss/get_report_loss_list
     * @author Jaxton 2019/01/17
     */
    public function get_report_loss_list_sum(){
        $params=[
            'sku' => $this->input->get_post('sku'),
            'pur_number' => $this->input->get_post('pur_number'),//采购单号
            'bs_number' => $this->input->get_post('bs_number'),//报损编号
            'apply_person' => $this->input->get_post('apply_person'),//申请人
            'status' => $this->input->get_post('status'),
            'apply_time_start' => $this->input->get_post('apply_time_start'),//申请时间开始
            'apply_time_end' => $this->input->get_post('apply_time_end'),//申请时间截止
            'demand_number' => $this->input->get_post('demand_number'),// 备货单号
            'is_abnormal' => $this->input->get_post('is_abnormal'),// 是否异常
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
            'loss_totalprice_min' => $this->input->get_post('loss_totalprice_min'),// 报损金额最小值
            'loss_totalprice_max' => $this->input->get_post('loss_totalprice_max'),// 报损金额最大值
            'responsible_party' => $this->input->get_post('lossResponsibleParty'),// 承担方式
            'relative_superior_number' => $this->input->get_post('relative_superior_number'),// 关联的取消编码
            'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
        ];


        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }

        $result=$this->report_loss_model->get_report_loss_list_sum($params);
        $this->success_json($result);
    }


    /**
     * 批量审核
     * /abnormal/report_loss/batch_handle
     * @author Jaxton 2019/01/17
     */
    public function batch_approval_handle(){
        $ids                      = $this->input->get_post('ids');//主键id
        $approval_type           = $this->input->get_post('approval_type');//1通过，2不通过
        $remark                  = $this->input->get_post('remark');//备注
        $error_list              = [];//报错信息
      /*  $responsible_user        = $this->input->get_post('responsible_user');// 责任人
        $responsible_user_number = $this->input->get_post('responsible_user_number');// 责任人工号
        $responsible_party       = $this->input->get_post('responsible_party');// 责任承担方式*/
        if(empty($ids)) $this->error_json('ID必须');
        if(empty($approval_type) || !in_array($approval_type, [1, 2])){
            $this->error_json('审核类型错误');
        }
        if($approval_type == 2 && empty($remark)) $this->error_json('驳回请填写原因');
        $ids_arr = explode(',',$ids);
        foreach ($ids_arr as $id) {
            $report_loss_info = $this->report_loss_model->get_one_report_loss($id);
            $result = $this->report_loss_model->approval_handle($id, $approval_type, $remark,$report_loss_info['responsible_user'],$report_loss_info['responsible_user_number'],$report_loss_info['responsible_party']);
            if($result['success']){
            }else{
                $error_list[] = $report_loss_info.['bs_number'].$result['error_msg'];
            }



        }

        if(empty($error_list)){
            $this->success_json([], null, '批量审核成功');
        }else{
            $this->success_json($error_list,null,'批量审核完成，有部分失败');
        }




    }

}