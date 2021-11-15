<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/6
 * Time: 17:39
 */

class Purchase_invoice_api extends MY_API_Controller{
    protected $data_abnormal_check_key = 'INVOICE_IS_ABNORMAL';//开票异常状态
    protected $recal_uninvoiced_qty_key = 'recal_uninvoiced_qty';//计算未开票数量
//PUR_WEB_REDIS_EXPRESS_INVOICE_IS_ABNORMAL
    public function __construct(){
        parent::__construct();
        $this->load->model('Purchase_order_cancel_model','m_cancel',false,'purchase');
        $this->load->model('Purchase_order_items_model','m_order_item',false,'purchase');
        $this->load->model('Purchase_invoice_api_model');
    }

    /**
     * 处理开票状态是否异常
     * /purchase_invoice_api/handle_invoice_is_abnormal
     */
    public function handle_invoice_is_abnormal()
    {
        $len = $this->rediss->set_scard($this->data_abnormal_check_key);// 获取集合元素的个数

        if($len){
            $count = ($len > 100)?100:$len;
            $this->load->model('purchase/Purchase_order_model');
            $this->load->model('purchase/Declare_customs_model','m_customs');
            $_SESSION['user_name'] = '系统';// 设置默认用户，getActiveUsername会用到

            for($i = 0;$i < $count;$i ++){
                $ps_tag = $this->rediss->set_spop($this->data_abnormal_check_key);
                $current_data = current($ps_tag);
                try{
                    $ps_tag = explode('$$',$current_data);
                    $purchase_number = $ps_tag[0]??'';
                    $sku = $ps_tag[1]??'';
//                    pr($purchase_number);
//                    pr($sku);exit;
                    if (empty($purchase_number) || empty($sku)){
                        throw new  Exception(sprintf('数据异常,采购单:%s,sku:%s; ',$purchase_number,$sku));
                    }
                    //判断订单是否完结  订单完结:已作废 部分到货不等待剩余 全部到货
                    $purchase_order_status = $this->Purchase_invoice_api_model->get_purchase_order_status($purchase_number);
                    if (!in_array($purchase_order_status,[PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                        PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                        PURCHASE_ORDER_STATUS_CANCELED])){
                        echo sprintf('不属于订单完结状态,采购单:%s,sku:%s',$purchase_number,$sku);
                        continue;
                    }

                    //开票是否异常, 校验:报关数量＞入库数量，则报异常，或者不满足“实际采购数量=实际入库数量=报关数量=已开票数量”则报异常

                    $qty_info = $this->m_order_item->get_qty_info($purchase_number,$sku);
                    if (empty($qty_info)){
                        throw new  Exception(sprintf('查询结果为空,采购单:%s,sku:%s; ',$purchase_number,$sku));
                    }
                    $cancel_map = $this->m_cancel->get_cancel_qty_by_item_id([$qty_info['id']]);

                    $purchase_qty = $qty_info['purchase_qty']??0;//采购数量
                    $invoiced_qty = $qty_info['invoiced_qty']??0;//已开票数量
                    if (isset($qty_info['loss_status']) && $qty_info['loss_status'] == REPORT_LOSS_STATUS_FINANCE_PASS){
                        $loss_qty = $qty_info['loss_qty']??0;//报损数量
                    }else{
                        $loss_qty = 0;
                    }
                    $cancel_qty = $cancel_map[$qty_info['id']]??0;//取消数量
                    $act_purchase_qty = $purchase_qty-$loss_qty-$cancel_qty;//实际采购数量
                    $upselft_amount = $qty_info['upselft_amount']??0;//入库数量
                    $customs_qty = $this->m_customs->get_customs_qty($purchase_number,$sku);//报关数量
                    if ($customs_qty > $upselft_amount){//报关数量＞入库数量 是
                        $invoice_is_abnormal = INVOICE_IS_ABNORMAL_TRUE;
                    }else{//否
                        if ($act_purchase_qty == $upselft_amount && $act_purchase_qty == $customs_qty && $act_purchase_qty==$invoiced_qty){//实际采购数量=实际入库数量=报关数量=已开票数量
                            $invoice_is_abnormal = INVOICE_IS_ABNORMAL_FALSE;
                        }else{
                            $invoice_is_abnormal = INVOICE_IS_ABNORMAL_TRUE;
                        }
                    }
                    //状态发生变更
                    if ($qty_info['invoice_is_abnormal'] != $invoice_is_abnormal){
                        $this->db->where('purchase_number',$purchase_number);
                        $this->db->where('sku',$sku);
                        $this->db->update('purchase_order_items', ['invoice_is_abnormal'=>$invoice_is_abnormal]);

                        $log_data =  [
                            'id' => $qty_info['id'],
                            'content' => '开票是否异常状态变更',
                            'detail' => sprintf('开票是否异常由 %s 改为 %s;',$qty_info['invoice_is_abnormal'],$invoice_is_abnormal)
                        ];
                        if (!operatorLogInsert($log_data)){
                            throw new  Exception(sprintf('记录日志失败,采购单:%s,sku:%s; ',$purchase_number,$sku));
                        }
                    }
                }catch(Exception $e){
                    $this->rediss->set_sadd($this->data_abnormal_check_key,$current_data);// 执行失败 下次继续执行
                    echo $e->getMessage();
                }
            }
            exit('执行完毕');
        }else{
            exit("没有需要操作的数据");
        }
    }

    /**
     * 更新未开票数量
     * 未开票数量 = [未开票数量 = 实际采购数量-已开票数量]
     * 实际采购数量 【实际采购数量 = 采购数量-取消数量-报损数量】
     * /purchase_invoice_api/update_uninvoiced_qty
     */
    public function update_uninvoiced_qty()
    {
        $len = $this->rediss->set_scard($this->recal_uninvoiced_qty_key);// 获取集合元素的个数

        if($len){
            $count = ($len > 100)?100:$len;
            $this->load->model('purchase/Purchase_order_model');
            $_SESSION['user_name'] = '系统';// 设置默认用户，getActiveUsername会用到

            for($i = 0;$i < $count;$i ++){
                $ps_tag = $this->rediss->set_spop($this->recal_uninvoiced_qty_key);
                $current_data = current($ps_tag);
                try{
                    $ps_tag = explode('$$',$current_data);
                    $purchase_number = $ps_tag[0]??'';
                    $sku = $ps_tag[1]??'';
                    if (empty($purchase_number)){
                        throw new  Exception(sprintf('数据异常,标识:%s; ',$current_data));
                    }

                    if (!empty($purchase_number) && !empty($sku)){//po+sku维度
                        $res = $this->m_order_item->get_qty_info($purchase_number,$sku);
                        $qty_info[] = $res;
                        if (empty($qty_info)){
                            throw new  Exception(sprintf('查询结果为空,标识:%s,采购单:%s,sku:%s; ',$current_data,$purchase_number,$sku));
                        }
                    }

                    if (!empty($purchase_number) && empty($sku)){//po维度
                        $qty_info = $this->m_order_item->get_qty_info($purchase_number,$sku);
                        if (empty($qty_info)){
                            throw new  Exception(sprintf('查询结果为空,标识:%s,采购单:%s,sku:%s; ',$current_data,$purchase_number,$sku));
                        }
                    }
                    $items_id_list = array_column($qty_info,'id');
                    $cancel_map = $this->m_cancel->get_cancel_qty_by_item_id($items_id_list);
//pr($cancel_map);exit;
                    foreach ($qty_info as $key => $item){
                        $purchase_qty = $item['purchase_qty']??0;//采购数量
                        if (isset($item['loss_status']) && $item['loss_status'] == REPORT_LOSS_STATUS_FINANCE_PASS){
                            $loss_qty = $item['loss_qty']??0;//报损数量
                        }else{
                            $loss_qty = 0;
                        }
                        $cancel_qty = $cancel_map[$item['id']]??0;//取消数量
                        $invoiced_qty = $item['invoiced_qty']??0;//已开票数量
                        $uninvoiced_qty = $item['uninvoiced_qty']??0;//未开票数量
                        $act_purchase_qty = $purchase_qty-$loss_qty-$cancel_qty;// 【实际采购数量 = 采购数量-取消数量-报损数量】
                        $new_uninvoiced_qty = $act_purchase_qty - $invoiced_qty;//  [未开票数量 = 实际采购数量-已开票数量]

                        if ($uninvoiced_qty != $new_uninvoiced_qty){//需要更新未开票数量
                            $this->db->where('purchase_number',$item['purchase_number']);
                            $this->db->where('sku',$item['sku']);
                            $this->db->update('purchase_order_items', ['uninvoiced_qty'=>$new_uninvoiced_qty]);

                            $log_data =  [
                                'id' => $item['id'],
                                'content' => '未开票数量发生变更',
                                'detail' => sprintf('未开票数量由 %s 改为 %s;',$uninvoiced_qty,$new_uninvoiced_qty)
                            ];
                            if (!operatorLogInsert($log_data)){
                                throw new  Exception(sprintf('记录日志失败,标识:%s,采购单:%s,sku:%s; ',$current_data,$item['purchase_number'],$item['sku']));
                            }
                        }
                    }

                }catch(Exception $e){
                    $this->rediss->set_sadd($this->recal_uninvoiced_qty_key,$current_data);// 执行失败 下次继续执行
                    echo $e->getMessage();
                }
            }
            exit('执行完毕');
        }else{
            exit("没有需要操作的数据");
        }
    }

    /**
     * 定时计算未开票数量
     * 采购数量不等于0
     * 未开票数量等于0
     * 开票状态为未开票
     * /purchase_invoice_api/add_uninvoiced_qty?new_data='purchase_number$$sku'
     *
     * @author Manson
     */
    public function add_uninvoiced_qty()
    {
        $this->Purchase_invoice_api_model->add_uninvoiced_qty();
    }

    /**
     * 将财务审核通过的数据推送至汇总系统
     * /purchase_invoice_api/push_to_summary_after_audit
     * @author Manson
     */
    public function push_to_summary_after_audit()
    {
        $i = 0;
        $max_i = 3;//执行3次
        while(true){
            try{
                //获取要推送的数据
                $result = $this->Purchase_invoice_api_model->get_push_to_summary_data();
//                pr($result);exit;
                if (empty($result)){
                    echo '没有要推送的数据了';
                    break;
                }else{
                    $i++;
                }
                if ($i > $max_i){
                    break;
                }
                //调java接口推送数据
                $success_data = $this->Purchase_invoice_api_model->push_invoice_data_by_java($result);
                //更新推送状态
                $this->Purchase_invoice_api_model->update_after_push_to_summary($success_data);
            }catch(Exception $e){
                echo $e->getMessage().PHP_EOL;
            }
        }
        echo '执行完毕';

    }

    /**
     * 修复bug导致的,当用户未录入发票信息时也应当写入一条空记录
     * @author Manson
     */
    public function fix_invoice_code_is_null()
    {
        while(true){
            $result = $this->Purchase_invoice_api_model->fix_invoice_code_is_null();
            if (!$result){
                echo '执行完毕,没有可操作数据';exit;
            }
        }
    }
}