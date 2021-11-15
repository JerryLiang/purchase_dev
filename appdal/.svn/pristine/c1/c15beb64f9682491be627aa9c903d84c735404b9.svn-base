<?php
/**
 * Created by PhpStorm.
 * 采购需求数据库模型类
 * User: Jeff
 * Date: 2019/05/05 11:23
 */

class Suggest_expiration_set_model extends Purchase_model
{

    protected $table_name = 'suggest_expiration_set';// 数据表名称

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @desc 获取过期时间配置列表
     * @author Jeff
     * @Date 2019/5/5 11:52
     * @param $params
     * @return
     */
    public function get_list()
    {
        $list=$this->purchase_db->select('*')
            ->from($this->table_name)
            ->get()
            ->result_array();
        return $list;
    }

    /**
     * @desc 编辑过期时间
     * @author Jeff
     * @Date 2019/5/5 13:38
     * @param $id
     * @param $expiration
     * @return
     */
    public function edit_expiration($id, $expiration, $remark)
    {
        $return = ['code'=>false,'msg'=>''];

        $update_data = [
            'id' => $id,
            'expiration_time' => '2099-12-30 23:59:59',
            'remark' => $remark,
            'modify_user_name' => getActiveUserName(),
            'modify_time' => date("Y-m-d H:i:s",time()),
        ];

        $this->purchase_db->trans_begin();
        try{
            $row=$this->purchase_db->select('*')
                ->from($this->table_name)
                ->where('id',$id)
                ->get()
                ->row_array();

            if (empty($row)) throw new Exception('数据不存在');

            //更新过期时间表
            $res = $this->purchase_db->update($this->table_name,$update_data,['id'=>$id]);

            if (!$res) throw new Exception('更新过期时间失败');

            //插入操作记录表
            operatorLogInsert(
                ['id'      => $id,
                 'type'    => $this->table_name,
                 'content' => '修改过期时间',
                 'detail'  => '将过期时间从['.$row['expiration_time'].']改为['.$expiration.']'
                ]);

            $this->purchase_db->trans_commit();
            $return['code']=true;

        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $return['msg']=$e->getMessage();
        }

        return $return;
    }

    /**
     * @desc 根据业务线id获取过期时间记录
     * @author Jeff
     * @Date 2019/5/5 17:36
     * @param $purchase_type_id
     * @return
     */
    public function get_one_by_type_id($purchase_type_id)
    {
        $row=$this->purchase_db->select('*')
            ->from($this->table_name)
            ->where('purchase_type_id',$purchase_type_id)
            ->get()
            ->row_array();
        return $row;
    }
}