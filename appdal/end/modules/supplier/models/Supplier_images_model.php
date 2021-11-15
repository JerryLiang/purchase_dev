<?php

/**
 * Created by PhpStorm.
 * 供应商 图片
 * User: Jolon
 * Date: 2019/01/09 0029 11:50
 */
class Supplier_images_model extends Purchase_model
{
    protected $table_name = 'supplier_images';// 数据表名称

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
     * 获取 供应商 指定的一张图片
     * @author Jolon
     * @param string $supplier_code 供应商编码
     * @param int $image_type 图片类型（busine_licen.营业执照,verify_book.一般纳税人认定书,bank_information.开票资料,receipt_entrust_book.收款委托书）
     * @return array|bool
     */
    public function get_image_one($supplier_code, $image_type = null)
    {
        if (empty($supplier_code)) return false;

        $where = ['supplier_code' => $supplier_code];
        if ($image_type !== null) {
            $where['image_type'] = $image_type;
        }

        $row = $this->purchase_db->where($where)
            ->get($this->table_name)
            ->row_array();

        return $row;
    }


    /**
     * 获取 供应商所有图片
     * @author Jolon
     * @param string $supplier_code 供应商编码
     * @param int $image_status 图片状态（1.正常,2.历史图片,3 图片删除）
     * @param string $image_type 图片类型（busine_licen.营业执照,verify_book.一般纳税人认定书,bank_information.开票资料,receipt_entrust_book.收款委托书）
     * @return array|bool
     */
    public function get_image_list($supplier_code, $image_status = 1, $image_type = null)
    {
        if (empty($supplier_code)) return false;
        $where = ['supplier_code' => $supplier_code];
        if ($image_status) {
            $where['image_status'] = $image_status;
        }
        if ($image_type) {
            $where['image_type'] = $image_type;
        }
        /**
           默认选择没有删除图片信息
         **/
        $list = $this->purchase_db->where($where)->where('image_status!=',3)
            ->get($this->table_name)
            ->result_array();
        $list_tmp = [];
        foreach($list as $key => $value){
            $list_tmp[$key] = $value;
            if($value['image_url'] and stripos($value['image_url'],';') !== false){
                $image_url = explode(';',$value['image_url']);
                $list_tmp[$key]['image_url'] = $image_url;
            }
        }


        return $list_tmp;
    }

    /**
     * @desc 更新数据
     * @author Jackson
     * @Date 2019-01-22 17:01:00
     * @return array()
     **/
    public function update_supplier_image(array $parames, $supplier_code='',$update_history = true)
    {
        //更新条件
        $condition = [];
        if (!empty($parames)) {

            //更新数据
            $data = $parames;
            if (isset($data['id']) && $data['id']) {
                $condition['id'] = $data['id'];
            } else {
                $condition['supplier_code'] = $data['supplier_code'];
            }
            $condition['image_type'] = $parames['image_type'];
            if(!$update_history) $condition['image_status'] = 1;
            //查检数据是否存在，存在更新，反之插入
            $checkData = $this->checkDataExsit($condition);
            unset($data['id']);

            //过虑不存在字段
            $data = $this->filterNotExistFields($data);

            if (empty($checkData)) {

                $result = $this->insert($data);
                //记录日志
                if ($result) {
                    // 插入表改变日志信息
                    tableChangeLogInsert(
                        ['record_number' => $this->getLastInsertID(),
                            'table_name' => 'pur_' . $this->table_name,
                            'change_type' => 1,
                            'content' => $data
                        ]);
                    return array(true, "更新成功");
                }else{
                    return array(false, "操作失败:" . $this->getWriteDBError());
                }
            } else {

                //获取更新前的数据
                $updateBefore = $this->getDataByCondition($condition);

                $result = $this->update($data, $condition);
                //记录日志
                if ($result) {
                    $ids = array_column($updateBefore, 'id');
                    if (is_array($ids)) {
                        //解析更新前后数据
                        $changDatas = $this->checkChangData($updateBefore, $parames);
                        if (!empty($changDatas)) {
                            foreach ($ids as $key => $_id) {
                                if(!isset($changDatas[$_id])) continue;
                                operatorLogInsert(
                                    [
                                        'id' => $_id,
                                        'type' => 'pur_' . $this->table_name,
                                        'content' => '供应商图片资料信息更新',
                                        'detail' => $changDatas[$_id],
                                        'ext'=> $supplier_code
                                    ]);
                                tableChangeLogInsert(
                                    ['record_number' => $_id,
                                        'table_name' => 'pur_' . $this->table_name,
                                        'change_type' => 2,
                                        'change_content' => $changDatas[$_id],
                                    ]);
                            }
                        }
                    }

                    return array(true, "更新成功");
                }else{
                    return array(false, "操作失败:" . $this->getWriteDBError());
                }
            }
        }
        return array(true, "数据为空");
    }

}