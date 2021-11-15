<?php
/**
 * Created by PhpStorm.
 * 合同单 文件资料信息
 * User: Jolon
 * Date: 2019/11/08 0027 11:23
 */

class Compact_file_model extends Purchase_model {

    protected $table_name = 'purchase_compact_file';

    /**
     * Compact_model constructor.
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * 插入一个文件资料到数据库
     * @param array  $add_data  文件资料
     * @param string $file_type 文件类型
     * @return bool
     */
    public function file_insert_one($add_data, $file_type = null){
        if(empty($file_type)){
            $file_type = isset($add_data['file_type']) ? $add_data['file_type'] : 0;
        }

        $insert_data = [
            'file_name'        => isset($add_data['file_name']) ? $add_data['file_name'] : md5(time()),// 为空则自动生成文件名，防止数据缺失
            'file_path'        => isset($add_data['file_path']) ? $add_data['file_path'] : '',
            'pop_id'           => isset($add_data['pop_id']) ? $add_data['pop_id'] : '',
            'pc_id'            => isset($add_data['pc_id']) ? $add_data['pc_id'] : '',
            'upload_user_id'   => getActiveUserId(),
            'upload_user_name' => getActiveUserName(),
            'upload_time'      => date('Y-m-d H:i:s'),
            'file_type'        => $file_type,
        ];

        return $this->purchase_db->insert($this->table_name, $insert_data);
    }

    /**
     * 获取 文件资料信息列表（非原始合同扫描件）
     * @param $pop_id
     * @param $pc_id
     * @return array|bool
     */
    public function see_compact_file($pop_id = null, $pc_id){
        $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where('pc_id', $pc_id);

        if(is_null($pop_id)){// 查找合同所有的非原始合同扫描件
            $this->purchase_db->where('pop_id !=', '-1');
        }else{// 查找请款单号对应的合同扫描件
            $this->purchase_db->where('pop_id', $pop_id);
        }

        $file_list = $this->purchase_db->order_by('upload_time', 'DESC')
            ->get()
            ->result_array();

        if($file_list){
            return $file_list;
        }else{
            return [];
        }
    }

    /**
     * 获取 合同上传扫描件（原始合同扫描件 一个合同只有一个原始合同扫描件）
     * @param string $pc_id 合同ID
     * @return array|bool
     */
    public function see_compact_scanning_file($pc_id){
        $file_list = $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where('pc_id', intval($pc_id))
            ->where('pop_id', intval('-1'))
            ->order_by('upload_time', 'DESC')
            ->get()
            ->row_array();
        if($file_list){
            return $file_list;
        }else{
            return [];
        }
    }

    /**
     * 根据 合同编号 获取合同扫描件
     * @param string $compact_number
     * @return array
     */
    public function get_contract_number_file_row($compact_number =''){
        $file_list=$this->purchase_db->select('cf.file_path')
            ->from('purchase_order_pay ppo')
            ->join('purchase_compact_file cf','ppo.id=cf.pop_id')
            ->where(['ppo.pur_number'=>$compact_number])
            ->order_by('upload_time','DESC')
            ->get()
            ->result_array();
        return !empty($file_list)?$file_list:[];
    }

    /**
     * 根据 合同编号 获取合同扫描件
     * @param string $compact_number
     * @return string
     */
    public function get_contract_number_file($compact_number = ''){
        $file_list = $this->purchase_db->select('ppo.pur_number,ppo.requisition_number,ppc.id pc_id,ppo.id pop_id,cf.file_path')
            ->from('purchase_compact ppc ')
            ->join('purchase_order_pay ppo', 'ppc.compact_number=ppo.pur_number')
            ->join($this->table_name.' cf', 'cf.pc_id=ppc.id and ppo.id=cf.pop_id')
            ->where(['ppo.pur_number' => $compact_number])
            ->order_by('upload_time', 'DESC')
            ->get()
            ->row_array();
        return !empty($file_list)?$file_list['file_path']:'';
    }

}