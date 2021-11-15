<?php
class Purchase_system_model extends Purchase_model
{
    protected $table_name = 'param_sets';// 数据表名称

    /**
     * 表控制在param表中的标识字段
     * @var string
     */
    protected $base_key = 'PURCHASE_ORDER_TODAY_WORK_KEY';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
    }

    /**
     * 获取列表
     */
    public function get_list($id=false)
    {
        $query = [
            "pType" => $this->base_key,
        ];
        if($id){
            $query['id'] = $id;
        }
        $data = $this->purchase_db->from($this->table_name)->where($query)->get()->result_array();

        if(!$data || count($data) == 0)return [];
        $data_temp = [];
        foreach ($data as $val){
            $row = !empty($val['pValue']) ? json_decode($val['pValue'],  true) : [];
            $data_temp[] = [
                "id"                    => $val['id'],
                "suggest_status_cn"     => isset($row['suggest_status']) ? getPurchaseStatus($row['suggest_status']): '',
                "suggest_status"        => isset($row['suggest_status']) ? $row['suggest_status']: 0,
                "track_log"             => isset($row['track_log']) ? $row['track_log']: 0,
                "add_remark"            => isset($row['add_remark']) ? $row['add_remark']: '',
                "examine_tips"          => isset($row['examine_tips']) ? $row['examine_tips']: '',
                "examine_date"          => isset($row['examine_date']) ? $row['examine_date']: '',
                "updateTime"            => $val['updateTime'],
                "user"                  => isset($row['user']) ? $row['user']: '',
                "status"                => $val['pSort'],
            ];
        }
        return $data_temp;
    }

    /**
     * 编辑/新增数据
     */
    public function save_edit_setting($params=[])
    {
        $res = ["code" => 0, "msg" => "新增/修改失败"];
        if(empty($params)){
            $res['msg'] = '新增参数不能为空！';
            return $res;
        }

        try {
            $this->purchase_db->trans_begin();
            $uid = getActiveUserId();
            $user = getActiveUserName();
            foreach ($params as $val){
                $row = [];
                $pVal = [
                    "suggest_status"        => $val['suggest_status'],
                    "track_log"             => $val['track_log'],
                    "add_remark"            => $val['add_remark'],
                    "examine_tips"          => $val['examine_tips'],
                    "examine_date"          => $val['examine_date'],
                    "uid"                   => $uid,
                    "user"                  => $user,
                ];
                $row['pValue']      = json_encode($pVal);
//                $row['pSort']       = (int)$val['status'];
                $row['updateTime']  = date("Y-m-d H:i:s");
                if(SetAndNotEmpty($val, "id", 'n') && $val['id'] > 0){
                    $this->purchase_db->where(['id' => $val['id']])->update("param_sets", $row);
                }else{
                    $row['pKey']        = "POTWK".date("ymdHms").rand(1000, 9999);
                    $row['pType']       = $this->base_key;
                    $row['createTime']  = date("Y-m-d H:i:s");
                    $this->purchase_db->insert("param_sets", $row);
                }
            }
            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('提交事务失败!');
            }
            $res['code'] = 1;
            $res['msg'] = '新增成功！';
            $this->purchase_db->trans_commit();
        }catch (Exception $exc) {
            $this->purchase_db->trans_rollback();
            $res['msg'] = $exc->getMessage();
        }
        return $res;
    }

    /**
     * 启用/禁用数据
     */
    public function on_off_setting($id, $status)
    {
        $res = ["code" => 0, "msg" => "修改失败"];
        if($this->purchase_db->where(['id' => $id])->update("param_sets", ['pSort'=> $status])){
            $res['code'] = 1;
            $res['msg'] = '修改成功！';
        }
        return $res;
    }


}