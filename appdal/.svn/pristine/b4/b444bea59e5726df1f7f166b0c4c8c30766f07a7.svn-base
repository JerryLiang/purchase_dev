<?php
/**
 * 对账单数据明细表操作模型
 * User: Jolon
 * Date: 2020/04/14 10:00
 */

class Purchase_statement_items_model extends Purchase_model {

    protected $table_name = 'purchase_statement_items';

    public function __construct(){
        parent::__construct();
    }

    /**
     * 验证 采购单号 是否存在 未付款的对账单
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @return bool
     */
    public function verify_exists_statement($purchase_number){
        // 未完结状态的请款单
        $statementInfo = $this->purchase_db->select('A.statement_number')
            ->from('purchase_statement AS A')
            ->join('purchase_statement_items AS B','A.statement_number=B.statement_number','INNER')
            ->where('B.purchase_number',$purchase_number)
            ->where('A.statement_pay_status != 51')
            ->where('A.status_valid=1')
            ->get()
            ->row_array();

        if($statementInfo){
            return $statementInfo['statement_number'];
        }else{
            return true;
        }
    }


}