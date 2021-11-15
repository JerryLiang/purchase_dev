<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/21
 * Time: 14:48
 */

class Purchase_statement_note_model extends Purchase_model
{
    protected $table_statement_note = 'statement_note';//核销备注信息

    // 先定义 再使用，避免类型混乱
    private $link_type_list = [
        '1' => '对账单号',// 对账单列表 - 变更日志
        '2' => '入库编号',// 采购系统 - 入库明细表 添加备注
        '3' => '退款冲销操作',// 退款冲销记录
        '4' => '入库编号（门户）',// 门户系统 - 入库明细表 添加备注
        '5' => '对账号号（催办记录）',// 催办记录
        '6' => '对账号号（催办记录）',// 催办记录（门户）
        '12' => '同款货源列表',// 同款货源列表
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('user');
    }

    /**
     * 新增备注
     * @param array|string $link_number |单据编号
     * @param int $link_type |单据类型（参照 $this->link_type_list）
     * @param string $remark 操作备注信息
     * @param string $operation_type 操作类型
     * @param string $user_id 操作用户ID
     * @param string $user_name 操作用户名称
     * @param string $create_time 创建时间
     * @return array
     */
    public function add_remark($link_number, int $link_type, string $remark, string $operation_type = '',$user_id = null,$user_name = null,$create_time = null)
    {
        if(!in_array($link_type,array_keys($this->link_type_list))){
            return ['msg' => '单据类型未定义', 'flag' => false];
        }
        if (!is_array($link_number)) {
            $link_number = [$link_number];
        }
        if (empty($link_number) OR empty($link_type) OR empty($remark)) {
            return ['msg' => '请求参数不全', 'flag' => false];
        }
        $query = $this->purchase_db;
        try {
            $time = date('Y-m-d H:i:s');

            $insert_data = [];
            foreach ($link_number as $id) {
                $insert_data[] = [
                    'link_number' => $id,
                    'link_type' => $link_type,
                    'create_user_id' => !is_null($user_id)?$user_id:getActiveUserId(),
                    'create_user_name' => !is_null($user_name)?$user_name:getActiveUserName(),
                    'note' => $remark,
                    'create_time' => !is_null($create_time)?$create_time:$time,
                    'operation_type' => $operation_type
                ];
            }
            if (empty($insert_data)) {
                throw new Exception('添加备注数据为空');
            }

            $res = $query->insert_batch($this->table_statement_note, $insert_data);
            if ($res === FALSE) {
                throw new Exception('添加失败');
            }
            $result = ['msg' => '添加成功', 'flag' => true];

        } catch (Exception $e) {
            $result = ['msg' => $e->getMessage(), 'flag' => false];
        }
        return $result;
    }


    /**
     * 获取 操作备注信息列表
     * @param string $link_number  |单据编号
     * @param int   $link_type  |单据类型（参照 $this->link_type_list）
     * @return array
     */
    public function get_remark_list(string $link_number, int $link_type){

        $remark_list = $this->purchase_db->where('link_number',$link_number)
            ->where('link_type',$link_type)
            ->order_by('id DESC')
            ->get($this->table_statement_note)
            ->result_array();

        return $remark_list;
    }

}