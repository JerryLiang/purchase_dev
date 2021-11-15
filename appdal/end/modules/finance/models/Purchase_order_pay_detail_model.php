<?php

/**
 * Created by PhpStorm.
 * 请款单明细
 * User: Jolon
 * Date: 2019/01/10 0027 11:23
 */
class Purchase_order_pay_detail_model extends Purchase_model
{

    protected $table_name = 'purchase_order_pay_detail';

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @desc 根据选择的ID获取所有付款记录
     * @author Jackson
     * @parames array $parames 查询条件
     * @parames string $field 返回字段
     * @Date 2019-02-12 18:01:00
     * @return array()
     **/
    public function findOnes($parames = array(), $field = '*')
    {

        //查询条件
        $condition = array();
        if (!empty($parames)) {
            foreach ($parames as $key => $value) {
                $condition[$key] = $value;
            }
        }

        //查询数据
        return $this->findOne($condition, $field);

    }

}