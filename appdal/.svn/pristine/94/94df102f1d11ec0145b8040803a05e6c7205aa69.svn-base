<?php

/**
 * Created by PhpStorm.
 * 供应商验货验厂
 * author: Jackson
 * Date: 2019/1/23
 */
class Check_note_model extends Purchase_model
{

    protected $table_name = 'supplier_check_note';//供货商验厂-备注

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
    public function create_marks(array $params)
    {
        // 1.验证字段
        list($status, $msg) = $this->validateParams($params);
        if (!$status) {
            return array(false, $msg);
        }

        // 2.过滤非数据库字段
        $params = $this->filterNotExistFields($params);

        // 3.保存
        $this->insert($params);
        return $this->getAffectedRows() > 0 ? array(true, '添加成功') : array(false, "添加失败:" . $this->getWriteDBError());

    }

    /**
     * @desc 供应商验货验厂(更新数据)
     * @author Jackson
     * @Date 2019-01-23
     * @return array()
     **/
    public function update_marks($id, array $params)
    {
        //条件
        $condition = array();
        if ($id) {
            $condition['check_id'] = $id;
            $params['check_id'] = $id;
        } else {
            return array(false, 'id不能为空');
        }
        // 1.验证字段
        list($status, $msg) = $this->validateParams($params, true);
        if (!$status) {
            return array(false, $msg);
        }

        //过虑字段
        unset($params['check_id']);

        // 2.过滤非数据库字段
        $params = $this->filterNotExistFields($params);

        // 3.保存
        $this->update($params, $condition);
        return $this->getAffectedRows() > 0 ? array(true, '修改成功') : array(false, "修改失败:" . $this->getWriteDBError());

    }

    /**
     * @desc 供应商验货验厂-备注信息字段验证
     * @author Jackson
     * @param array $params 参数
     * @return array = array(
     *      $status => bool 是否成功
     *      $msg    => string 错误信息
     * )
     */
    public function validateParams(array &$params, $flg = false)
    {

        //查检字段是否为空
        $reqFields = array(
            'check_id', 'supplier_code','check_type'
        );

        //判断修改还是新增
        if (!$flg) {
            array_push($reqFields, 'create_user_name');
        }

        if (!empty($reqFields)) {
            foreach ($reqFields as $key => $field) {
                if (!isset($params[$field]) || $params[$field] === '') {
                    return array(false, "供应没验货验厂备注： $field 不能为空");
                } else {
                    $params[$field] = trim($params[$field]);//去掉首尾空格
                }
            }
        }
        return array(true, 'OK');
    }

}