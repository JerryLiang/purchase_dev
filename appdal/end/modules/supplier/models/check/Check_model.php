<?php

/**
 * Created by PhpStorm.
 * 供应商验货验厂
 * author: Jackson
 * Date: 2019/1/23
 */
class Check_model extends Purchase_model
{

    protected $table_name = 'supplier_check';//供货商验货验厂操作
    protected $smark_table_name = 'supplier_check_note';//供货商验厂-备注
    protected $supplier_table_name = 'supplier';//供应商表
    protected $supplier_buyer_table_name = 'supplier_buyer';//供应商联系方式表
    protected $supplier_sku_table_name = 'supplier_check_sku';//供货商验厂 SKU

    /**
     * 返回表名
     * MY_Model 中的 filterNotExistFields() 方法需要
     * @return string
     */
    public function table_nameName()
    {
        return $this->table_name;
    }

    /**
     * Supplier_model constructor.
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @desc 创建验货验厂
     * @author Jackson
     * @Date 2019-01-23
     * @return array()
     **/
    public function create_inspection(array $params, &$last_Id)
    {
        // 1.验证字段
        list($status, $msg) = $this->validateParams($params);
        if (!$status) {
            return array(false, $msg);
        }

        //增加用户信息
        $params['modify_user_name'] = '';//用户名
        analyzeUserInfo($params);
        $params['modify_time'] = date("Y-m-d H:i:s");

        //申请人信息
        $params['apply_user_id'] = getActiveUserId();
        $params['apply_user_name'] = getActiveUserName();

        // 2.过滤非数据库字段
        $params = $this->filterNotExistFields($params);

        // 3.保存
        $this->insert($params);
        $last_Id = $this->getLastInsertID();//获取最后插入ID
        return $this->getAffectedRows() > 0 ? array(true, '添加成功') : array(false, "添加失败:" . $this->getWriteDBError());

    }

    /**
     * @desc 获取验货验厂列表
     * @author Jackson
     * @Date 2019-01-23
     * @return array()
     **/
    public function get_by_page($params = [])
    {
        // 1.搜索条件
        $condition = array();
        //LIKE搜索字段
        $likeFields = array(
            'pur_number', 'sku'
        );
        //一般搜索字段
        $_fields = array(
            'status', 'apply_user_id', 'check_type', 'judgment_results', 'is_urgent', 'create_time'
        );

        //组装LIKE条件语句
        if (!empty($likeFields)) {
            foreach ($likeFields as $field) {
                if ($field == 'sku' && isset($params[$field]) && $params[$field]) {
                    //$skuList = array_filter(explode(",", trim($params[$field])));
                    $skuList = query_string_to_array($params[$field]);
                    $condition["where_in"] = ['sk.sku' => $skuList];
                } else {
                    if (!empty($params[$field])) {
                        $condition["$field LIKE"] = $params[$field] . '%';
                    }
                }
            }
        }
		
        //其他搜索字段
        if (!empty($_fields)) {
            foreach ($_fields as $field) {
                if (isset($params[$field]) && $params[$field]!='') {				
                    if ($field == 'create_time') {
                        list($startTime, $endTiem) = explode(",", $params[$field]);
                        $condition[$this->table_name . '.' . $field . '>='] = $startTime . " 00:00:00";
                        $condition[$this->table_name . '.' . $field . '<'] = $endTiem . " 23:59:59";
                    } elseif (in_array($field, array('status', 'check_type'))) {
                        $condition[$this->table_name . '.' . $field] = $params[$field];
                    } else {
                        $condition[$field] = $params[$field];
                    }
                }
            }
        }

        // 2.排序条件
        $orderBy = '';

        // 3.分页参数
        $pageSize = query_limit_range(isset($params['limit'])?$params['limit']:0);
        $page = !isset($params['offset']) || intval($params['offset']) <= 0 ? 1 : intval($params['offset']);
        $offset = ($page - 1) * $pageSize;

        // 4. 查询数据
        $joinCondition = array(
            array(
                "{$this->smark_table_name} m", "{$this->table_name}.id=m.check_id", 'left',
            ),
            array("{$this->supplier_table_name} s", "{$this->table_name}.supplier_code=s.supplier_code", 'left'),
            array("{$this->supplier_sku_table_name} sk", "{$this->table_name}.id=sk.check_id", 'left'),
        );

        //查询字段
        $fields = $this->table_name . '.id,apply_user_id,apply_user_name,check_time,' . $this->table_name . '.create_time,check_note,' .
            $this->table_name . '.check_type,check_times,supplier_name,
        contact_person,phone_number,contact_address,pur_number,times,' . $this->table_name . '.status,judgment_results,
        check_reason,review_reason,check_price';

        //数据查询
        $result = $this->getDataListByJoin($condition, $fields, $orderBy, $offset, $pageSize, "{$this->table_name}.id", $joinCondition);
//        echo $this->db->last_query();die;

        return array(
            'count' => $result['total'],
            'page_count' => ceil($result['total'] / $pageSize),
            'list' => $result['data'],
        );

    }

    /**
     * @desc 供应商验货验厂信息字段验证
     * @author Jackson
     * @param array $params 参数
     * @return array = array(
     *      $status => bool 是否成功
     *      $msg    => string 错误信息
     * )
     */
    public function validateParams(array &$params)
    {
        //查检字段是否为空
        $reqFields = array(
            'check_type', 'supplier_code', 'check_times', 'group_id'
        );

        if (!empty($reqFields)) {
            foreach ($reqFields as $key => $field) {
                if (!isset($params[$field]) || $params[$field] === '') {
                    return array(false, "供应商验货验厂： $field 不能为空");
                } else {
                    $params[$field] = trim($params[$field]);//去掉首尾空格
                }
            }
        }
        return array(true, 'OK');
    }

    /**
     * @desc 供应商验货验厂数据导出
     * @author Jackson
     * @param array $params 参数
     * @return array
     */
    public function get_export(array $params)
    {
        // 1.搜索条件
        $condition = array();
        //判断勾选ID导出(IN条件查询)
        if (isset($params['id']) && is_string($params['id'])) {
            $condition['where_in'] = array($this->table_name . '.id' => explode(",", $params['id']));
        }

        //其他条件查询
        foreach ($params as $key => $value) {


            if($value!=''){
                if (in_array($key, array('id', 'uid'))) {
                    continue;
                }

                if ($key == 'create_time') {

                    list($startTime, $endTiem) = explode(",", $value);
                    $condition[$this->table_name . '.' . $key . '>='] = $startTime . " 00:00:00";
                    $condition[$this->table_name . '.' . $key . '<'] = $endTiem . " 23:59:59";

                } elseif (in_array($key, array('status', 'check_type'))) {
                    $condition[$this->table_name . '.' . $key] = $value;
                } else if (in_array($key, array('sku', 'pur_number'))) {
                    $condition["$key LIKE"] = $value . '%';
                } else {
                    $condition[$key] = $value;
                }
            }

        }

        // 4. 查询数据
        $joinCondition = array(
            array(
                "{$this->smark_table_name} m", "{$this->table_name}.id=m.check_id", 'left',
            ),
            array("{$this->supplier_table_name} s", "{$this->table_name}.supplier_code=s.supplier_code", 'left'),
            array("{$this->supplier_buyer_table_name} b", "{$this->table_name}.supplier_code=b.supplier_code and {$this->table_name}.group_id=b.buyer_type", 'left'),
            array("{$this->supplier_sku_table_name} sk", "{$this->table_name}.id=sk.check_id", 'left'),
        );

        //查询字段
        $fields = 'buyer_name,' . $this->table_name . '.create_time,pur_number,check_times,expect_time,confirm_time,report_time,
        ' . $this->table_name . '.check_type,s.supplier_name,contact_address,contact_person,phone_number,judgment_results,
        evaluate,improvement_measure,times,check_reason,check_price';

        //order by
        $orderBy = $this->table_name . '.id desc';

        //数据查询
        $result = $this->getDataListByJoin($condition, $fields, $orderBy, 0, 1000, "{$this->table_name}.id", $joinCondition);
        //echo $this->db->last_query();die;
        return array(
            'list' => $result['data'],
        );

    }

    /**
     * @desc 供应商验货验厂单条数据根据ID
     * @author Jackson
     * @param array $id 参数
     * @return array
     */
    public function check_by_id($id)
    {
        //查询条件
        $condition = array();
        if ($id) {
            $condition[$this->table_name . '.id'] = $id;
        }

        //查询字段
        $fields = $this->table_name . '.check_type,group_id,expect_time,supplier_name,' . $this->table_name . '.supplier_code,
        check_times,is_urgent,contact_person,phone_number,contact_address,check_note,order_type,pur_number';

        // 4. 查询数据
        $joinCondition = array(
            array(
                "{$this->smark_table_name} m", "{$this->table_name}.id=m.check_id", 'left',
            ),
            array("{$this->supplier_table_name} s", "{$this->table_name}.supplier_code=s.supplier_code", 'left'),
        );

        //order by
        $orderBy = '';

        //数据查询
        $result = $this->getDataListByJoin($condition, $fields, $orderBy, 0, 1, '', $joinCondition);

        return array(
            'list' => $result['data'],
        );
    }

    /**
     * @desc 供应商验货验厂指定数据更新根据ID
     * @author Jackson
     * @param array $id 参数
     * @return array
     */
    public function _update($id, $parames = array())
    {

        //条件
        $condition = array();
        if ($id) {
            $condition['id'] = $id;
        } else {
            return array(false, 'id不能为空');
        }

        // 1.验证字段
        list($status, $msg) = $this->validateParams($parames);
        if (!$status) {
            return array(false, $msg);
        }

        //增加用户信息
        $parames['modify_user_name'] = '';//用户名
        analyzeUserInfo($parames);
        $parames['modify_time'] = date("Y-m-d H:i:s");

        // 2.过滤非数据库字段
        $parames = $this->filterNotExistFields($parames);

        //3.更新数据
        $this->update($parames, $condition);
        return $this->getAffectedRows() > 0 ? array(true, '修改成功') : array(false, "修改失败:" . $this->getWriteDBError());

    }

}