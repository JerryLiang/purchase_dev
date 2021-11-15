<?php

/**
 * Created by PhpStorm.
 * 供应商 支付帐号
 * User: Jolon
 * Date: 2019/01/09 0029 11:50
 */
class Supplier_payment_account_model extends Purchase_model
{
    protected $table_name = 'supplier_payment_account';// 数据表名称
    protected $table_bank_info='bank_info';

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
     * 获取 供应商 指定 的支付账号
     * @author Jolon
     * @param string $supplier_code   供应商编码
     * @param int    $payment_method  支付方式:1.支付宝,2.对公支付，3.对私支付
     * @param string $account 账户
     * @return array|bool
     */
/*    public function get_payment_one($supplier_code, $payment_method = null, $account = null)
    {
        if (empty($supplier_code)) return false;
        $where = ['supplier_code' => $supplier_code];
        if ($payment_method !== null) {
            $where['payment_method'] = $payment_method;
        }
        if ($account !== null) {
            $where['account'] = $account;
        }     
        $row = $this->purchase_db->where($where)
            ->get($this->table_name)
            ->row_array();

        return $row;
    }*/
    /**
     * 更新供应商映射关系
     * @author harvin 2019-5-21
     * @param type $productid
     * @param type $procurementid
     * @return boolean
     */
/*    public function update_payment_id($productid, $procurementid){
         $resulet = $this->purchase_db
                ->where('id', $procurementid)
                ->update($this->table_name, ['payment_id' => $productid]);
        if (empty($resulet)) {
            return false;
        } else {
            return true;
        }
    }*/
    /**
     * 获取 供应商所有 支付帐号
     * @author Jolon
     * @param string $supplier_code 供应商编码
     * @param int    $payment_method  支付方式:1.支付宝,2.对公支付，3.对私支付
     * @return array|bool
     */
/*    public function get_account_list($supplier_code, $payment_method = null)
    {
        if (empty($supplier_code)) return false;

        $where = ['supplier_code' => $supplier_code,'is_del'=>0];
        if ($payment_method !== null) {
            $where['payment_method'] = $payment_method;
        }
        $list = $this->purchase_db->where($where)
            ->get($this->table_name)
            ->result_array();

        return $list;
    }*/

    /**
     * @desc 更新数据
     * @author Jackson
     * * @param array $parames 参数
     * @Date 2019-01-22 17:01:00
     * @return mixed
     **/
/*    public function update_supplier_payment(array $parames,$supplier_code='')
    {
        $result_flag = true;// 操作结果标记
        //更新条件
        $condition = [];
        if (!empty($parames)) {
            foreach($parames as $param){// 多个 付款方式
                //更新数据
                $data = $param;
                $condition['supplier_code'] = $param['supplier_code'];
                if(isset($param['id'])){
                    $condition['id'] = $param['id'];
                }elseif(isset($param['payment_method'])){
                    $condition['payment_method'] = $param['payment_method'];
                }
                //查检数据是否存在，存在更新，反之插入
                $checkData = $this->checkDataExsit($condition);
                unset($data['id']);
                //增加用户信息
                $data['modify_user_name'] = '';//用户名
                analyzeUserInfo($data);
                $data['modify_time'] = date("Y-m-d H:i:s");
        
     
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
                    }

                    if(empty($result)){
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

                            //删除不必要字段
                            $delFilter = array('create_user_name', 'create_time', 'modify_time', 'modify_user_name');
                            foreach ($param as $key => $val) {
                                if (in_array($key, $delFilter)) {
                                    unset($param[$key]);
                                }
                            }

                            //解析更新前后数据
                            $changDatas = $this->checkChangData($updateBefore, $param, $delFilter);
                            if (!empty($changDatas)) {
                                foreach ($ids as $key => $_id) {

                                    operatorLogInsert(
                                        [
                                            'id' => $_id,
                                            'type' => 'pur_' . $this->table_name,
                                            'content' => '供应商支付帐号信息更新',
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

                    }
                 
//                    if($this->getAffectedRows() <= 0){
//                         
//                        return array(false, "操作失败:" . $this->getWriteDBError());
//                    }
                }
            }
        }
      
        return array($result_flag,'操作成功');
    }*/
   
    /**
     * @desc 删除记录
     * @author Jackson
     * * @param array $parames 参数
     * @Date 2019-01-30 14:01:00
     * @return array()
     **/
/*    public function is_del(array $parames)
    {
        //更新条件
        $condition = [];
        if (!empty($parames)) {
            $condition['id'] = $parames['id'];
            $result = $this->update(['is_del' => 1], $condition); 
            $info =   $row = $this->purchase_db->where($condition)
            ->get($this->table_name)
            ->row_array();
            //删除日志
            if ($result) {

                operatorLogInsert(
                    [
                        'id' => $parames['id'],
                        'type' => 'pur_' . $this->table_name,
                        'content' => '供应商支付帐号信息删除',
                        'detail' => '删除ID：' . $parames['id'],
                        'ext' => $info['supplier_code'],
                    ]);

                tableChangeLogInsert(
                    ['record_number' => $parames['id'],
                        'table_name' => 'pur_' . $this->table_name,
                        'change_type' => 3,
                        'change_content' => '删除ID：' . $parames['id'],
                    ]);

            }
     
            return $this->getAffectedRows() > 0 ? array(true, "删除成功") : array(false, "操作失败:" . $this->getWriteDBError());
        }
        return array(false, "操作失败");

    }*/
    /**
     * 获取开户行信息
     * @author harvin 
     * @date 2019-4-23
     * @return array
     */
    public function get_payment_platform_bank(){
       $bank=[];
       $bank_name= $this->purchase_db
               ->select('master_bank_name')
               ->group_by('master_bank_name')
               ->get($this->table_bank_info)
               ->result_array();
        if(empty($bank_name)){
            return $bank;
        }
        foreach ($bank_name as $row) {
            $bank[$row['master_bank_name']]=$row['master_bank_name'];
        }
        return $bank;
    }
   /**
    * 获取支行信息
    * @author harvin
    * @date 2019-4-23
    * @param type $payment_platform_bank
    * @param type $payment_platform_branch
    * @param int $limit
    * @return array
    */
    public function get_payment_platform_branch($payment_platform_bank,$payment_platform_branch,$limit = 100){
         $branch=[];
         $branch_name= $this->purchase_db
               ->select('branch_bank_name')
               ->where('master_bank_name',$payment_platform_bank)
               ->like('branch_bank_name',$payment_platform_branch)    
               ->get($this->table_bank_info,$limit)
               ->result_array();
        if(empty($branch_name)){
            return $branch;
        }
        foreach ($branch_name as $row) {
            $branch[$row['branch_bank_name']]=$row['branch_bank_name'];
        } 
        return  $branch;
        
    }
}