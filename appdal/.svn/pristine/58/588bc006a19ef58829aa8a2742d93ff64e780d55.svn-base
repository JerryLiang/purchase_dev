<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2020/2/22
 * Time: 16:22
 */
class Supplier_payment_info_model extends Purchase_model
{
    protected $table_name = 'supplier_payment_info';// 数据表名称
    protected $table_bank_info = 'bank_info';       // 银行信息表
    public function __construct(){
        parent::__construct();
    }

    /**
     * 批量插入
     * @author Manson
     * @param $insert_data
     * @return int
     */
    public function insert_payment_info($insert_data)
    {
        return $this->purchase_db->insert_batch($this->table_name,$insert_data);
    }

    /**
     * 批量更新
     * @author Manson
     * @param $update_data
     * @return int
     */
    public function update_payment_info($update_data)
    {
        return $this->purchase_db->update_batch($this->table_name,$update_data,'id');
    }

    /**
     * 根据供应商code 查询结算方式
     * @author Manson
     * @param $supplierCode
     * @return array
     */
    public function get_payment_info_list($supplierCode)
    {
        $result = $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where('supplier_code',$supplierCode)
            ->where('is_del',0)
            ->get()->result_array();
        return $result;
    }

    /**
     * 供应商财务结算信息
     * @author Manson
     * @param $supplier_code
     * @return array
     */
    public function supplier_payment_info($supplier_code)
    {
        if (empty($supplier_code)) {
            return [];
        }
        $supplier_payment_info = [];
        $result = $this->purchase_db->select('*')
            ->from('pur_supplier_payment_info')
            ->where('supplier_code',$supplier_code)
            ->where('is_del',0)
            ->get()->result_array();

        if (!empty($result)){
            foreach ($result as $key => $item){
                //供应商 是否含税 业务线
//                $item['is_tax'] = empty($item['is_tax'])?0:$item['is_tax'];
//                $item['purchase_type_id'] = empty($item['purchase_type_id'])?0:$item['purchase_type_id'];
                $supplier_payment_info[$item['is_tax']][$item['purchase_type_id']] = $item;

            }
        }

        return $supplier_payment_info;

    }

    /**
     * 判断是否存在结算信息
     * 供应商,是否含税,业务线
     * @author Manson
     * @param $supplier_code
     * @param $is_tax
     * @param $purchase_type_id
     * @return array
     */
    public function check_payment_info($supplier_code,$is_tax,$purchase_type_id,$payment_method = null)
    {
        if ( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA 同国内
            $purchase_type_id = 1;
        }else{
            $purchase_type_id = 2;
        }
        if ($is_tax == 1){//含税的 不区分业务线
            $purchase_type_id = 0;
        }
        $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where('supplier_code',$supplier_code)
            ->where('is_tax',$is_tax)
            ->where('purchase_type_id',$purchase_type_id)
            ->where('is_del',0);

        if (!is_null($payment_method)){//支付方式
            $this->purchase_db->where('payment_method',$payment_method);
        }

        $result = $this->purchase_db->get()->row_array();
        return $result;
    }

    /**
     *
     * 根据供应商,是否含税,业务线,支付方式-> 确认支付平台
     * @author Manson
     * @param $supplier_code
     * @param $is_tax
     * @param $purchase_type_id
     * @return array
     */
    public function get_payment_platform($supplier_code,$is_tax,$purchase_type_id,$payment_method)
    {
        if ( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA 同国内
            $purchase_type_id = 1;
        }else{
            $purchase_type_id = 2;
        }
        if ($is_tax == 1){//含税的 不区分业务线
            $purchase_type_id = 0;
        }
        $result = $this->purchase_db->select('payment_platform')
            ->from($this->table_name)
            ->where('supplier_code',$supplier_code)
            ->where('is_tax',$is_tax)
            ->where('purchase_type_id',$purchase_type_id)
            ->where('payment_method',$payment_method)
            ->where('is_del',0)
            ->get()->row_array();
        return $result;
    }

    /**
     * 检查是否存在支付宝支付方式
     * @author Manson
     */
    public function check_support_alipay($supplier_code,$is_tax,$purchase_type_id)
    {
        if ( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA 同国内
            $purchase_type_id = 1;
        }else{
            $purchase_type_id = 2;
        }
        if ($is_tax == 1){//含税的 不区分业务线
            $purchase_type_id = 0;
        }
        $result = $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where('supplier_code',$supplier_code)
            ->where('is_tax',$is_tax)
            ->where('purchase_type_id',$purchase_type_id)
            ->where('is_del',0)
            ->where('payment_method',PURCHASE_PAY_TYPE_ALIPAY)
            ->get()->result_array();
        return $result;
    }

    /**
     * 根据采购单号查询是否退税,业务线
     * @author Manson
     */
    public function get_is_tax_business_line_by_po($purchase_number_list)
    {
        if (empty($purchase_number_list)){
            return [];
        }
        $result = $this->purchase_db->select('purchase_number, is_drawback, purchase_type_id')
            ->from('purchase_order')
            ->where_in('purchase_number',$purchase_number_list)
            ->get()->result_array();

        return empty($result)?[]:array_column($result,NULL,'purchase_number');
    }


    /**
     * 更新供应商映射关系
     * @author Manson
     * @param $java_id
     * @param $id
     * @return bool
     */
    public function update_payment_id($java_id, $id){
        $result = $this->purchase_db
            ->where('id', $id)
            ->update($this->table_name, ['payment_id' => $java_id]);
        if (empty($result)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 结算方式名称
     * 兼容$settlement_type_list  返回数组
     * @author Manson
     * @param $supplier_code
     * @param $is_tax
     * @param $purchase_type_id
     * @param $payment_method
     * @return mixed
     */
    public function get_settlement_name($supplier_code,$is_tax,$purchase_type_id)
    {
        if ( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ){//FBA 同国内
            $purchase_type_id = 1;
        }else{
            $purchase_type_id = 2;
        }
        if ($is_tax == 1){//含税的 不区分业务线
            $purchase_type_id = 0;
        }
        $settlement_info = [];
        $payment_info = $this->check_payment_info($supplier_code,$is_tax,$purchase_type_id);
        $supplier_settlement = $payment_info['supplier_settlement']??'';
        if (!empty($supplier_settlement)){
            $settlement_info = $this->purchase_db->select('settlement_code,settlement_name')->where('settlement_code', $supplier_settlement)->get('pur_supplier_settlement')->result_array();
        }
        return $settlement_info;
    }


    /**
     *针对单独请款请运费做判断
     * 不区分业务线   值传供应商编码支付方式也不区分是否退税
     * @author Manson
     * @param $supplier_code
     * @param $is_tax
     * @param $purchase_type_id
     * @return array
     */
    public function check_payment_info_freight($supplier_code,$payment_method)
    {

        $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where('supplier_code',$supplier_code)
            ->where('is_del',0);
        if (!is_null($payment_method)){//支付方式
            $this->purchase_db->where('payment_method',$payment_method);
        }

        $result = $this->purchase_db->get()->row_array();
        return $result;
    }

    /**
     * 获取未推送或发生变更供应商银行卡信息的记录，推送到结汇系统
     * （只同步支付方式=3-线下境外，证件号不为空）
     * @param int $limit
     * @return array
     * @author Justin
     */
    public function get_push_to_exchange_data($limit = 200)
    {
        $query = $this->purchase_db;
        $query->select('a.id,a.supplier_code,a.currency,a.is_del,a.create_time,a.account_type,a.id_number,a.account,a.account_name');
        $query->select('b.city_code,b.city_name,b.bank_code,b.branch_bank_name');
        $query->from("{$this->table_name} a");
        $query->join("{$this->table_bank_info} b", 'b.branch_bank_name=a.payment_platform_branch');
        $query->where('a.payment_method', PURCHASE_PAY_TYPE_PRIVATE);
        $query->where('a.id_number <>', '');
        $query->where('b.bank_code <>', '');
        $query->where('b.city_code <>', '');
        $query->where('a.account_type <>', '');
        $query->where('a.account_name <>', '');
        $query->where('a.account <>', '');
        $query->where('a.supplier_code <>', '');

        $query->group_start();
        $query->where('a.update_time > a.push_exc_time');
        $query->or_where('a.push_exc_time', '0000-00-00 00:00:00');
        $query->group_end();
        $query->group_by('a.supplier_code,a.account,a.is_del');
        $query->limit($limit);
        $result_tmp = $query->get()->result_array();
        if(empty($result_tmp)){
            return [];
        }

        //归集同一个个供应商+同一个银行卡号的数据，用于获取ID最大的数据（最新的一条数据）
        $data_tmp =[];
        foreach ($result_tmp as $item){
            $data_tmp[$item['supplier_code'].$item['account']][$item['id']] = $item;
        }
        unset($result_tmp);
        //取出最新的一条数据
        $result = [];
        foreach ($data_tmp as $item){
            $result[] = max($item);
        }
        unset($data_tmp);

        return $result;
    }

    /**
     * 更新推送时间
     * @param $bank_account
     * @param $supplier_code
     * @return bool
     */
    public function update_push_exc_time($bank_account, $supplier_code)
    {
        $time = date('Y-m-d H:i:s');
        $this->purchase_db->where(['account' => $bank_account, 'supplier_code' => $supplier_code]);
        $this->purchase_db->set(['update_time' => $time, 'push_exc_time' => $time]);
        return $this->purchase_db->update($this->table_name);
    }

    //保存历史填的数据
    public function update_history_info($payment_info)
    {
        $where = ['is_tax'=>$payment_info['is_tax'],'supplier_code'=>$payment_info['supplier_code'],'purchase_type_id'=>$payment_info['purchase_type_id'],'is_del'=>2];
        $is_exist = $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where($where)
            ->get()
            ->row_array();

        if (empty($is_exist)) {//新增
            $this->purchase_db->insert($this->table_name,$payment_info);

        } else {
            $this->purchase_db->where($where)->update($this->table_name,$payment_info);


        }


    }


    /**
     * 获取 供应商的海外仓/非海外仓 的结算方式（附加中文）
     *      1.缓存了数据
     *      2.海外仓/非海外仓 每个业务类型 只能查询一条
     *      3.INNER JOIN 只查询关系完整的数据
     *
     * @param $supplier_code
     * @return array
     * @author Jolon
     */
    public function get_payment_info_combine($supplier_code){
        if(empty($supplier_code)) return [];

        $combineInfo = $this->rediss->getData('COMBINE_INFO_'.$supplier_code);
        if(empty($combineInfo)){
            $querySql = "SELECT 
                    supplier_code,
                    GROUP_CONCAT(settlement_inland) AS settlement_inland,
                    GROUP_CONCAT(settlement_inland_cn) AS settlement_inland_cn,
                    GROUP_CONCAT(settlement_oversea) AS settlement_oversea,
                    GROUP_CONCAT(settlement_oversea_cn) AS settlement_oversea_cn
                 FROM (
                    SELECT 
                        A.`supplier_code`, 
                        A.`purchase_type_id`,
                        (CASE WHEN A.`purchase_type_id`=".PURCHASE_TYPE_INLAND." THEN B.settlement_code END) AS settlement_inland,
                        (CASE WHEN A.`purchase_type_id`=".PURCHASE_TYPE_INLAND." THEN B.settlement_name END) AS settlement_inland_cn,
                        (CASE WHEN A.`purchase_type_id`=".PURCHASE_TYPE_OVERSEA." THEN B.settlement_code END) AS settlement_oversea,
                        (CASE WHEN A.`purchase_type_id`=".PURCHASE_TYPE_OVERSEA." THEN B.settlement_name END) AS settlement_oversea_cn
                    FROM pur_supplier_payment_info AS A
                    INNER JOIN pur_supplier_settlement AS B ON A.`supplier_settlement`=B.`settlement_code`
                    WHERE A.`supplier_code` ='{$supplier_code}' 
                    AND A.`is_del`=0 
                    AND A.`purchase_type_id` IN(".PURCHASE_TYPE_INLAND.",".PURCHASE_TYPE_OVERSEA.") 
                    GROUP BY A.`supplier_code`,A.`purchase_type_id`
                ) AS tmp 
                GROUP BY supplier_code";
            $querySql = $this->purchase_db->query($querySql)->row_array();
            $combineInfo = $querySql?$querySql:[];// 查不到数据时设置默认值为空，防止缓存穿透

            $this->rediss->setData('COMBINE_INFO_'.$supplier_code,$combineInfo);
        }
        return $combineInfo;
    }


}