<?php
/**
 * 余额调整单
 * User: Yibai
 * Date: 2020-06-04
 * Time: 9:41
 */

class Supplier_balance_order_model extends Purchase_model {

    public $table_name = 'supplier_balance_order';

    public $audit_status_list = [// 审核状态
        '1' => '待财务经理审核',
        '2' => '审核通过',
        '3' => '审核驳回'
    ];

    public function __construct(){
        parent::__construct();
    }

    /**
     * head头
     * @return array
     */
    public function get_head_list(){
        $head = ['申请ID', '供应商', '采购主体', '发生时间', '调整金额', '申请信息', '申请备注', '审核信息', '审核备注', '审核状态', '操作'];

        return $head;
    }

    /**
     * 余额调整单列表
     * @param $params
     * @param $offsets
     * @param $limit
     * @param $page
     * @param $action
     * @return array|int
     */
    public function balance_order_list($params, $offsets, $limit, $page,$action = 'query' ){
        $query = $this->purchase_db;

        $query->select('id,order_no,purchase_name,supplier_code,adjust_money,occurrence_time,audit_status,create_time,create_user_name,audit_time,audit_user_name,create_note,audit_note');
        $query->from($this->table_name);

        //主体
        if(isset($params['purchase_name']) and $params['purchase_name'] != ''){
            $query->where('purchase_name', $params['purchase_name']);
        }
        //供应商编码
        if(isset($params['supplier_code']) and $params['supplier_code'] != ''){
            $query->where('supplier_code', $params['supplier_code']);
        }
        //供应商编码
        if(isset($params['audit_status']) and $params['audit_status'] != ''){
            $query->where('audit_status', $params['audit_status']);
        }
        //id
        if(isset($params['id']) and $params['id'] != ''){
            $query->where('id', $params['id']);
        }
        //发生时间
        if(isset($params['start_create_time']) and isset($params['end_create_time'])
            and !empty($params['end_create_time']) and !empty($params['start_create_time']) and $params['start_create_time'] != '0000-00-00'){
            $start_time = date('Y-m-d', strtotime($params['start_create_time']));
            $end_time   = date('Y-m-d 23:59:59', strtotime($params['end_create_time']));
            $this->purchase_db->where("create_time between '{$start_time}' and '{$end_time}' ");
        }

        if(isset($params['start_occurrence_time']) and isset($params['end_occurrence_time'])
            and !empty($params['start_occurrence_time']) and !empty($params['end_occurrence_time']) and $params['start_occurrence_time'] != '0000-00-00'){
            $start_time = date('Y-m-d', strtotime($params['start_occurrence_time']));
            $end_time   = date('Y-m-d 23:59:59', strtotime($params['end_occurrence_time']));
            $this->purchase_db->where("occurrence_time between '{$start_time}' and '{$end_time}' ");
        }

        if(isset($params['create_user_id']) and $params['create_user_id'] != ''){
            $query->where('create_user_id', $params['create_user_id']);
        }

        if(isset($params['audit_status']) and $params['audit_status'] != ''){
            $query->where('audit_status', $params['audit_status']);
        }
        if(isset($params['order_no']) and $params['order_no'] != ''){
            $query->where('order_no', $params['order_no']);
        }

        if(isset($params['compare']) and $params['compare'] != ''){
            if($params['compare'] == 10){
                $query->where('adjust_money<0');
            }elseif($params['compare'] == 20){
                $query->where('adjust_money>0');
            }
        }
        $count_qb = clone  $query;
        $query->limit($limit, $offsets);
        $results                         = $query->order_by('id desc')->get()->result_array();
        $count_row                       = $count_qb->select('count(id) as num')->get()->row_array();
        $total_count                     = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        if($action == 'sum'){
            return $total_count;
        }
        $drop_down_list['purchase_name'] = get_purchase_agent();//公司主体
        $drop_down_list['audit_status']  = getBalanceAdjustmentOrderStatus();//{"1":"创建中","2":"已审核","3":"审核失败","4":"已作废"}
        $drop_down_list['compare']       = array(
            '1' => '全部',
            '10' => '<0',
            '20' => '>0'
        );
        $drop_down_list['applicant']     = $this->get_applicant_user_list(); //申请人

        if(!empty($results)){
            $this->load->model('supplier/Supplier_model');
            foreach($results as $key => $val){
                $results[$key]['purchase_name'] = get_purchase_agent($val['purchase_name']);
                $supplier_name                  = $this->Supplier_model->get_supplier_name_bycode($val['supplier_code'], 'supplier_name');
                $results[$key]['supplier_name'] = empty($supplier_name['supplier_name']) ? '' : $supplier_name['supplier_name'];
                $results[$key]['audit_status']  = getBalanceAdjustmentOrderStatus($val['audit_status']);
            }
        }
        $return_data = [
            'key'           => $this->get_head_list(),
            'values'        => $results,
            'drop_down_box' => $drop_down_list,
            'paging_data'   => [
                'total'  => $total_count,
                'offset' => $page,
                'limit'  => $limit,
                'pages'  => ceil($total_count / $limit),
            ]
        ];

        return $return_data;

    }


    /**
     * 导入余额调整单
     * @param $data
     * @return mixed
     */
    public function imp_balance_order($data){
        $return = [];
        $return['code']    = false;
        $return['message'] = '操作失败';

        if($data){
            $this->load->model('supplier/Supplier_model');

            $add_data                 = [];
            $error_list               = [];
            $cache_supplier_code_list = [];
            foreach($data as $key => $value){
                if($key <= 1) continue;//过滤第一条
                $purchase_name = isset($value['A']) ? trim($value['A']) : '';
                $supplier_code = isset($value['B']) ? trim($value['B']) : '';
                $adjust_money  = isset($value['C']) ? trim($value['C']) : '';
                $create_note   = isset($value['D']) ? trim($value['D']) : '';

                if(empty($purchase_name)){
                    $error_list[$key] = '采购主体不允许为空!';
                    continue;
                }else{
                    $purchase_agent = get_purchase_agent($purchase_name);
                    if(empty($purchase_agent)){
                        $error_list[$key] = '采购主体有输入有误!';
                        continue;
                    }
                }
                if(empty($supplier_code)){
                    $error_list[$key] = '供应商代码不允许为空!';
                    continue;
                }else{
                    // 验证供应商是否存在
                    if(isset($cache_supplier_code_list[$supplier_code])){
                        if(empty($cache_supplier_code_list[$supplier_code])){
                            $error_list[$key] = '供应商代码不存在!';
                            continue;
                        }
                    }else{
                        $supplierInfo = $this->Supplier_model->get_supplier_info($supplier_code,false);
                        if($supplierInfo){
                            $cache_supplier_code_list[$supplier_code] = $supplierInfo['supplier_name'];
                        }else{
                            $cache_supplier_code_list[$supplier_code] = '';
                            $error_list[$key] = '供应商代码不存在!';
                            continue;
                        }
                    }
                }
                if(empty($adjust_money) or !is_numeric($adjust_money)){
                    $error_list[$key] = '调整金额必须为数值!';
                    continue;
                }
                if(!is_two_decimal($adjust_money)){
                    $error_list[$key] = '调整金额最多为两位小数!';
                    continue;
                }
                if(empty($create_note)){
                    $error_list[$key] = '备注不允许为空!';
                    continue;
                }

                $add_data[] = [
                    'order_no'         => '',
                    'supplier_code'    => $supplier_code,
                    'supplier_name'    => $cache_supplier_code_list[$supplier_code],
                    'purchase_name'    => $purchase_name,
                    'occurrence_time'  => '0000-00-00 00:00:00',
                    'adjust_money'     => $adjust_money,
                    'audit_status'     => 1,
                    'create_time'      => date('Y-m-d H:i:s'),
                    'create_user_id'   => getActiveUserId(),
                    'create_user_name' => getActiveUserName(),
                    'create_note'      => $create_note,
                ];
            }
            if($error_list){
                $return['code'] = false;
                $return['data'] = $error_list;
                return $return;
            }
            try{
                $this->purchase_db->trans_begin();
                foreach($add_data as &$value){
                    $value['order_no'] = get_prefix_new_number('TZ'.date('Ymd'));
                }
                $add_data_list = array_chunk($add_data,5);
                foreach($add_data_list as $add_data){
                    $res = $this->purchase_db->insert_batch($this->table_name, $add_data);
                    if(empty($res)){
                        throw new Exception('数据插入失败');
                    }
                }

                $this->purchase_db->trans_commit();
                $return['code']    = true;
                $return['message'] = '';

            }catch(Exception $e){
                $return['code']    = false;
                $return['message'] = $e->getMessage();
                $this->purchase_db->trans_rollback();
            }

            return $return;
        }else{
            $return['code']    = false;
            $return['message'] = '数据缺失';

            return $return;
        }

    }

    /**
     * 保存余额调整单
     * @param $data_list
     * @return int
     */
    public function save_balance_order($data_list){
        return $this->purchase_db->insert_batch($this->table_name, $data_list);
    }

    /**
     * 余额调整单审核、作废
     * @param $id
     * @param $audit_status
     * @param $audit_note
     * @return bool
     */
    public function update_balance_order_status($id, $audit_status, $audit_note){
        $time = date('Y-m-d H:i:s');
        $update_arr = [
            'audit_status'    => $audit_status,
            'audit_time'      => $time,
            'audit_user_id'   => getActiveUserId(),
            'audit_user_name' => getActiveUserName(),
            'audit_note'      => $audit_note,
            'occurrence_time' => $time,// 发生时间 为审核通过时间
        ];
        $result = $this->purchase_db->where_in('id', $id)
            ->update($this->table_name, $update_arr);

        return $result;
    }

    /**
     * 根据ID判断调整单的状态
     * @author Jolon
     * @param $ids
     * @param $return_status
     * @return bool
     */
    public function get_balance_order_status($ids, $return_status){
        if(!is_array($ids)){
            $id = $this->purchase_db->select('id')->from($this->table_name)->where('id', $ids)->where('audit_status', $return_status)->get()->row_array();
            if(empty($id)){
                return true;
            }
        }else{
            $id = $this->purchase_db->select('id')->from($this->table_name)->where_in('id', $ids)->where('audit_status', $return_status)->get()->row_array();
            if(empty($id)){
                return true;
            }
        }

        return false;
    }

    /**
     * 获取 申请人列表 - 从创建记录里面group_by获得
     * @return array
     */
    public function get_applicant_user_list(){
        $list = $this->rediss->getData('balance_order_applicant_user_list');
        if(empty($list)){
            $list = $this->purchase_db->select('create_user_id,create_user_name')
                ->from($this->table_name)
                ->group_by('create_user_id')
                ->get()
                ->result_array();
            $list = array_column($list,'create_user_name','create_user_id');
            $this->rediss->setData('balance_order_applicant_user_list',$list);
            $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_balance_order_applicant_user_list');
        }
        return $list;
    }

    /**
     * 重置 申请人列表
     */
    public function reset_applicant_user_list(){
        $this->rediss->deleteData('balance_order_applicant_user_list');
        $this->get_applicant_user_list();
    }
}