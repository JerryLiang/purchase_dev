<?php
/**
 * Created by PhpStorm.
 * 同款货源列表
 * User: Jolon
 * Date: 2021/11/08 0027 11:17
 */
class Product_similar_model extends Purchase_model {
    protected $table_name           = 'product_similar';// 同款货源SKU列表
    protected $table_config_name    = 'product_similar_config';// 同款货源配置项信息表
    protected $table_supply_log     = 'operator_supply_status_log';// SKU货源状态变更日志


    public $_priority_list          = ['1' => '高','2' => '中','3' => '低'];// 优先级
    public $_is_enable_list         = ['1' => '是','2' => '否'];// 是否启用
    public $_status_list            = ['10' => '创建','20' => '待分配','30' => '待审核'];
    private $_link_type             = 12;// 日志类型

    public function __construct(){
        parent::__construct();

        $this->load->model('product/product_line_model');
    }


    //region  配置项

    /**
     * 获取配置项
     * @param int $id 指定记录ID
     * @return array
     */
    public function get_similar_config($id = null){
        if(!is_null($id)){

            $record = $this->purchase_db->where('id',$id)
                ->get($this->table_config_name)
                ->row_array();
        }else{

            $record = $this->purchase_db->where('is_del',1)
                ->get($this->table_config_name)
                ->result_array();
        }


        return $record?$record:[];
    }

    /**
     * 修改 配置项
     * @param $update_arr
     * @return array
     */
    public function save_similar_config($update_arr){
        if(empty($update_arr)) return $this->res_data(false,'更新数据项为空');
        if(!is_array($update_arr)) return $this->res_data(false,'更新数据项必须为二维数据');

        $cache_priority_list = [];
        $sale_section = [];

        foreach($update_arr as $value){
            $days_sales_start = isset($value['days_sales_start'])?$value['days_sales_start']:0;
            $days_sales_end = isset($value['days_sales_end'])?$value['days_sales_end']:0;
            $priority = isset($value['priority'])?$value['priority']:0;
            $is_enable = isset($value['is_enable'])?$value['is_enable']:0;


            if($days_sales_start < 0 or !preg_match('/^[0-9]+$/',$days_sales_start)){
                return $this->res_data(false,'不满足条件：前输入框值必须是≥0的正整数');
            }
            if($days_sales_end < 0 or !preg_match('/^[0-9]+$/',$days_sales_end)){
                return $this->res_data(false,'不满足条件：后输入框值必须是≥0的正整数');
            }

            if($days_sales_start >= $days_sales_end){
                return $this->res_data(false,'不满足条件：前输入框<后输入框');
            }

            if(!in_array($priority,array_keys($this->_priority_list))){
                return $this->res_data(false,'不满足条件：优先级必填');
            }
            if(!in_array($is_enable,array_keys($this->_is_enable_list))){
                return $this->res_data(false,'不满足条件： 是否启用必填');
            }

            if(in_array($priority,$cache_priority_list)){
                return $this->res_data(false,'优先级不能重复，请检查');
            }else{
                $cache_priority_list[] = $priority;
            }

            $sale_section[] = ['start' => $days_sales_start,'end' => $days_sales_end];
        }

        // 验证 销量区间是否有交叉
        $check_res_flag = true;
        foreach ($sale_section as $check_key => $check_value){
            $check_start = $check_value['start'];
            $check_end = $check_value['end'];
            foreach($sale_section as $key => $value){
                if($key == $check_key) continue;

                $start = $value['start'];
                $end = $value['end'];

                if( ($check_start >= $start and $check_start < $end) or ($check_end > $start and $check_end <= $end) or ($check_start <= $start and $check_end >= $end) ){
                    $check_res_flag = false;
                }
            }
        }

        if($check_res_flag === false) return $this->res_data(false,'销量区间有交叉，请检查');


        $value_tmp_list = [];
        foreach($update_arr as $value){
            $value_tmp = [];
            $value_tmp['sales_type'] = isset($value['sales_type'])?$value['sales_type']:30;
            $value_tmp['days_sales_start'] = isset($value['days_sales_start'])?$value['days_sales_start']:0;
            $value_tmp['days_sales_end'] = isset($value['days_sales_end'])?$value['days_sales_end']:0;
            $value_tmp['priority'] = isset($value['priority'])?$value['priority']:0;
            $value_tmp['is_enable'] = isset($value['is_enable'])?$value['is_enable']:0;
            $value_tmp['create_time'] = date('Y-m-d H:i:s');

            $value_tmp_list[] = $value_tmp;

        }

        $this->purchase_db->update($this->table_config_name,['is_del' => 2]);// 删除已存在的所有数据

        $res = $this->purchase_db->insert_batch($this->table_config_name,$value_tmp_list);
        if($res){
            return $this->res_data(true);
        }else{
            return $this->res_data(false,'配置项数据库保存失败');
        }
    }


    //endregion


    /**
     * 添加 同款货源推荐列表 数据记录
     * @param $sku
     * @param $priority
     * @return bool
     */
    public function add_similar_record($sku,$priority){
        $apply_number = 'SM'.date('YmdHis').'_'.$sku;
        $pushTime      = date('Y-m-d H:i:s');

        // 判断是否存在 处理中的记录
        $haveRecord = $this->purchase_db->from($this->table_name)
            ->where('sku',$sku)
            ->where('is_delete',1)
            ->where('status',10)
            ->get()
            ->row_array();

        if($haveRecord){

            // 更新推送时间，更新为已推送
            $updateSql = "UPDATE pur_{$this->table_name} 
                    SET smc_push_time='".$pushTime."',smc_push_times=CONCAT(smc_push_times,';".$pushTime."')
                    WHERE id=".intval($haveRecord['id'])." LIMIT 1";
            $this->purchase_db->query($updateSql);
        }else{
            $addRecord = [
                'apply_number' => $apply_number,
                'sku' => $sku,
                'status' => 10,
                'priority' => $priority,
                'smc_push_time' => $pushTime,
                'smc_push_times' => $pushTime
            ];

            $this->purchase_db->insert($this->table_name,$addRecord);
        }

        return true;
    }

    /**
     * 获取 同款货源信息列表
     * @author Jolon
     * @param array $params
     * @param int $page
     * @param int $limit
     * @param string $type 页面类型
     * @param string $action 操作类型
     * @return array
     * 2019-1-8
     */
    public function get_similar_list($params, $page, $limit,$type = 'initial',$action = 'select'){
        $this->load->helper('status_product');
        $this->load->model('product/Product_line_model');
        $this->load->model('supplier/Supplier_settlement_model');

        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;


        if (isset($params['product_line_id']) && !empty($params['product_line_id'])) {
            $category_all_ids = $this->product_line_model->get_all_category($params['product_line_id']);
        }

        if($type == 'initial'){// 同款货源推荐
            $fields = 'sim.id,sim.apply_number,sim.sku,sim.smc_push_times,sim.smc_push_time,sim.priority,sim.applied,';
            $fields .= 'p.supply_status,p.product_img_url,p.product_name,p.supplier_code,p.supplier_name,';
            $fields .= 'p.purchase_price,p.product_line_id,p.rought_weight,p.sample_package_size,p.product_cn_link';
        }elseif($type == 'wait_audit' or $type == 'wait_allot' or $type == 'passed'){// 待分配，待审核，已通过
            $fields = 'sim.*,';
            $fields .= 'p.supply_status,p.product_img_url,p.product_name,';
            $fields .= 'p.purchase_price,p.product_line_id,p.rought_weight,p.sample_package_size';
        }else{
            return $this->res_data(false,'暂不支持的请求类型');
        }

        $this->purchase_db->from($this->table_name.' AS sim')
            ->join('product AS p','sim.sku=p.sku','LEFT')
            ->where('sim.is_delete',1);// 1.未删除的

        // 四合一界面查询，只展示对应状态的记录
        switch ($type){
            case 'initial':// 同款货源推荐
                $this->purchase_db->where('sim.status',10);
                break;
            case 'wait_allot':// 待分配
                $this->purchase_db->where('sim.status',20);
                break;
            case 'wait_audit':// 待审核
                $this->purchase_db->where('sim.status',30);
                break;
            case 'passed':// 已通过
                $this->purchase_db->where('sim.status',40);
                break;
        }

        // 产品线
        if (isset($category_all_ids)) {
            if($category_all_ids and is_array($category_all_ids)){
                $children_ids = is_string($category_all_ids)?explode(",", $category_all_ids):[];
                $children_ids = array_filter($children_ids);
                $this->purchase_db->where_in('p.product_line_id', $children_ids);
            }else{
                $this->purchase_db->where_in('p.product_line_id', -1);// 没有对应的产品线数据是使用-1来排斥数据
            }
        }

        if(isset($params['apply_number_list']) and !empty($params['apply_number_list'])){
            if(is_array($params['apply_number_list'])){
                $this->purchase_db->where_in('sim.apply_number',$params['apply_number_list']);
            }else{
                $apply_number_arr = query_string_to_array($params['apply_number_list']);
                $this->purchase_db->where_in('sim.apply_number',$apply_number_arr);
            }
        }
        if(isset($params['sku_list']) and !empty($params['sku_list'])){
            if(is_array($params['sku_list'])){
                $this->purchase_db->where_in('sim.sku',$params['sku_list']);
            }else{
                $sku_arr = query_string_to_array($params['sku_list']);
                $this->purchase_db->where_in('sim.sku',$sku_arr);
            }
        }
        if(isset($params['smc_similar_code_list']) and !empty($params['smc_similar_code_list'])){
            if(is_array($params['smc_similar_code_list'])){
                $this->purchase_db->where_in('sim.smc_similar_code',$params['smc_similar_code_list']);
            }else{
                $smc_similar_code_arr = query_string_to_array($params['smc_similar_code_list']);
                $this->purchase_db->where_in('sim.smc_similar_code',$smc_similar_code_arr);
            }
        }
        if(isset($params['supplier_code_list']) and !empty($params['supplier_code_list'])){
            if(is_array($params['supplier_code_list'])){
                $this->purchase_db->where_in('sim.supplier_code',$params['supplier_code_list']);
            }else{
                $supplier_code_arr = query_string_to_array($params['supplier_code_list']);
                $this->purchase_db->where_in('sim.supplier_code',$supplier_code_arr);
            }
        }
        if(isset($params['supply_status']) and !empty($params['supply_status'])){
            if(is_array($params['supply_status'])){
                $this->purchase_db->where_in('p.supply_status',$params['supply_status']);
            }else{
                $this->purchase_db->where('p.supply_status',$params['supply_status']);
            }
        }
        if(isset($params['priority']) and !empty($params['priority'])){
            if(is_array($params['priority'])){
                $this->purchase_db->where_in('sim.priority',$params['priority']);
            }else{
                $this->purchase_db->where('sim.priority',$params['priority']);
            }
        }


        $clone_count = clone $this->purchase_db;
        $count = $clone_count->select('count(1) as num')->get()->row_array();
        $count = isset($count['num'])?$count['num']:0;

        //列表查询
        $results = $this->purchase_db->select($fields)->limit($limit, $offset)->get()->result_array();

        if($action == 'total'){
            return $count;
        }

        if( !empty($results)) {
            $sku_arr = array_unique(array_column($results,'sku'));

            // 获取结算方式
            $supplier_codes = array_unique(array_column($results,'smc_supplier_code'));
            $supplier_codes = empty($supplier_codes)?[PURCHASE_NUMBER_ZFSTATUS]:$supplier_codes;
            $payment_info   = $this->Supplier_settlement_model->get_supplier_settlement_all($supplier_codes);

            $statisticSku = $this->purchase_db->select('sku,count(1) as similar_num')
                ->from($this->table_name)
                ->where('status',40)
                ->where_in('sku',$sku_arr)
                ->group_by('sku')
                ->get()
                ->result_array();
            $statisticSku = array_column($statisticSku,'similar_num','sku');

            foreach ($results as $key => &$value) {
                $value['supply_status_cn'] = getProductsupplystatus($value['supply_status']);
                isset($value['applied']) and $value['applied_cn'] = $value['applied'] == '1'?'否':'是';
                isset($value['status']) and $value['status_cn'] = isset($this->_status_list[$value['status']])?$this->_status_list[$value['status']]:'';
                isset($value['priority']) and $value['priority_cn'] = isset($this->_priority_list[$value['priority']])?$this->_priority_list[$value['priority']]:'';

                if(isset($value['product_line_id'])){
                    $product_line_data = $this->product_line_model->get_product_top_line_data($value['product_line_id']);
                    $value['product_line_id_top_cn'] = isset($product_line_data['linelist_cn_name'])?$product_line_data['linelist_cn_name']:'';
                }else{
                    $value['product_line_id_top_cn'] = '';
                }

                if(isset($value['smc_push_times'])){
                    $smc_push_times = explode(';',trim($value['smc_push_times'],';'));
                    sort($smc_push_times);
                    $value['smc_push_times'] = $smc_push_times;
                }
                if(isset($value['smc_dev_image'])){
                    $smc_dev_image = explode(';',$value['smc_dev_image']);
                    $value['smc_dev_image'] = $smc_dev_image;
                }

                if($type != 'initial'){
                    $value['smc_similar_total'] = isset($statisticSku[$value['sku']])?$statisticSku[$value['sku']]:0;
                    $value['supplier_settlement'] = isset($payment_info[$value['smc_supplier_code']]) ? $payment_info[$value['smc_supplier_code']]['supplier_settlement'] : '';
                    $value['supplier_settlement_cn'] = isset($payment_info[$value['smc_supplier_code']]) ? $payment_info[$value['smc_supplier_code']]['supplier_settlement_cn'] : '';
                }
            }
        }

        if($action == 'export') return $results;

        // 下拉框数据
        $product_line_list = $this->product_line_model->get_product_line_list(0);
        $drop_down_box['product_line_id']   = array_column($product_line_list, 'linelist_cn_name','product_line_id');
        $drop_down_box['supply_status']     = getProductsupplystatus();//货源状态
        $drop_down_box['priority_list']     = $this->_priority_list;

        $return_data = [
            'values'   => $results,
            'page_data' => [
                'total'     => intval($count),
                'limit'     => intval($limit),
                'offset'    => intval($page)
            ],
            'drop_down_box' => $drop_down_box
        ];


        return $return_data;
    }

    /**
     * 获取 同款货源详情数据
     * @param $apply_number
     * @return array
     */
    public function get_similar_detail($apply_number){
        $fields = 'sim.*,';
        $fields .= 'p.supply_status,p.product_img_url,p.product_name,';
        $fields .= 'p.purchase_price,p.product_line_id,p.rought_weight,p.sample_package_size';

        $similar_info = $this->purchase_db->select($fields)
            ->from($this->table_name.' AS sim')
            ->join('product AS p','sim.sku=p.sku','LEFT')
            ->where('sim.apply_number',$apply_number)
            ->get()
            ->row_array();

        return $similar_info?$similar_info:[];
    }


    /**
     * 移除记录
     * @param $apply_number
     * @return array
     */
    public function delete_similar($apply_number){
        $res = $this->purchase_db->where('apply_number',$apply_number)
            ->update($this->table_name,['is_delete' => 2]);

        if($res){
            return $this->res_data(true,'操作成功');
        }else{
            return $this->res_data(false,'移除结果数据库保存失败');
        }
    }

    /**
     * 移除记录_根据SKU批量移除
     * @param $sku
     * @return array
     */
    public function delete_similar_by_sku($sku){
        $res = $this->purchase_db->where('sku',$sku)
            ->where('status',10) // 只能删除刚创建的，之后流程中的不允许删除
            ->update($this->table_name,['is_delete' => 2]);

        if($res){
            return $this->res_data(true,'操作成功');
        }else{
            return $this->res_data(false,'移除结果数据库保存失败');
        }
    }

    /**
     * 分配人员
     * @param $apply_number
     * @param $allot_user_id
     * @param $allot_user_name
     * @return array
     */
    public function allot_user_similar($apply_number,$allot_user_id,$allot_user_name){
        $res = $this->purchase_db->where('apply_number',$apply_number)
            ->update($this->table_name,['status' => 30,'allot_user_id' => $allot_user_id,'allot_user_name' => $allot_user_name]);

        if($res){
            $this->add_similar_log($apply_number,'分配任务给'.$allot_user_name,'分配人员');
            return $this->res_data(true,'操作成功');
        }else{
            return $this->res_data(false,'审核结果数据库保存失败');
        }
    }


    /**
     * 审核 同款货源记录
     * @param $apply_number
     * @param $audit_status
     * @param $remark
     * @return array
     */
    public function audit_similar($apply_number,$audit_status,$remark){
        if($audit_status != 35 and $audit_status != 40){
            return $this->res_data(false,'审核状态有误');
        }

        $res = $this->purchase_db->where('apply_number',$apply_number)
            ->update($this->table_name,['status' => $audit_status,'audit_time' => date('Y-m-d H:i:s'),'audit_remark' => $remark]);
        
        if($res){
            $this->add_similar_log($apply_number,$remark, $audit_status == 40 ?'审核通过' : '审核驳回');
            return $this->res_data(true,'操作成功');
        }else{
            return $this->res_data(false,'审核结果数据库保存失败');
        }
    }


    /**
     * 新增操作日志
     * @param string $apply_number 单据编号
     * @param string $remark 操作备注信息
     * @param string $operation_type 操作类型
     * @param string $user_id 操作用户ID
     * @param string $user_name 操作用户名称
     * @param string $create_time 创建时间
     * @return array
     */
    public function add_similar_log($apply_number,$remark,$operation_type = '',$user_id = null,$user_name = null,$create_time = null){
        $this->load->model('statement/Purchase_statement_note_model');
        $this->Purchase_statement_note_model->add_remark($apply_number,$this->_link_type,$remark,$operation_type,$user_id,$user_name,$create_time);
        return $this->res_data(true);
    }

    /**
     * 获取操作日志
     * @param $apply_number
     * @return array
     */
    public function get_similar_logs($apply_number){
        $this->load->model('statement/Purchase_statement_note_model');
        $logs = $this->Purchase_statement_note_model->get_remark_list($apply_number,$this->_link_type);
        return $this->res_data(true,'操作成功',$logs);
    }

    /**
     * 获取 同款货源推荐详情
     * @param $sku
     * @return array
     */
    public function get_similar_history_detail($sku){
        $this->load->model('supplier/Supplier_settlement_model');

        $similar_history_list = $this->purchase_db->select('sku,smc_similar_code,smc_supplier_code,smc_supplier_name,smc_product_cost')
            ->from($this->table_name)
            ->where('status',40)
            ->where('sku',$sku)
            ->get()
            ->result_array();

        // 获取结算方式
        $supplier_codes = array_unique(array_column($similar_history_list,'smc_supplier_code'));
        $supplier_codes = empty($supplier_codes)?[PURCHASE_NUMBER_ZFSTATUS]:$supplier_codes;
        $payment_info   = $this->Supplier_settlement_model->get_supplier_settlement_all($supplier_codes);


        foreach ($similar_history_list as &$item) {

            $item['supplier_settlement'] = isset($payment_info[$item['smc_supplier_code']]) ? $payment_info[$item['smc_supplier_code']]['supplier_settlement'] : '';
            $item['supplier_settlement_cn'] = isset($payment_info[$item['smc_supplier_code']]) ? $payment_info[$item['smc_supplier_code']]['supplier_settlement_cn'] : '';
        }

        return $similar_history_list?$similar_history_list:[];
    }

    /**
     * 保存门户系统 提交的同款货源数据
     * @param $apply_number
     * @param $new_status
     * @param $update_data
     * @return array
     */
    public function save_smc_same_product_detail($apply_number,$new_status,$update_data){
        if($new_status != 20 and $new_status != 30){
            return $this->res_data(false,'提交状态有误');
        }

        $update_data['status'] = $new_status;
        if($new_status == 30){
            $update_data['is_push_smc_audit'] = 0;// 修改成未推送
        }
        $res = $this->purchase_db->where('apply_number',$apply_number)->update($this->table_name,$update_data);
        if($res){
            return $this->res_data(true,'操作成功');
        }else{
            return $this->res_data(false,'审核结果数据库保存失败');
        }
    }

    /**
     * 根据 SKU货源状态变更日志 生成 同款货源推荐列表数据
     * @param string $sales_type            销量类型
     * @param string $days_sales_start      销量起始值
     * @param string $days_sales_end        销量结束值
     * @param string $priority              优先级
     * @param string $create_time           创建时间（获取该时间段之后的日志）
     * @return bool
     */
    public function plan_create_similar_record($sales_type,$days_sales_start,$days_sales_end,$priority,$create_time){
        $wait_handle_list = $this->purchase_db->select('MAX(id) AS sku_max_id,sku')
            ->from($this->table_supply_log)
            ->where('similar_handle',0)
            ->where('operate_time >=',$create_time) // 只处理 启用记录创建之后 变更的货源状态记录
            ->group_by('sku')
            ->get()
            ->result_array();

        $this->load->helper('status_product');
        $supplyStatusList = getProductsupplystatus();

        // 根据天数 获取销量类型（便于拓展）
        if($sales_type == 30){
            $days_sales_type = 'days_sales_30';
        }else{
            $days_sales_type = 'days_sales_30';
        }

        foreach($wait_handle_list as $sku_value){
            $latest_sku_record = $this->purchase_db->select('supply_log.id,supply_log.apply_number,supply_log.sku,supply_log.new_supply_status')
                ->select('p.'.$days_sales_type.' AS days_sales_value')
                ->from($this->table_supply_log.' AS supply_log')
                ->join('product AS p','p.sku=supply_log.sku','left')
                ->where('supply_log.id',$sku_value['sku_max_id'])
                ->get()
                ->row_array();
            if(empty($latest_sku_record)) continue;

            $supply_status = $latest_sku_record['new_supply_status'];

            // 货源状态不是正常状态 而且满足 销量范围要求  的同款货源推荐列表
            if($latest_sku_record['new_supply_status']
                and $latest_sku_record['new_supply_status'] != 1
                and $latest_sku_record['days_sales_value'] >= $days_sales_start
                and $latest_sku_record['days_sales_value'] < $days_sales_end)
            {
                $this->add_similar_record($latest_sku_record['sku'],$priority);// 推送到 同款货源推荐列表

                $supply_status_cn = isset($supplyStatusList[$supply_status])?$supplyStatusList[$supply_status]:PURCHASE_NUMBER_ZFSTATUS;
                $this->add_similar_log($latest_sku_record['apply_number'],'sku货源状态为'.$supply_status_cn.'，推送至同款产品池','推送货源异常sku');
            }elseif($latest_sku_record['new_supply_status'] == 1){// 货源状态为正常，删除已存在同款货源列表的记录

                $this->delete_similar_by_sku($latest_sku_record['sku']);
            }

            // 更新待处理记录为已处理
            $this->purchase_db->where('sku',$latest_sku_record['sku'])
                ->where('similar_handle',0)
                ->update($this->table_supply_log,['similar_handle' => 1]);
        }

        return true;
    }

    /**
     * 计划任务推送 同款货源数到 门户系统
     */
    public function push_similar_to_smc(){
        $this->load->library('SMCApi');
        $smcApi = new SMCApi();

        $header        = array('Content-Type: application/json');
        $url           = getConfigItemByName('api_config','java_system_erp','yibaiProduct-getSkuInfoBySku');
        $newUrl        = getConfigItemByName('api_config','java_system_erp','new_yibaiProduct-getSkuInfoBySku');
        $access_taken  = getOASystemAccessToken();
        $url           = $url."?access_token=".$access_taken;
        $newUrl        = $newUrl."?access_token=".$access_taken;


        // 1/3 记录 推送门户系统
        $waitPushList = $this->purchase_db->select('sim.id,sim.apply_number,sim.sku,p.product_name,sim.priority,p.product_img_url')
            ->select('p.product_category_id,p.sample_package_length,p.sample_package_width,p.sample_package_heigth,p.product_model')
            ->select('sim.smc_push_time AS pushTime')
            ->select('p.product_brand,p.sample_packaging_type,p.sku_message AS goodsParams,p.rought_weight')
            ->from($this->table_name.' AS sim')
            ->join('product AS p','p.sku=sim.sku','left')
            ->where('sim.is_push_smc',0) // 0.未推送
            ->where('sim.is_delete',1) // 1.未删除
            ->limit(50)
            ->get()
            ->result_array();

        if($waitPushList){
            // 补充商品资料
            foreach($waitPushList as &$item_value){
                $param         = [];
                $param['sku']  = $item_value['sku'];

                // 获取 SKU
                $result        = getCurlData($url,json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);
                $newResult     = getCurlData($newUrl,json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);

                $item_value['priority'] = isset($this->_priority_list[$item_value['priority']])?$this->_priority_list[$item_value['priority']]:'';


                // 补充商品资料
                if(is_json($result)){
                    $result         = json_decode($result, true);
                    $resultData     = ($result['code'] == 200)?$result['data']:[];

                    $item_value['packing_list']     = isset($resultData['packingList'])?$resultData['packingList']:'';// 包装清单
                    $item_value['product_material'] = isset($resultData['materialCn'])?$resultData['materialCn']:'';// 产品材质
                }else{
                    $item_value['packing_list']     = '';
                    $item_value['product_material'] = '';
                }

                // 补充商品资料
                if(is_json($newResult)){
                    $newResult      = json_decode($newResult,true);
                    $newResultData  = ($newResult['code'] == 200)?$newResult['data']:[];
                    $item_value['special_packing'] = isset($newResultData['specialPack'])?$newResultData['specialPack']:''; // 特殊包装类型
                }else{
                    $item_value['special_packing'] = '';
                }
            }

            $pushRes = $smcApi->pushSameProduct($waitPushList);

            if($pushRes['code']){
                $id_arr = array_column($waitPushList,'id');
                $this->purchase_db->where_in('id',$id_arr)->update($this->table_name,['is_push_smc' => 1]);

                echo "1/3 记录 推送门户系统->成功<br>\n";
            }else{
                echo "1/3 记录 推送门户系统->失败".$pushRes['errorMsg']."<br>\n";
            }
        }else{
            echo "1/3 记录 推送门户系统->Not Founds<br>\n";
        }


        // 2/3 记录审核结果 推送
        $waitPushAuditList = $this->purchase_db->select('sim.id,sim.smc_similar_code,sim.status,sim.audit_remark')
            ->from($this->table_name.' AS sim')
            ->where('sim.is_push_smc_audit',0) // 0.未推送
            ->where_in('sim.status',[35,40])
            ->limit(50)
            ->get()
            ->result_array();

        if($waitPushAuditList){
            $pushRes = $smcApi->receiveSameProductAudit($waitPushAuditList);
            if($pushRes['code']){
                $id_arr = array_column($waitPushAuditList,'id');
                $this->purchase_db->where_in('id',$id_arr)->update($this->table_name,['is_push_smc_audit' => 1]);// 更新为已推送

                echo "2/3 记录 推送门户系统->成功<br>\n";
            }else{
                echo "2/3 记录 推送门户系统->失败".$pushRes['errorMsg']."<br>\n";
            }
        }else{
            echo "2/3 记录 推送门户系统->Not Founds<br>\n";
        }


        // 3/3 记录删除 推送
        $waitPushDeleteList = $this->purchase_db->select('sim.id,sim.sku')
            ->from($this->table_name.' AS sim')
            ->where('sim.is_push_smc_delete',0) // 0.未推送
            ->where('sim.is_delete',2) // 2.未删除
            ->limit(50)
            ->get()
            ->result_array();

        if($waitPushDeleteList){
            $skuList = array_column($waitPushDeleteList,'sku');
            $pushRes = $smcApi->removeSameProduct($skuList);
            if($pushRes['code']){
                $id_arr = array_column($waitPushDeleteList,'id');
                $this->purchase_db->where_in('id',$id_arr)->update($this->table_name,['is_push_smc_delete' => 1]);// 更新为已推送

                echo "3/3 记录 推送门户系统->成功<br>\n";
            }else{
                echo "3/3 记录 推送门户系统->失败".$pushRes['errorMsg']."<br>\n";
            }
        }else{
            echo "3/3 记录 推送门户系统->Not Founds<br>\n";
        }

        return true;
    }


}