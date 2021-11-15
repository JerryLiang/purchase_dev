<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/20
 * Time: 11:43
 */
class Purchase_invoice_api_model extends Purchase_model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Purchase_order_cancel_model','m_cancel',false,'purchase');
    }


    /**
     * 计划任务 自动写入 采购数量发生变更的数据
     */
    public function add_uninvoiced_qty()
    {
        $new_data     = $this->input->get_post('new_data');
        $debug        = $this->input->get_post('debug');
        $limit        = $this->input->get_post('limit')??300;
        $operator_key = 'auto_add_uninvoiced_qty';

        // 验证 redis 里面是否还有要待处理的数据
        $len = $this->rediss->llenData($operator_key);
        if ($len <= 0) {
            // 没有数据 则自动增加待处理的数据
            $query_sql = "SELECT a.purchase_number,a.sku FROM pur_purchase_order_items a
	LEFT JOIN pur_purchase_order b ON a.purchase_number = b.purchase_number 
WHERE  a.confirm_amount != 0 AND a.uninvoiced_qty = 0 AND a.invoiced_qty = 0  AND b.source = 1  AND b.is_drawback = 1 LIMIT $limit";

            $result = $this->purchase_db->query($query_sql)->result_array();
            if ($result) {
                foreach ($result as $item) {
                    $value = $item['purchase_number'] . '$$' . $item['sku'];
                    $this->rediss->lpushData($operator_key, $value);
                }

                $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_' . $operator_key);
            } else {
                echo '没有需要执行的数据-1';
                exit;
            }
        }

        if ($new_data) {// 执行指定订单
            echo '<pre>';
            $wait_list[] = $new_data;
        } else {
            $wait_list = [];
            for ($i = 0; $i < $limit; $i++) {
                $data = $this->rediss->rpopData($operator_key);
                if (empty($data)) break;
                $wait_list[] = $data;
            }

            if (empty($wait_list)) {
                echo '没有需要执行的数据-2';
                exit;
            }
        }

        $update_info = [];
        foreach ($wait_list as $now_value) {
            $now_value                          = explode('$$', $now_value);
            $update_info[] = [
                'purchase_number'   => $now_value[0],
                'sku'   => $now_value[1],
            ];
        }
        unset($wait_list);
        $this->update_uninvoiced_qty($update_info, $debug);

        echo 'sss';
        exit;
    }

    /**
     * 更新未开票数量
     * @author Manson
     */
    public function update_uninvoiced_qty($params,$debug=null)
    {
//        pr($params);exit;
        if ($debug) print_r($params);
        foreach ($params as $item){

            if (empty($item['purchase_number']) || empty($item['sku'])){
                continue;
            }

            $qty_info = $this->m_order_item->get_qty_info($item['purchase_number'],$item['sku']);
            if (empty($qty_info)){
                if ($debug)
                    echo sprintf('查询结果为空,采购单:%s,sku:%s; ',$item['purchase_number'],$item['sku']);
                    continue;
            }

            $cancel_map = $this->m_cancel->get_cancel_qty_by_item_id([$qty_info['id']]);

            $purchase_qty = $qty_info['purchase_qty']??0;//采购数量
            $invoiced_qty = $qty_info['invoiced_qty']??0;//已开票数量
            $uninvoiced_qty = $qty_info['uninvoiced_qty']??0;//未开票数量
            if (isset($qty_info['loss_status']) && $qty_info['loss_status'] == REPORT_LOSS_STATUS_FINANCE_PASS){
                $loss_qty = $qty_info['loss_qty']??0;//报损数量
            }else{
                $loss_qty = 0;
            }
            $cancel_qty = $cancel_map[$qty_info['id']]??0;//取消数量
            $act_purchase_qty = $purchase_qty-$loss_qty-$cancel_qty;//实际采购数量
            $new_uninvoiced_qty = $act_purchase_qty - $invoiced_qty;//  [未开票数量 = 实际采购数量-已开票数量]

            if ($uninvoiced_qty != $new_uninvoiced_qty){//需要更新未开票数量

                $this->db->where('purchase_number',$item['purchase_number']);
                $this->db->where('sku',$item['sku']);
                $this->db->update('purchase_order_items', ['uninvoiced_qty'=>$new_uninvoiced_qty]);
                if ($debug) echo sprintf('采购单号:%,sku:%s,未开票数量由 %s 改为 %s;',$item['purchase_number'],$item['sku'],$uninvoiced_qty,$new_uninvoiced_qty);
                $log_data =  [
                    'id' => $qty_info['id'],
                    'content' => '未开票数量发生变更',
                    'detail' => sprintf('未开票数量由 %s 改为 %s;',$uninvoiced_qty,$new_uninvoiced_qty)
                ];
                operatorLogInsert($log_data);//记录日志
            }
        }
    }

    /**
     * 查询采购单状态
     * @author Manson
     */
    public function get_purchase_order_status($purchase_number)
    {
        if (empty($purchase_number)){
            return '';
        }
        $result = $this->purchase_db->select('purchase_number,purchase_order_status')
            ->from('purchase_order')
            ->where('purchase_number',$purchase_number)
            ->get()->row_array();

        return empty($result)?'':$result['purchase_order_status'];
    }


    /**
     * 发票清单号、发票代码(左)、发票号码(右)、采购单号、sku、合同号、供应商名称、供应商编码、含税单价、开票品名、已开票数量、开票点、票面税率、
     * @author Manson
     */
    public function get_push_to_summary_data()
    {
        $result = [];
        $result = $this->purchase_db->select('a.children_invoice_number,a.id, a.purchase_number, a.sku, a.invoice_coupon_rate, a.invoice_value,
         a.invoice_code_left, a.invoice_code_right, a.invoiced_qty, a.invoice_number,
         b.supplier_code, b.supplier_name, 
         c.unit_price, 
         d.export_cname, d.pur_ticketed_point, d.product_name,
         e.customs_code, e.product_line_id')
            ->from('pur_purchase_items_invoice_info a')
            ->join('pur_purchase_invoice_list b','a.invoice_number = b.invoice_number','left')
            ->join('pur_purchase_invoice_item c','a.invoice_number = c.invoice_number AND a.purchase_number = c.purchase_number AND a.sku = c.sku','left')
            ->join('pur_purchase_order_items d','a.purchase_number = d.purchase_number AND a.sku = d.sku','left')
            ->join('pur_product e','a.sku = e.sku','left')
            ->where('a.is_push_summary',0)//未推送
            ->where('a.audit_status',INVOICE_AUDITED)//审核通过的
            ->limit(200)
            ->group_by('a.id')
            ->order_by('a.audit_time')
            ->get()->result_array();

        if (!empty($result)){
            foreach ($result as $key => &$item){
                $this->load->model('product_line_model','product_line',false,'product');
                $first_product_line = $this->product_line->get_all_parent_category($item['product_line_id'],'asc');
                $item['first_product_line_name'] = $first_product_line[0]['product_line_name']??'';
                $item['first_product_line'] = $first_product_line[0]['product_line_id']??'';
            }
        }

        return $result;
    }

    /**
     * 调java接口 推送财务审核通过的数据
     */
    public function push_invoice_data_by_java($params){
        $data = [];
        $result = [];
        foreach ($params as $key => $item){
            $data[] = [
                'id' => $item['id'],//
                'pur_number' => $item['purchase_number'],//采购单号
                'sku' => $item['sku'],//sku
                'declare_name' => $item['product_name'],//产品名称
                'supplier_code' => $item['supplier_code'],//供应商编码
                'supplier_name' => $item['supplier_name'],//供应商名称
                'tickets_number' => $item['invoiced_qty'],//已开票数量
                'invoice_amount' => $item['invoice_value'],//开票金额
                'ticketed_point' => $item['invoice_coupon_rate'],//
                'price' => $item['unit_price'],//含税单价
                'invoice_code' => $item['invoice_code_right'],//发票代码(右)
                'customs_number' => $item['customs_code']??'',//出口海关编码
                'tax' => $item['pur_ticketed_point'],//开票点
                'invoice_co' => $item['invoice_code_left'],//发票代码(左)
                'invoice_list_no' => $item['invoice_number'],//发票清单号
                'invoice_name' => $item['export_cname'],//开票品名
                'invoice_tax' => $item['invoice_coupon_rate'],//票面税率
                'first_product_line' => $item['first_product_line'],//一级产品线id
                'first_product_line_name' => $item['first_product_line_name'],//一级产品线名称
                'inventory_number' => $item['children_invoice_number']
            ];


            unset($params[$key]);
        }
        if (empty($data)){
            throw new Exception('推送数据参数为空');
        }

        $url = getConfigItemByName('api_config', 'java_summary', 'sendSysInsertData');

        $request_params = [
            'batch_data' => json_encode($data),
        ];

        $access_taken = getOASystemAccessToken();
        $url = $url . "?access_token=" . $access_taken;
        $result = getCurlData($url, $request_params);
        $result = json_decode($result, TRUE);

        apiRequestLogInsert(
            [
                'record_type'      => 'push_invoice_data_to_summary_after_audit',
                'api_url'          => $url,
                'post_content'     => $request_params,
                'response_content' => $result,
                'create_time'      => date('Y-m-d H:i:s'),
                'status'           => $result['status']??0
            ]);

        $errorMess = '';
        $push_success = [];

        if (isset($result['status']) && $result['status'] == 1){
            if (isset($result['data']) && !empty($result['data'])) {
                foreach ($result['data'] as $val) {
                    if ($val['status'] == 1 && !empty($val['id'])) { //成功
                        $push_success[] = [
                            'id' => $val['id'],
                            'is_push_summary' => 1,
                            'push_summary_time' => date('Y-m-d H:i:s')
                        ];
                    } elseif ($val['status'] == 0) {
                        $push_fail[] = [
                            'id' => $val['id'],
                            'errorMess' => $val['errorMess'],
                        ];
                    } else {
                        throw new Exception('财务系统返回参数异常');
                    }
                }
            }else{
                throw new Exception('财务系统返回参数异常');
            }

            if (!empty($push_fail)){
                echo sprintf('推送至财务汇总系统返回推送失败的数据:{%s}',json_encode($push_fail));
            }

            return $push_success;

        }else{
            throw new Exception('推送至财务系统接口调用失败');
        }
    }

    /**
     * 更新推送状态
     * @author Manson
     * @param $push_success
     * @throws Exception
     */
    public function update_after_push_to_summary($push_success){
        if (!empty($push_success)){
            $this->purchase_db->trans_start();
            $this->purchase_db->update_batch('purchase_items_invoice_info',$push_success,'id');
            $this->purchase_db->trans_complete();
            if ($this->purchase_db->trans_status() == false){
                throw new Exception('更新状态失败');
            }else{
                echo sprintf('更新成功:{%s}',implode(',',array_column($push_success,'id')));
            }
        }
    }

    public function fix_invoice_code_is_null(){
        $sql = "SELECT a.* FROM pur_purchase_invoice_item a
LEFT JOIN pur_purchase_items_invoice_info b ON a.purchase_number = b.purchase_number AND a.sku = b.sku AND a.invoice_number = b.invoice_number
LEFT JOIN pur_purchase_invoice_list c ON a.invoice_number = c.invoice_number
WHERE b.id is NULL AND c.audit_status = 3 LIMIT 500";
        $result = $this->purchase_db->query($sql)->result_array();
        if (!empty($result)) {
            $insert_data = [];
            foreach ($result as $item) {
                $insert_data[] = [
                    'invoice_number'      => $item['invoice_number'],
                    'purchase_number'     => $item['purchase_number'],
                    'sku'                 => $item['sku'],
                    'demand_number'       => $item['demand_number'],
                    'create_user'         => '',
                    'create_time'         => date('Y-m-d H:i:s'),
                    'audit_status'        => 5,
                    'audit_user'          => '系统',
                    'audit_time'          => date('Y-m-d H:i:s'),
                    'remark'              => '修复数据'
                ];
            }
            pr($insert_data);
            return $this->purchase_db->insert_batch('pur_purchase_items_invoice_info', $insert_data);
        }else{
            return false;
        }
    }

}

