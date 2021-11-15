<?php

/**
 * 供应商管理 ==> 供应商列表
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/24
 * Time: 16:38
 */
class Supplier_audit_model extends Purchase_model
{

    protected $table_name = 'supplier_audit_results';

    public static $audit_status;

    /**
     * Supplier constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('status_supplier');
        self::$audit_status = getSupplierAuditResultStatus();
    }

    /**
     * 审核人
     * @author: liwuxue
     * @date: 2019/1/24 17:08
     * @param:
     * @return: array
     */
    public function get_auditors()
    {
        //$record= $this->_findByCondition($condition)->select($fields)->order_by($order_by)->group_by($group_by)->get()->result_array();
        $rows = $this->getDataByCondition([], "audit_user", "", "audit_user");
        return is_array($rows) ? array_column($rows, "audit_user", "audit_user") : [];
    }

    /**
     * 供应商
     * @author: liwuxue
     * @date: 2019/1/24 18:11
     * @param:
     * @return: mixed
     */
    public function get_suppliers()
    {
        $rows = $this->getDataByCondition([], "supplier_code,supplier_name", "", "supplier_code");
        return is_array($rows) ? array_column($rows, "supplier_name", "supplier_code") : [];
    }

    /**
     * 筛选条件，查询列表
     * @author: liwuxue
     * @date: 2019/1/24 18:27
     * @param: array $param
     * @return: array
     */
    public function get_audit_list($param)
    {
        $resp = [];

        $where = [];
        $offset = 0;
        //字段与页面渲染字段数量、顺序一致page
        $fields = "a.id,a.supplier_name,a.supplier_code,a.audit_user,a.audit_time,a.apply_time,a.audit_status,a.audit_type,a.audit_used,a.remarks,s.create_user_name as create_user ,s.create_user_id as s_create_id";
        $builder = $this->purchase_db->select($fields)->from($this->table_name.' as a')
                                    ->join('pur_supplier as s','s.supplier_code = a.supplier_code');
        //不传分页为导出数据，查询全量
        if (isset($param['export'])) {
            $limit = null;
        } else{
            //分页查询列表
            $page = isset($param['offset']) && $param['offset'] > 0 ? (int)$param['offset'] : 1;
            $page_size = query_limit_range(isset($param['limit'])?$param['limit']:0);
            $offset = ($page - 1) * $page_size;
            $limit = $page_size;
        }
        //审核日期  前端已时间格式 yyyy-mm-dd HH:ii:ss
        $start_time = isset($param['start_time']) ? $param['start_time'] : 0;
        $end_time = isset($param['end_time']) ? $param['end_time'] : 0;
        if ($start_time >0 && $end_time > 0) {
          //  $where['audit_time >='] = date("Y-m-d H:i:s", $start_time);
         //   $where['audit_time <='] = date("Y-m-d H:i:s", $end_time);
             $where['a.audit_time >=']=$start_time;
             $where['a.audit_time <=']=$end_time;
        } 
        //审核人
        $audit_user = isset($param['audit_user']) ? trim($param['audit_user']) : '';
        if ($audit_user) {
            $where['a.audit_user'] = $audit_user;
        }
        //供应商
        $supplier_code = isset($param['supplier_code']) ? trim($param['supplier_code']) : '';
        if ($supplier_code) {
            $where['a.supplier_code'] = $supplier_code;
        }
        //审核状态
        $audit_status = isset($param['audit_status']) ? $param['audit_status'] : null;
        if ($audit_status !== null && isset(self::$audit_status[$audit_status])) {
            $where['a.audit_status'] = $audit_status;
        }

        //申请类型
        if(isset($param['apply_type']) && $param['apply_type']){
            if(is_array($param['apply_type'])){
                $builder->where_in('a.audit_type',$param['apply_type']);
            }else{
                $builder->where('a.audit_type',$param['apply_type']);
            }
        }

        //申请人
        if(isset($param['apply_user']) && $param['apply_user']){
            if(is_array($param['apply_user'])){
                $builder->where_in('a.create_user_id',$param['apply_user']);
            }else{
                $builder->where('a.create_user_id',$param['apply_user']);
            }
        }

        //创建人
        if(isset($param['create_name']) && $param['create_name']){//创建人多个模糊搜索
            if(is_array($param['create_name'])){
                $builder->group_start();
                foreach ($param['create_name'] as $value){
                    $builder->or_like('s.create_user_name',$value);
                }
                $builder->group_end();
            }else{
                $builder->like('s.create_user_name',$param['create_name']);
            }
        }



        //导出参数
       $ids= isset($param['ids'])?trim($param['ids']):'';
       if($ids){
           $builder->where_in('a.id',explode(',', $ids));
       }
       //pr($where);die;
        //$rows = $this->getDataList($where, $fields, "id desc", $offset, $limit);
        $count_builder = clone $builder;
        $total = $count_builder->where($where)->count_all_results();
        $rows = $builder ->where($where)
                            ->order_by('a.id','desc')
                            ->limit($limit,$offset)
                            ->get()
                            ->result_array();

        $this->load->model('supplier/Supplier_update_log_model');
        if (isset($rows) && !empty($rows)) {
            foreach ($rows as &$datum) {
                $datum['audit_status'] = self::get_audit_status_cn($datum['audit_status']);
                $datum['audit_type'] = getSupplierApplyType($datum['audit_type']);
                $datum['audit_used'] = self::get_audit_used_cn($datum['audit_used']);
                $datum['audit_time_list'] = $this->Supplier_update_log_model->get_audit_time_list($datum['supplier_code'],$datum['apply_time'],$datum['audit_time']);
            }
        }
		
        //列表title，与value的值数量和顺序完全一致
        $resp['data_list']['key'] = ["供应商", "审核人", "审核时间", "申请时间", "审核状态", "审核类型", "审核时效(H)",];
        //数据
        $resp['data_list']['value'] = isset($rows) ? $rows : [];
        //筛选项

        $resp['data_list']['drop_down_box'] = [
            'auditors' => $this->get_auditors(),//审核人
            'suppliers' => $this->get_suppliers(),//供应商
            'audit_status' => self::$audit_status ,//审核状态
            'apply_type'   => getSupplierApplyType(),//申请类型
            'apply_user'   => $this->get_apply_list(),
        ];	
        
		if (!isset($param['export'])) {
			//分页数据
			$resp['page_data'] = [
				'total' => isset($total) ? (int)$total : 0,
				'offset' => $page,
				'limit' => $page_size,
				'pages' => ceil(intval($total['total']) / intval($page_size)),
			];
		}
        return $resp;
    }

    /**
     * 审核状态（默认:0.待审核,10.审核通过,20.驳回）
     * @author liwuxue
     * @date 2019/1/24 18:43
     * @param int $audit_status
     * @return mixed|string
     */
    public static function get_audit_status_cn($audit_status)
    {
        return isset(self::$audit_status[$audit_status]) ? self::$audit_status[$audit_status] : '';
    }

    /**
     * 审核类型(默认:0,1.新增供应商,2修改资料)
     * @author: liwuxue
     * @date: 2019/1/24 18:35
     * @param int $audit_type
     * @return string|mixed
     */
    public static function get_audit_type_cn($audit_type)
    {
        $arr = [
            0 => '',
            1 => '新增供应商',
            2 => '修改资料',
        ];
        return isset($arr[$audit_type]) ? $arr[$audit_type] : '';
    }

    /**
     * 审核时效（秒）
     * @author liwuxue
     * @date 2019/1/24 18:37
     * @param int $audit_used
     * @return mixed|string
     */
    public static function get_audit_used_cn($audit_used)
    {
        return round(ceil(($audit_used/3600) * 100) / 100, 2) . "H";
    }

    /** 获取所有更新日志人员名单
     * @return array
     */
    public function get_apply_list(){
        $res = $this->purchase_db->select('DISTINCT(`create_user_id`), `create_user_name`')
            ->where('create_user_id >' ,0)
            ->where('create_user_id !=',1)//排除admin
            ->get('pur_supplier_update_log')
            ->result_array();

        return array_column($res,'create_user_name','create_user_id');
    }


}