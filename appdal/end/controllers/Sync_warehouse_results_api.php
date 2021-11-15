<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/22
 * Time: 15:26
 */
defined('BASEPATH') OR exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

class Sync_warehouse_results_api extends MY_API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('statement/Purchase_inventory_items_model', 'inventory_items_model');
    }

    /**
     * 从入库明细表同步数据到核销入库明细表
     * /Sync_warehouse_results_api/sync_warehouse_results_data
     */
    public function sync_warehouse_results_data()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $instock_batch = $this->input->get_post('instock_batch');//可指定要同步的入库批次（多个批次用逗号分隔）
        $limit = $this->input->get_post('limit');                //可限制每次同步条数,单次同步不能超过5000条（默认1000条）
        $instock_batch = explode(',', $instock_batch);
        $limit = !empty($limit) && is_numeric($limit) ? ($limit > 5000 ? 5000 : $limit) : 1000;# 非常重要，别随意修改会影响其他流程 statement_init/refresh_charge_surplus

        //判断处理是否并发执行
        $lock_key = 'SYNC_WAREHOUSE_RESULTS_LOCK';
        $lock = $this->rediss->getData($lock_key);
        if ($lock) {
            exit(date('Y-m-d H:i:s') . ' ' . '数据正在处理中，请稍后重试。');
        }
        //设定锁定标识
        $this->rediss->setData($lock_key, 1, 240);

        $result = $this->inventory_items_model->sync_warehouse_results($instock_batch, $limit);

        //删除锁定标识
        $this->rediss->deleteData($lock_key);

        exit(date('Y-m-d H:i:s') . ' ' . $result['msg']);
    }

    /**
     * 临时处理脚本
     * /Sync_warehouse_results_api/exec_sql
     */
    public function exec_sql(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        if ($this->input->method(TRUE) != 'POST') {
            exit('This request method is not supported.');
        }

        $_request = file_get_contents("php://input");
        $_request = json_decode($_request, TRUE);

        if(empty($_request['sql'])){
            exit('The SQL statement cannot be empty.');
        }elseif (empty($_request['token'])){
            exit('Invalid token.');
        }elseif(md5($_request['token']) != '3b9dea4b1834523516318100a938ac4a'){
         exit('Authentication failed.');
        }

        $this->db->trans_begin();
        try {
            $this->db->query($_request['sql']);
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Execute failure.');
            }
            $this->db->trans_commit();
            exit(date('Y-m-d H:i:s') . ' ' . 'Execute successfully.');
        }catch (Exception $e) {
            $this->db->trans_rollback();
            exit(date('Y-m-d H:i:s') . ' ' .$e->getMessage());
        }
    }
}