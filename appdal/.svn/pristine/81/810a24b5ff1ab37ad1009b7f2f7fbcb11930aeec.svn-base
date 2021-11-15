<?php
class Operation_log extends LogAbstract {

  //  private $collection_name = ''; //表名
    private $operator = 0;    //操作人
    private $operate_time = ''; //操作时间
    private $key_id = '';   //重要识别ID,如订单号,采购单号或自增主键等
    private $change_details = []; //修改细节
    private $operation_level = 0; //该操作的严重程度
    private $operation_type = ''; //操作类型,分add,del,edit
    private $operation_child_type = '';
    private $hanle_in_type = '';   //操作入口方式,如(手动,自动,列表页按键等)
    private $customed_info = [];  //自定义参数
    private $operation_status = ''; // 初始化状态值:init_status,进行中状态值:pending,成功:success,失败:failure
    private $meno = '';             //说明，比如批量插入时写上批量插入N条数据


    public function addLog($collection_name = '',$data =[]){
        return $this->addByMongo($collection_name,$data);
    }

    public function listLog($collection = '', $where = [],$page = 1,$pageSize = 20)
    {
        return parent::listByPage($collection,$where,$page,$pageSize);
    }



}
