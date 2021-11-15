<?php

/**
 * 线下收款列表
 * Class Offline_receipt_model
 * @author Jolon
 * @date 2021-01-12 14:50:01
 */

class Offline_receipt_model extends Purchase_model {
    
    protected $table_name = 'offline_receipt';

    public $refund_status_list = [// 退款状态
        '1' => '待财务收款',
        '2' => '已收款',
        '3' => '财务驳回',
        '4' => '已作废'
    ];

    public function __construct() {
        parent::__construct();
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
    }

    /**
     * 获取指定的一条记录
     * @param $id
     * @return array
     */
    public function get_offline_receipt_one($id){
        $receiptInfo = $this->purchase_db->where('id',$id)
            ->get($this->table_name)
            ->row_array();

        return $receiptInfo;
    }


    /**
     * 获取 线下收款列表 数据列表
     * @param $params
     * @param $offsets
     * @param $limit
     * @param $page
     * @return array
     */
    public function get_offline_receipt_list($params,$offsets, $limit,$page){
        $this->load->model('finance/Offline_refund_model');
        $this->load->model('system/Offline_reason_model');

        // 优先查询数据筛选条件

        $search_user_ids = [];
        if( isset($params['groupname']) && !empty($params['groupname'])){
            $this->load->model('user/User_group_model', 'User_group_model');

            if(is_array($params['groupname'])){
                foreach($params['groupname'] as $groupname){
                    $groupids = $this->User_group_model->getGroupPersonData($groupname);
                    if(!empty($groupids)){
                        $search_user_ids = array_merge($search_user_ids,array_column($groupids,'value'));
                    }
                }
            }else{
                $groupids = $this->User_group_model->getGroupPersonData($params['groupname']);
                if(!empty($groupids)){
                    $search_user_ids = array_column($groupids,'value');
                }
            }
        }

        // 查询条件设置
        $this->purchase_db->from($this->table_name .' AS A');

        if(isset($params['ids']) and !empty($params['ids'])){
            $ids_list = array_filter(explode(',',$params['ids']));
            $this->purchase_db->where_in('id',$ids_list);
        }
        if(isset($params['refund_number']) and !empty($params['refund_number'])){
            $this->purchase_db->where('refund_number',$params['refund_number']);
        }
        if(isset($params['refund_log_number']) and !empty($params['refund_log_number'])){
            $this->purchase_db->where('refund_log_number',$params['refund_log_number']);
        }
        if(isset($params['purchase_number']) and !empty($params['purchase_number'])){
            if(stripos($params['purchase_number'],' ') !== false){
                $purchase_numbers = query_string_to_array($params['purchase_number']);
                $this->purchase_db->group_start();
                foreach ($purchase_numbers as $purchase_number_v){
                    $this->purchase_db->or_like('purchase_number_multi',$purchase_number_v);
                }
                $this->purchase_db->group_end();

            }else{
                $this->purchase_db->like('purchase_number_multi',$params['purchase_number']);
            }
        }
        if(isset($params['apply_user_id']) and !empty($params['apply_user_id'])){
            if(is_array($params['apply_user_id'])){
                $this->purchase_db->where_in('apply_user_id',$params['apply_user_id']);
            }else{
                $this->purchase_db->where('apply_user_id',$params['apply_user_id']);
            }
        }
        if(isset($params['apply_time_start']) and !empty($params['apply_time_start'])){
            $this->purchase_db->where('apply_time>=',$params['apply_time_start']);
        }
        if(isset($params['apply_time_end']) and !empty($params['apply_time_end'])){
            $this->purchase_db->where('apply_time<=',$params['apply_time_end']);
        }
        if(isset($params['refund_channel']) and !empty($params['refund_channel'])){
            if(is_array($params['refund_channel'])){
                $this->purchase_db->where_in('refund_channel',$params['refund_channel']);
            }else{
                $this->purchase_db->where('refund_channel',$params['refund_channel']);
            }
        }
        if(isset($params['refund_status']) and !empty($params['refund_status'])){
            if(is_array($params['refund_status'])){
                $this->purchase_db->where_in('refund_status',$params['refund_status']);
            }else{
                $this->purchase_db->where('refund_status',$params['refund_status']);
            }
        }
        if(isset($params['refund_water_number']) and !empty($params['refund_water_number'])){
            $refund_water_number_list = array_filter(explode(' ',$params['refund_water_number']));
            $this->purchase_db->where_in('refund_water_number',$refund_water_number_list);
        }
        if(isset($params['refund_reason']) and !empty($params['refund_reason'])){
            if(is_array($params['refund_reason'])){
                $this->purchase_db->where_in('refund_reason',$params['refund_reason']);
            }else{
                $this->purchase_db->where('refund_reason',$params['refund_reason']);
            }
        }
        if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
            if(is_array($params['supplier_code'])){
                $this->purchase_db->where_in('supplier_code',$params['supplier_code']);
            }else{
                $this->purchase_db->where('supplier_code',$params['supplier_code']);
            }
        }
        if(isset($params['refund_price_start']) and !empty($params['refund_price_start'])){
            $this->purchase_db->where('refund_price>=',$params['refund_price_start']);
        }
        if(isset($params['refund_price_end']) and !empty($params['refund_price_end'])){
            $this->purchase_db->where('refund_price<=',$params['refund_price_end']);
        }
        if(isset($params['receipt_user_id']) and !empty($params['receipt_user_id'])){
            if(is_array($params['receipt_user_id'])){
                $this->purchase_db->where_in('receipt_user_id',$params['receipt_user_id']);
            }else{
                $this->purchase_db->where('receipt_user_id',$params['receipt_user_id']);
            }
        }
        if(isset($params['refund_time_start']) and !empty($params['refund_time_start'])){
            $this->purchase_db->where('refund_time>=',$params['refund_time_start']);
        }
        if(isset($params['refund_time_end']) and !empty($params['refund_time_end'])){
            $this->purchase_db->where('refund_time<=',$params['refund_time_end']);
        }
        if(isset($params['refund_type']) and !empty($params['refund_type'])){
            $this->purchase_db->where('refund_type',$params['refund_type']);
        }
        if(isset($params['compact_number']) and !empty($params['compact_number'])){
            $compact_number_list = array_filter(explode(' ',$params['compact_number']));// 多个拆分后模糊查询
            $this->purchase_db->group_start();
            foreach ($compact_number_list as $compact_number_val){
                $this->purchase_db->or_like('compact_number_multi',$compact_number_val);
            }
            $this->purchase_db->group_end();
        }
        if(isset($params['statement_number']) and !empty($params['statement_number'])){
            $statement_number_list = array_filter(explode(' ',$params['statement_number']));// 多个拆分后精确查询
            $this->purchase_db->where_in('statement_number',$statement_number_list);
        }
        if(isset($params['receipt_time_start']) and !empty($params['receipt_time_start'])){
            $this->purchase_db->where('receipt_time>=',$params['receipt_time_start']);
        }
        if(isset($params['receipt_time_end']) and !empty($params['receipt_time_end'])){
            $this->purchase_db->where('receipt_time<=',$params['receipt_time_end']);
        }
        if(isset($params['receipt_account_short']) and !empty($params['receipt_account_short'])){
            $this->purchase_db->where('receipt_account_short',$params['receipt_account_short']);
        }
        if(isset($params['receipt_account_number']) and !empty($params['receipt_account_number'])){
            $receipt_account_number_list = array_filter(explode(' ',$params['receipt_account_number']));// 多个拆分后精确查询
            $this->purchase_db->where_in('receipt_account_number',$receipt_account_number_list);
        }
        if( isset($params['groupname']) && !empty($params['groupname'])){
            $this->purchase_db->where_in('apply_user_id',$search_user_ids);
        }

        $count_db_for_count = clone $this->purchase_db;
        $count_db_for_current_page = clone $this->purchase_db;
        $count_db_for_all_page = clone $this->purchase_db;

        $results_data = $this->purchase_db->select('*')
            ->offset($offsets)
            ->limit($limit)
            ->order_by('A.id DESC')
            ->get()
            ->result_array();

        $count_list_for_count = $count_db_for_count->select('COUNT(1) AS total')
            ->get()->row_array();
        $count_list_for_current_page = [
            'supplier_code_count' => [],
            'refund_price_total' => 0,
            'receipted_price_total' => 0
        ];
        $count_list_for_all_page = $count_db_for_all_page->select('COUNT(1) AS total,COUNT(DISTINCT A.supplier_code) AS supplier_code_count,SUM(A.refund_price) AS refund_price_total,SUM(A.receipted_price) AS receipted_price_total')
            ->where('refund_status !=',4)
            ->get()->row_array();

        if($results_data){// 获取物流信息
            $logistics_info = $this->Offline_refund_model->get_logistics_info(array_column($results_data,'refund_log_number'),'cargo_company_id,status,express_no');

            if(!empty($logistics_info)){
                $logistics_info_data = array_column($logistics_info,NULL,"express_no");
            }
        }

        $purchase_name_list = get_purchase_agent();
        $payment_platform_all = get_supplier_payment_platform_all();
        foreach($results_data as &$value){
            $value['refund_status_cn'] = isset($this->refund_status_list[$value['refund_status']])?$this->refund_status_list[$value['refund_status']]:'-';
            $value['purchase_name_cn'] = isset($purchase_name_list[$value['purchase_name']])?$purchase_name_list[$value['purchase_name']]:'-';
            $value['refund_channel_cn'] = isset($payment_platform_all[$value['refund_channel']])?$payment_platform_all[$value['refund_channel']]:'-';

            $value['cargo_company_name'] = isset($logistics_info_data[$value['refund_log_number']])?$logistics_info_data[$value['refund_log_number']]['cargo_company_id']:'-';
            $value['status'] = (isset($logistics_info_data[$value['refund_log_number']]) and !empty($logistics_info_data[$value['refund_log_number']]))?getTrackStatus($logistics_info_data[$value['refund_log_number']]['status']):'-';
            $value['receipt_time'] = ($value['receipt_time']!='0000-00-00 00:00:00')?$value['receipt_time']:'-';
            $value['purchase_number_multi'] = !empty($value['purchase_number_multi'])?$value['purchase_number_multi']:'-';
            $value['compact_number_multi'] = !empty($value['compact_number_multi'])?$value['compact_number_multi']:'-';
            $value['receipt_account_short'] = !empty($value['receipt_account_short'])?$value['receipt_account_short']:'-';
            $value['receipt_account_number'] = !empty($value['receipt_account_number'])?$value['receipt_account_number']:'-';
            $value['pai_number'] = !empty($value['pai_number'])?$value['pai_number']:'-';
            $value['abnormal_number'] = !empty($value['abnormal_number'])?$value['abnormal_number']:'-';
            $value['refund_water_number'] = !empty($value['refund_water_number'])?$value['refund_water_number']:'-';
            $value['receipt_user_name'] = !empty($value['receipt_user_name'])?$value['receipt_user_name']:'-';
            $value['receipt_notice'] = !empty($value['receipt_notice'])?$value['receipt_notice']:'-';
            $value['refund_log_number'] = !empty($value['refund_log_number'])?$value['refund_log_number']:'-';
            $value['statement_number'] = !empty($value['statement_number'])?$value['statement_number']:'-';
            $value['apply_notice'] = !empty($value['apply_notice'])?$value['apply_notice']:'-';
            $value['refund_water_append'] = $value['refund_water_append'] == '-'?'':$value['refund_water_append'];
            
            if($value['refund_status'] != 4){// 当前页不统计 已作废的
                $count_list_for_current_page['supplier_code_count'][] = $value['supplier_code'];
                $count_list_for_current_page['refund_price_total'] += $value['refund_price'];
                $count_list_for_current_page['receipted_price_total'] += $value['receipted_price'];
            }
        }

        $count_list_for_current_page['supplier_code_count'] = count(array_unique($count_list_for_current_page['supplier_code_count']));

        $total_count = $count_list_for_count['total'];

        $refund_reason_list = $this->Offline_reason_model->get_refund_reason_list();
        $refund_type_list = $this->Offline_reason_model->get_refund_type_list();


        $return_data = [
            'values' => $results_data,
            'paging_data' => [
                'page_total' => count($results_data),
                'total' => $total_count,
                'offset' => $page,
                'limit' => $limit,
                'pages' => ceil($total_count / $limit),
            ],
            'aggregate_data'=>[
                'current_sp_count' => $count_list_for_current_page['supplier_code_count'],
                'current_refund_price' => format_two_point_price($count_list_for_current_page['refund_price_total']),
                'current_receipted_price' => format_two_point_price($count_list_for_current_page['receipted_price_total']),
                'all_sp_count' => $count_list_for_all_page['supplier_code_count'],
                'all_refund_price' => format_two_point_price($count_list_for_all_page['refund_price_total']),
                'all_receipted_price' => format_two_point_price($count_list_for_all_page['receipted_price_total']),
                'page_total' => count($results_data),
                'total' => $total_count
            ],
            'drop_down_box' => [
                'refund_status' => $this->refund_status_list,
                'refund_reason_list' => $refund_reason_list,
                'refund_type_list' => $refund_type_list
            ],
        ];

        return $return_data;

    }


    /**
     * 确认待收款 驳回/确认到账
     * @param int $id
     * @param int $confirm_status 确认状态
     * @param string $receipt_notice 收款备注
     * @param string $receipt_account_short  账号简称
     * @return array
     */
    public function confirm_receipted($id,$confirm_status,$receipt_notice,$receipt_account_short){

        $receiptInfo = $this->get_offline_receipt_one($id);
        if(empty($receiptInfo)){
            return $this->res_data(false,'目标ID对应的记录不存在');
        }

        if($receiptInfo['refund_status'] != 1){
            return $this->res_data(false,'只有状态为:待财务收款状态时,才能操作待收款');
        }

        if($receiptInfo['apply_user_id'] == getActiveUserId()){
            return $this->res_data(false,'收款人与申请人一致,不允许提交');
        }

        if($confirm_status != 2 and $confirm_status != 3){
            return $this->res_data(false,'操作状态是非法的');
        }

        $this->load->model('system/Bank_card_model', 'bankCart');
        $this->load->model('finance/Offline_refund_model');
        if($confirm_status == 2){
            $bankInfo = $this->bankCart->findOne(['account_short' => $receipt_account_short, 'status' => 1]);
            if(empty($bankInfo)){
                return $this->res_data(false,'银行账号获取失败');
            }
            $this->Offline_refund_model->add_log_data('收款备注'.$receipt_notice,"已收款",$id);

            $update_arr = [
                'refund_status' => $confirm_status,
                'receipt_user_id' => getActiveUserId(),
                'receipt_user_name' => getActiveUserName(),
                'receipt_time' => date('Y-m-d H:i:s'),
                'receipt_notice' => $receipt_notice,
                'receipted_price' => $receiptInfo['refund_price'],
                'receipt_account_short' => $receipt_account_short,
                'receipt_account_number' => isset($bankInfo['account_number'])?$bankInfo['account_number']:''
            ];
            $result = $this->purchase_db->where('id',$id)->update($this->table_name,$update_arr);

        }else{
            $this->Offline_refund_model->add_log_data('驳回备注'.$receipt_notice,"财务驳回",$id);

            $update_arr = [
                'refund_status' => $confirm_status
            ];
            $result = $this->purchase_db->where('id',$id)->update($this->table_name,$update_arr);

        }

        if($result){
            return $this->res_data(true,'更新成功');
        }else{
            return $this->res_data(false,'更新失败');
        }

    }

    /**
     * 查看 收款详情日志
     * @param $id
     * @return array
     */
    public function get_receipt_logs($id){
        $list = $this->purchase_db->select('content_detail')
            ->where('record_number',$id)
            ->where('record_type','OFFLINE_RECEIPT')
            ->order_by('id ASC')
            ->get('operator_log')
            ->result_array();

        $show_list = [];
        if($list){
            foreach($list as $value){
                $show_list[] = json_decode($value['content_detail'],true);

            }
        }

        return $show_list;
    }

    /**
     * 更新 收款详情
     * @param int $id
     * @param string $receipt_notice 收款备注
     * @param string $receipt_account_short  账号简称
     * @return array
     */
    public function update_receipt_details($id,$receipt_notice,$receipt_account_short){

        $receiptInfo = $this->get_offline_receipt_one($id);
        if(empty($receiptInfo)){
            return $this->res_data(false,'目标ID对应的记录不存在');
        }

        if($receiptInfo['refund_status'] != 2){
            return $this->res_data(false,'只有状态为:已收款状态时,才能编辑详情');
        }

        $this->load->model('system/Bank_card_model', 'bankCart');
        $oldBankInfo = $this->bankCart->findOne(['account_short' => $receiptInfo['receipt_account_short']]);
        $bankInfo = $this->bankCart->findOne(['account_short' => $receipt_account_short, 'status' => 1]);
        if(empty($bankInfo)){
            return $this->res_data(false,'银行账号获取失败');
        }

        $update_arr = [// 更新 收款详情 只能更新 收款账号和收款备注 2个信息
            'receipt_notice' => $receipt_notice,
            'receipt_account_short' => $receipt_account_short,
            'receipt_account_number' => isset($bankInfo['account_number'])?$bankInfo['account_number']:''
        ];

        // 保存修改前信息日志
        operatorLogInsert([
            'id' => $receiptInfo['id'],
            'type' => $this->table_name,
            'content' => 'm_confirm_info',
            'detail' => [
                'receipt_account_short' => $receiptInfo['receipt_account_short'],
                'receipt_account_number' => $receiptInfo['receipt_account_number'],
                'receipt_account_holder' => $oldBankInfo['account_holder'],
                'receipt_notice' => $receiptInfo['receipt_notice'],
                'update_user_name' => getActiveUserName(),
                'update_time' => date('Y-m-d H:i:s'),
            ]
        ]);

        $this->load->model('finance/Offline_refund_model');
        $this->Offline_refund_model->add_log_data('详情备注'.$receipt_notice,"修改收款账号",$id);

        $result = $this->purchase_db->where('id',$id)->update($this->table_name,$update_arr);
        if($result){
            return $this->res_data(true,'更新成功');
        }else{
            return $this->res_data(false,'更新失败');
        }

    }

}