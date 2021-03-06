<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * User: Jeff
 * Date: 2019/5/10
 * Time: 16:51
 */
class Scree extends MY_API_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_product');
        $this->load->model('abnormal/product_scree_model');
        $this->load->model('product/product_model', '', false, 'product');
        $this->load->model('supplier/supplier_model', '', false, 'supplier');
        $this->load->model('user/Purchase_user_model');
    }

    public function send_http($url, $data = null)
    {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!$data) {
            return 'data is null';
        }
        if (is_array($data)) {
            $data = json_encode($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($data),
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;
    }
    /**
     * 获取SKU 货源状态
     * @param   $sku  string 产品sku
     *
     **/
    private function get_sku_supply_status($sku)
    {
        $result = $this->db->from("product")->select("supply_status")->where("sku", $sku)->get()->row_array();
        if (isset($result['supply_status'])) {
            return $result['supply_status'];
        }
        return null;
    }

    /**
     * function:推送SKU 屏蔽信息到产品系统
     *  * 2: "停产", 4: "缺货", 99: "需要起订量"
     **/
    private function scree_push_to_product($scree_datas = array())
    {

        if (empty($scree_datas)) {

            return null;
        }

        if (!empty($this->config->item('product_system'))) {

            $product_system = $this->config->item('product_system');
            $_url_push_to_push = isset($product_system['sku_scree_to_product']) ? $product_system['sku_scree_to_product'] : '';

            if (empty($_url_push_to_push)) {
                exit('产品系统地址配置信息 IP或product_scree->sku_scree_to_product 参数缺失');
            }

            $scree_list = $scree_datas['data_list'];
            $push_data_list = [];

            $publishData = []; // 推送刊登系统货源状态数据
            foreach ($scree_list as $scree) {

                $apply_code = $apply_name = null;
                if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', trim($scree['apply_user']), $arr)) {
                    $apply_code = str_replace($arr[0], '', $scree['apply_user']);
                    $apply_name = $arr[0];

                }

                $userCode = $this->Purchase_user_model->get_user_info($scree['developer_id']);
                $push_data = [];
                $push_data['sku'] = $scree['sku'];
                $push_data['supplierName'] = $scree['old_supplier_name']; // 供应商名称
                $push_data['supplierCode'] = $scree['old_supplier_code']; // 原供应CODE
                $push_data['oneMonthSales'] = $scree['days_sales_30']; // 30天销量
                $push_data['applyUser'] = $apply_name; // 申请人
                $push_data['applyCode'] = $apply_code;
                $push_data['createTime'] = $scree['apply_time']; // 申请时间
                $push_data['reason'] = $scree['apply_remark_id']; // 申请原因
                $push_data['voucher'] = $scree['chat_evidence']; // 聊天凭证
                $push_data['skuId'] = $scree['id']; // SKU ID
                $push_data['untaxedPrice'] = $scree['old_supplier_price']; // 单价
                $push_data['remark'] = $scree['apply_content'];
                $push_data['devpUser'] = $scree['developer_name']; // 开发人员姓名
                $push_data['devpCode'] = isset($userCode['staff_code']) ? $userCode['staff_code'] : null;
//                $push_data['supplyStatus']        = $this->get_sku_supply_status($scree['sku']); // SKU 货源状态
                $push_data['titleCn'] = $scree['product_name'];
                $push_data['productCategoryId'] = $scree['product_line_id'];
                $push_data['supplyStatus'] = 1;
                // 如果申请原因为停产或者需要起订量，则货源状态为停产
                if ($push_data['reason'] == 2 || $push_data['reason'] == 99) {
                    $push_data['supplyStatus'] = 2;
                }
                // 如果申请原因为缺货，则货源状态修改为断货
                if ($push_data['reason'] == 4) {
                    $push_data['supplyStatus'] = 3;
                }

                //自动更改SKU货源状态为“停产找货中”；

                if ($push_data['reason'] == 10) {

                    $push_data['supplyStatus'] = 10;
                }
                $push_data_list[] = $push_data;

                $publishData[] = [

                    'sku' => $scree['sku'],
                    'supplyStatus' => $push_data['supplyStatus'],
                    'screeid' => $scree['id'],
                ];
            }

            $push_url = $_url_push_to_push . "?access_token=" . getOASystemAccessToken();
            $response = $this->send_http($push_url, ['items' => $push_data_list]);
            // 执行推送数据
            operatorLogInsert(
                [
                    'type' => 'product_scree',
                    'content' => '推送SKU到产品系统',
                    'detail' => json_encode($push_data_list) . $response,
                ]);

            $response = json_decode($response, true);

            if (isset($response['code']) && $response['code'] == 200) {

                $skus = array_column($scree_list, "sku");
                $result = $this->db->where_in("sku", $skus)->where("status", PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM)->update("product_scree", ['is_push_erp' => 1, 'is_product' => 1]);
            }
            if (!empty($publishData)) {

                $publishskus = array_column($publishData, "screeid");
                foreach ($publishData as $publishKey => $publishValue) {

                    //货源状态发生变化，推入消息队列
                    $this->_push_rabbitmq($publishValue['sku'], '', $publishValue['supplyStatus']);
                }

                $result = $this->db->where_in("id", $publishskus)->update("product_scree", ['is_publish' => 1]);
            }
        }
    }

    /**
     * 推送 SKU 屏蔽列表数据到ERP
     * @author Jolon
     * @return mixed
     */
    public function screen_push_to_erp()
    {
        //if(CG_ENV == 'dev') return true;

        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $push_count = $success = 0;
        // 查询 未推送、待开发确认的数据（每次获取 300 条）
        $scree_list = $this->product_scree_model->get_scree_list(['is_push_erp' => 0, 'status' => PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM], 0, 300);
        $this->load->config('api_config', false, true);

        if (!empty($scree_list['data_list'])) {
            $this->scree_push_to_product($scree_list);
            $return = [
                'push_count' => count($scree_list['data_list']),
                'success' => $success,
                'result' => isset($result) ? $result : 'success',
            ];

            $this->success_json($return);
        }
    }

    /**
     * 推送 SKU 屏蔽列表数据到ERP
     * @author Jolon
     * @return mixed
     */
    public function screen_push_to_erp1()
    {
        //if(CG_ENV == 'dev') return true;

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_time = time();
        $push_count = $success = 0;
        $flag = true;
        do {
            // 查询 未推送、待开发确认的数据（每次获取 300 条）
            $scree_list = $this->product_scree_model->get_scree_list(['is_push_erp' => 0, 'status' => PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM], 0, 100);
            if ($scree_list['data_list']) {
                // 加载 URL 配置项
                $this->load->config('api_config', false, true);
                if (!empty($this->config->item('erp_system'))) {
                    $erp_system = $this->config->item('erp_system');
                    $_url_erp_ip = isset($erp_system['ip']) ? $erp_system['ip'] : '';
                    $_url_push_to_erp = isset($erp_system['product_scree']['push_to_erp']) ? $erp_system['product_scree']['push_to_erp'] : '';
                    //$_url_push_to_erp = "http://120.78.243.154/services/products/product/screenapplyfromnewprovider";
                    if (empty($_url_erp_ip) or empty($_url_push_to_erp)) {
                        exit('ERP地址配置信息 IP或product_scree->push_to_erp 参数缺失');
                    }
//                    $_url_push_to_erp = $_url_push_to_erp;
                }

                $scree_list = $scree_list['data_list'];
                $push_data_list = [];
                foreach ($scree_list as $scree) {
                    $push_data = [];
                    $push_data['sku'] = $scree['sku'];
                    $push_data['title'] = $scree['product_name']; // 商品名称
                    $push_data['create_id'] = $scree['developer_id']; // 开发人员ID

                    $push_data['provider_now'] = $scree['old_supplier_name'];
                    $push_data['price_now'] = $scree['old_supplier_price']; // 原供应商单价
                    $push_data['days_sales_30'] = $scree['days_sales_30']; // 30天销量
                    $push_data['apply_user'] = $scree['apply_user']; // 申请人
                    $push_data['apply_time'] = $scree['apply_time']; // 申请时间
                    $push_data['apply_note'] = $scree['apply_remark']; // 申请备注
                    $push_data['lasted_processing_time'] = date('Y-m-d H:i:s', strtotime($scree['apply_time']) + 86400 * 2);
                    $push_data['image_url'] = $scree['chat_evidence'];
                    $push_data['apply_content'] = $scree['apply_content'];
                    $push_data['id'] = $scree['id'];

                    $push_data_list[] = $push_data;
                }
                try {

                    // 执行推送数据
                    operatorLogInsert(
                        [
                            'type' => 'product_scree',
                            'content' => '推送SKU到ERP',
                            'detail' => json_encode($push_data_list),
                        ]);

                    $response = $this->send_http($_url_push_to_erp, ['block_list' => $push_data_list]);
                    $push_count += count($push_data_list);
                    $response = json_decode($response, true);
                    if (isset($response['code']) && $response['message'] == 'success') {
                        if (empty($response['skus'])) {
                            $result = '执行成功但 sku 错误';
                            break;
                        } else {

                            foreach ($response['skus'] as $scree_id) {
                                if ($scree_id) {
                                    $result = $this->db->where("sku", $scree_id)->where("status", PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM)->update("product_scree", ['is_push_erp' => 1]);
                                    if ($result) {
                                        operatorLogInsert(
                                            [
                                                'id' => $scree_id,
                                                'type' => 'product_scree',
                                                'content' => '推送记录成功',
                                            ]);
                                    }
                                }
                            }
                            $success += count($response['skus']);
                        }

                    } else {
                        $result = '读取返回数据出错';
                        break;

                    }
                } catch (\Exception $e) {
                    $flag = false;
                }
            } else {
                $flag = false;
            }

        } while ($flag);

        $return = [
            'push_count' => $push_count,
            'success' => $success,
            'result' => isset($result) ? $result : 'success',
        ];

        $this->success_json($return);
    }

    /**
     * 接收 EPR推送过来的 SKU屏蔽列表 操作结果
     * @author Jolon
     * @example  array $post_data
     *  1 => '待处理',
    5 => '同意',
    6 => '已驳回',
    9 => '超时自动同意',
     *
     *    "id": "3",
    "sku": "FS05696-01",
    "modify_user": "管理员",
    "modify_time": "2019-08-08 09:21:59",
    "processing_status": "7",
    "refuse_reason": ""
     * 2: "停产", 4: "缺货", 99: "需要起订量"
     *
     */
    public function screen_receive_result_from_erp()
    {
//        $post_data   =  $this->input->post();
        //
        //        $scree_list = isset($post_data['screen'])?$post_data['screen']:'';

        $scree_list = json_decode(file_get_contents('php://input'), true);

        // file_put_contents(date('Y-m-d').'接收EPR推送过来的SKU屏蔽列表.txt',$scree_list,FILE_APPEND);

        //$scree_list = json_decode($scree_list,true);
        operatorLogInsert(
            [
                'type' => 'product_scree',
                'content' => '接收开发确认结果',
                'detail' => json_encode($scree_list),
            ]);
        $success_list = $fail_list = [];
        if ($scree_list) {
            foreach ($scree_list as $list_value) {

                $scree_sku = $list_value['sku'];
                $scree_info = $this->product_scree_model->get_scree_skus_data($scree_sku, array(PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM));
                if (empty($scree_info) || ($scree_info[0]['status'] != PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM)) {

                    $fail_list[$scree_sku] = '状态不对，非[待开发确认,待采购确认]状态';
                    continue;
                }

                $result_status = isset($list_value['processing_status']) ? trim($list_value['processing_status']) : ''; // 2.替换供应商,5.同意停售,6.开发驳回,7.系统屏蔽,8.同意屏蔽,9.系统停售

                $erp_oper_user = trim($list_value['modify_user']);
                $erp_oper_time = ($list_value['modify_time']) ? trim($list_value['modify_time']) : date('Y-m-d H:i:s'); // 操作时间（默认为当前时间）

                $update_data = [
                    'erp_oper_user' => $erp_oper_user,
                    'erp_oper_time' => $erp_oper_time,
                    'sku_audit_status' => $result_status,
                ];
                $supply_status = null;
                $refuse_reason = trim($list_value['refuse_reason']); // 驳回原因

                if ($result_status == 6) { //开发驳回
                    if (empty($refuse_reason)) {
                        $fail_list[$scree_sku] = '操作结果为驳回：必填驳回原因';
                        continue;
                    }
                    $update_data['erp_remark'] = '驳回原因：' . $refuse_reason;
                    $update_data['status'] = PRODUCT_SCREE_STATUS_DEVELOP_REJECTED; // 60.已结束(开发驳回)

                } elseif (in_array($result_status, [5, 7, 8, 9])) { //同意停售
                    $update_product_data = [];
                    // 人工或者系统同意屏蔽，系统同意停售 并且原因等于断货

                    // 商品的货源状态为断货

                    if ($scree_info[0]['apply_remark'] == 4) {

                        $update_product_data = ['supply_status' => 3];
                    }
                    if ($scree_info[0]['apply_remark'] == 2 || $scree_info[0]['apply_remark'] == 99) {

                        $update_product_data = ['supply_status' => 2, 'product_status' => 7];
                    }

                    if ($scree_info[0]['apply_remark'] == 10) {

                        $update_product_data = ['supply_status' => 10];
                    }

//                    // 人工同意停售
                    //                    if( $result_status == 5 ) {
                    //
                    //                        $update_product_data = ['supply_status'=>3];
                    //                    }

                    if (!empty($update_product_data)) {
                        //$this->db->from("product")->where("sku", $scree_info[0]['sku'])->update($update_product_data);
                        $this->db->update("product", $update_product_data, ['sku' => $scree_info[0]['sku']]);
                    }
                    $update_data['status'] = PRODUCT_SCREE_STATUS_END;
                    $update_data['erp_remark'] = '';
                }
                $update_data['audit_remark'] = isset($scree_list[0]['refuse_reason']) ? $scree_list[0]['refuse_reason'] : null;
                $update_data['affirm_time'] = date("Y-m-d H:i:s", time());
                $result = $this->db->update("product_scree", $update_data, ['sku' => $scree_info[0]['sku']]);
                $success_list[] = $scree_info[0]['sku'];

            }
            if (!empty(array_unique(array_filter($success_list)))) {
                $this->product_scree_model->handle_push_to_erp($success_list);
            }
            operatorLogInsert(
                [
                    'type' => 'product_scree',
                    'content' => '接收开发确认结果采购系统返回信息',
                    'detail' => json_encode(['success_list' => $success_list, 'fail_list' => $fail_list]),
                ]);
            $this->success_json(['success_list' => $success_list, 'fail_list' => $fail_list]);
        } else {
            $this->error_json('参数【screen_list】缺失');
        }

    }

    /**
     * 供应商申请
     **/
    public function screen_receive_supplier_from_erp()
    {

        $list_value = json_decode(file_get_contents('php://input'), true);

        operatorLogInsert(
            [
                'type' => 'product_scree',
                'content' => '接受ERP 推送的供应商变更',
                'detail' => json_encode($list_value),
            ]);

        $update_data['status'] = PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM; // 30.待采购确认
        if (!isset($list_value['provider_new']) || empty($list_value['provider_new']) || !isset($list_value['price_new']) || empty($list_value['price_new'])) {
            $fail_list[$list_value['sku']] = '操作结果为替换供应商：必填新供应商和供应商报价';
        }
        // 获取供应商信息
        $supplier_info = $this->supplier_model->get_supplier_by_name($list_value['provider_new'], false);
        $update_data['new_supplier_code'] = $supplier_info ? $supplier_info['supplier_code'] : '';
        $update_data['new_supplier_name'] = $list_value['provider_new'];
        $update_data['new_supplier_price'] = $list_value['price_new'];
        $update_data['is_push_change_supplier'] = 0;
        $update_data['affirm_time'] = $list_value['apply_time'];
        $update_data['affirm_user'] = $list_value['applier'];
        $result = $this->db->update("product_scree", $update_data, ['sku' => $list_value['sku'], 'id' => $list_value['id']]);
    }

    /**
     * 推送 SKU屏蔽列表数据审核结果到ERP
     * @author Jeff
     * @return mixed
     */
    public function change_supplier_screen_push_to_erp()
    {
        if (CG_ENV == 'dev') {
            return true;
        }

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $push_count = $success = 0;
        $flag = true;
        do {
            // 查询 未推送、待采购确认的数据 和 审核通过的数据（每次获取 300 条）
            $scree_list = $this->product_scree_model->get_un_push_audit_scree(0, 300);

            if ($scree_list) {
                // 加载 URL 配置项
                $this->load->config('api_config', false, true);
                if (!empty($this->config->item('erp_system'))) {
                    $erp_system = $this->config->item('erp_system');
                    $_url_erp_ip = isset($erp_system['ip']) ? $erp_system['ip'] : '';
                    $_url_push_audit_to_erp = isset($erp_system['product_scree']['push_audit_to_erp']) ? $erp_system['product_scree']['push_audit_to_erp'] : '';
                    if (empty($_url_erp_ip) or empty($_url_push_audit_to_erp)) {
                        exit('ERP地址配置信息 IP或product_scree->push_audit_to_erp 参数缺失');
                    }

                }

                $push_data_list = [];
                foreach ($scree_list as $scree) {
                    $push_data = [];
                    $push_data['id'] = $scree['id'];
                    $push_data['sku'] = $scree['sku'];

                    if ($scree['status'] == PRODUCT_SCREE_STATUS_CHANGED) {
                        $push_data['affirm_status'] = 'AGREE';
                        $push_data['new_supplier_code'] = $scree['new_supplier_code'];
                        $push_data['new_supplier_name'] = $scree['new_supplier_name'];
                        $push_data['affirm_user'] = $scree['affirm_user'];
                    } else {
                        $push_data['affirm_status'] = 'DISAGREE';
                        $push_data['affirm_user'] = $scree['affirm_user'];
                        $push_data['reject_reason'] = $scree['affirm_remark'];
                    }

                    $push_data_list[$scree['id']] = $push_data;

                }

                try {
                    // 执行推送数据
                    $response = getCurlData($_url_push_audit_to_erp, ['block_list' => json_encode($push_data_list)]);
                    $push_count += count($push_data_list);

                    //验证json
                    $response = json_decode($response, true);

                    if (isset($response['status']) and $response['status'] == 'success' and !empty($response['ids'])) {
                        if (empty($response['ids'])) {
                            $result = '执行成功但 IDS 错误';
                            break;
                        } else {
                            foreach ($response['ids'] as $scree_id) {
                                if ($scree_id) {
                                    $result = $this->product_scree_model->scree_update($scree_id, ['is_push_change_supplier' => 1]);
                                    if ($result) {
                                        operatorLogInsert(
                                            [
                                                'id' => $scree_id,
                                                'type' => 'product_scree',
                                                'content' => '推送替换供应商记录成功',
                                            ]);
                                    }
                                }
                            }
                            $success += count($response['ids']);
                        }

                    } else {
                        $result = '读取返回数据出错';
                        break;

                    }
                } catch (\Exception $e) {
                    $flag = false;
                }
            } else {
                $flag = false;
            }

        } while ($flag);

        $return = [
            'push_count' => $push_count,
            'success' => $success,
            'result' => isset($result) ? $result : 'success',
        ];

        $this->success_json($return);
    }

    /**
     *恢复开发审核停产SKU，
     **/
    public function get_sku_screen_data()
    {
        $this->load->model('product/product_model');

        $javas_system = $this->config->item('java_system_service');
        $url = $javas_system['supply_status'] . "?access_token=" . getOASystemAccessToken();
        $start_time = date("Y-m-d 00:00:00", time());

        if (isset($_GET['start_time'])) {

            $start_time = $_GET['start_time'];
        }
        $end_time = date("Y-m-d 23:59:59", time());
        $result = $this->db->from("product_scree")->select("sku,id,estimate_time")
            ->where("estimate_time>='" . $start_time . "'")
            ->where("estimate_time<='" . $end_time . "'")
            ->where_in('status', [PRODUCT_SCREE_STATUS_END, PRODUCT_SCREE_STATUS_CHANGED])
            ->where_in("apply_remark", [4])->where("is_supplier_status", 0)->get()->result_array();
        if (!empty($result)) {
            foreach ($result as $key => $value) {

                // 获取SKU 最新审核通过的数据

                $newscreedatas = $this->db->from("product_scree")->select("sku,id,estimate_time,purchase_time")
                    ->where("sku", $value['sku'])
                    ->where_in('status', [PRODUCT_SCREE_STATUS_END, PRODUCT_SCREE_STATUS_CHANGED])->where_in("apply_remark", [4])
                    ->where("is_supplier_status", 0)->order_by("purchase_time DESC")->limit(1)->get()->row_array();
                if (empty($newscreedatas)) {
                    continue;
                }
                $res = $this->db->from("product_scree")->select("sku,id,estimate_time")->where("estimate_time>'" . $end_time . "'")
                    ->where("sku", $value['sku'])
                    ->where("id", $newscreedatas['id'])
                    ->where_in('status', [PRODUCT_SCREE_STATUS_END, PRODUCT_SCREE_STATUS_CHANGED])->where_in("apply_remark", [4])
                    ->where("is_supplier_status", 0)->get()->result_array();
                if (!empty($res)) {

                    continue;
                }

                //获取旧的货源状态
                $sku_info = $this->product_model->get_product_info($value['sku'], 'supply_status');
                if (empty($sku_info)) {
                    $this->error_data_json('sku[' . $value['sku'] . ']不存在');
                }
                $supply_status = $this->db->update("product", ['supply_status' => 1], ['sku' => $value['sku']]);
                $supply_is_supplier = $this->db->update("product_scree", ['is_supplier_status' => 1], ['id' => $value['id']]);
                $this->db->where_in("demand_status", [SUGGEST_STATUS_NOT_FINISH, DEMAND_SKU_STATUS_CONFIR])
                    ->where("demand_lock", DEMAND_SKU_STATUS_NO_LOCK)->where("sku", $value['sku'])
                    ->update('purchase_demand', ['is_abnormal_lock' => 1]);
                if ($supply_status && $supply_is_supplier) {

                    $send_data = array(
                        'sku' => $value['sku'],
                        'providerStatus' => 1,
                    );

                    $response = $this->send_http($url, [$send_data]);
                    $scree_log = array(

                        'username' => 'admin',
                        'operation_time' => date('Y-m-d H:i:s', time()),
                        'operation_type' => '到达预计供货时间',
                        'operation_content' => "审核通过,产品货源状态变更为:正常",
                        'scree_id' => $value['id'],
                        'remark' => "",
                        'sku' => $value['sku'],
                        'supply_status_ch' => '正常',
                    );
                    $this->db->insert('pur_product_scree_log', $scree_log);
                    //货源状态发生变化，推入消息队列
                    $this->_push_rabbitmq($value['sku'], $sku_info['supply_status'], 1);
                }
            }
        }
    }

    /**
     * 货源状态发生变化，推入消息队列
     * @param string $sku sku
     * @param int $supply_status_old 变化前的货源状态
     * @param int $supply_status_new 变化后的货源状态
     */
    private function _push_rabbitmq($sku, $supply_status_old, $supply_status_new)
    {
        //推入消息队列
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setExchangeName('PRODUCT_SUPPLY_STATUS_CHANGE_EX_NAME');
        $mq->setType(AMQP_EX_TYPE_FANOUT); //设置为多消费者模式 分发
        //构造存入数据
        $push_data = [
            'sku' => $sku,
            'supply_status_old' => $supply_status_old,
            'supply_status_new' => $supply_status_new,
            'update_time' => time(),
        ];
        //存入消息队列
        $mq->sendMessage($push_data);
        $this->_ci = get_instance();
        //获取redis配置
        $this->_ci->load->config('mongodb');
        $host = $this->_ci->config->item('mongo_host');
        $port = $this->_ci->config->item('mongo_port');
        $user = $this->_ci->config->item('mongo_user');
        $password = $this->_ci->config->item('mongo_pass');
        $author_db = $this->_ci->config->item('mongo_db');
        $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
        $bulk = new MongoDB\Driver\BulkWrite();
        $mongodb_result = $bulk->insert($push_data);
        $result = $mongodb->executeBulkWrite("{$author_db}.scree_push_supply", $bulk);
    }

    /**
     * 推送预计到货时间、预计供货时间、权均交期到erp消息队列表中
     * @author Manson
     */
    public function push_to_erp_queue()
    {
        $params = [
            'id' => $this->input->get_post('id'),
            'sku' => $this->input->get_post('sku'),
            'limit' => $this->input->get_post('limit'),
        ];
        $i = 0;
        $max_i = 3; //执行3次
        while (true) {
            try {
                $result = $this->product_scree_model->get_push_data($params);
//                pr($result);exit;
                //将已结束的数据推送至erp
                if (empty($result)) {
                    throw new Exception('没有需要推送的数据');
                } else {
                    $i++;
                }
                if ($i > $max_i) {
                    break;
                }
                $this->product_scree_model->push_to_erp($result);
            } catch (Exception $e) {
                $this->error_json($e->getMessage());exit;
            }
        }
        echo 'success';
    }
    /**
     * 判断条件如下 ：yibai_prod_sku_select_attr.attr_type_id=1是 attr_value_id 只含有67为国内仓。含有其他id的订单属性为海外仓
     * sku  array|string  SKU
     * 获取SKU是否海外仓
     * @author:luxu
     * @time:2020/7/21
     **/
    private function getSkuWarehouse($skus, $tmsIds)
    {

        $skusData = array_column($skus, "sku");
        $result = $this->product_scree_model->getSkuWarehouse($skusData, $tmsIds);
        if (!empty($result)) {

            $skuWarehouse = [];
            foreach ($result as $key => $value) {

                if (!isset($skuWarehouse[$key])) {

                    $skuWarehouse[$key] = null;
                }

                $skuValue = array_unique($value);
                if (count($skuValue) == 1 && $skuValue[0] == 67) {
                    $skuWarehouse[$key] = 2; // 国内仓
                } else {
                    $skuWarehouse[$key] = 1; // 海外仓
                }
            }

            return $skuWarehouse;
        }
    }

    /**
     * 推送历史数据到ERP
     * @author:luxu
     **/
    public function pushHistoryData()
    {

        $sql = " SELECT COUNT(*) AS total FROM pur_product_scree WHERE is_devliy_push_erp=0 AND status=50 AND apply_remark=4 ";
        $total = $this->db->query($sql)->row_array();
        $limit = 300;
        $page = ceil($total['total'] / $limit);
        $this->load->library('Rabbitmq');

        //设置参数
        //修改推送状态
        $tmsIds = $this->product_scree_model->getProdAttributesTms();
        $tmsIds = array_column($tmsIds, "id");
//        $jsonData = '["1","2","5","25","32","40","48","50","66","142","145","168","3","4","6","14","7","8","9","10","11","12","13","17","20","57","58","61","62","63","141","162","182","183","184","185","186","187","188","189","190","191","21","22","23","24","131","45","46","47","135","136","137","138","139","140","26","27","28","29","30","31","51","52","53","54","55","56","163","33","34","35","36","37","38","39","155","49","41","42","43","44","156","158","59","60","157","164","159","160","161","15","16","143","148","149","150","144","146","147","151","152","153","154","166","18","19","165","169","170","171","172","173","174","175","176","177","178","179","180"]';
        //        $tmsIds =  json_decode($jsonData,True);
        for ($i = 1; $i <= $page; ++$i) {

            $sql = " SELECT * FROM pur_product_scree WHERE is_devliy_push_erp=0 AND status=50 AND apply_remark=4  LIMIT  " . ($i - 1) * $limit . "," . $limit;

            $result = $this->db->query($sql)->result_array();

            if (!empty($result)) {

                $resultSkus = array_column($result, "sku");
                $skus = array_map(function ($skudata) {

                    return sprintf("'%s'", $skudata);
                }, $resultSkus);
                $devliSql = " SELECT MIN(avg_delivery_time) AS avg_delivery_time,sku FROM pur_sku_avg_delivery_time WHERE sku IN (" . implode(",", $skus) . ") GROUP BY sku";
                $devliData = $this->db->query($devliSql)->result_array();
                $devliData = array_column($devliData, null, "sku");
                $productSkus = $this->db->from("product")->where_in("sku", $resultSkus)->select("sku,devliy,original_devliy")->get()->result_array();

                $productSkus = array_column($productSkus, null, "sku");

                $getSkuWarehouse = $this->getSkuWarehouse($productSkus, $tmsIds);
                foreach ($result as $key => $value) {
                    $push_data = [];
                    if (isset($devliData[$value['sku']]) && ($devliData[$value['sku']]['avg_delivery_time'] != 0.000)) {
                        $avg_delivery_time = $devliData[$value['sku']]['avg_delivery_time'];
                        $arrival_time = date("Y-m-d H:i:s", strtotime($value['estimate_time']) + $avg_delivery_time * 3600 * 24);
                    } else {
                        if ((isset($getSkuWarehouse[$value['sku']]) && $getSkuWarehouse[$value['sku']] == 1) && !empty($productSkus[$value['sku']]['devliy'])

                            && $productSkus[$value['sku']]['devliy'] != 0.00
                        ) {

                            $arrival_time = date("Y-m-d H:i:s", strtotime($value['estimate_time']) + $productSkus[$value['sku']]['devliy'] * 3600 * 24);
                            $avg_delivery_time = $productSkus[$value['sku']]['devliy'];
                        }
                        if ((isset($getSkuWarehouse[$value['sku']]) && $getSkuWarehouse[$value['sku']] == 1) && (empty($productSkus[$value['sku']]['devliy'])

                            || $productSkus[$value['sku']]['devliy'] == 0.00)
                        ) {

                            if ($productSkus[$value['sku']]['original_devliy'] != 0.00) {

                                $arrival_time = date("Y-m-d H:i:s", strtotime($value['estimate_time']) + $productSkus[$value['sku']]['original_devliy'] * 3600 * 24);
                                $avg_delivery_time = $productSkus[$value['sku']]['original_devliy'];
                            } else {
                                $arrival_time = date("Y-m-d H:i:s", strtotime($value['estimate_time']) + 40 * 3600 * 24);
                                $avg_delivery_time = 40;
                            }
                        }
                        if ((isset($getSkuWarehouse[$value['sku']]) && $getSkuWarehouse[$value['sku']] != 1)) {

                            $arrival_time = date("Y-m-d H:i:s", strtotime($value['estimate_time']) + 7 * 3600 * 24);
                            $avg_delivery_time = 7;
                        }

                    }
                    $push_data[] = [
                        'sku' => $value['sku'],
                        'estimate_time' => $value['estimate_time'], //预计供货时间
                        'avg_delivery_time' => $avg_delivery_time, //最小权均交期
                        'arrival_time' => $arrival_time, //预计到货时间
                    ];

                    if (!empty($push_data)) {

                        $skusNewDatas = array_column($push_data, "sku");
                        $supplierNewDatas = $this->db->from("product")->where_in("sku", $skusNewDatas)->select("supplier_code,sku")->get()->result_array();
                        $supplierNewDatas = array_column($supplierNewDatas, null, "sku");
                        foreach ($push_data as &$push_data_value) {

                            $deliveryNewData = $this->db->from("sku_avg_delivery_time")->select("sku,purchase_type_id,warehouse_code,avg_delivery_time")->where("sku", $push_data_value['sku'])->get()->result_array();
                            if (!empty($deliveryNewData)) {

                                foreach ($deliveryNewData as &$deliveryNewData_value) {
                                    //采购类型(1国内2海外3FBA)
                                    if ($deliveryNewData_value['purchase_type_id'] == 1) {

                                        $deliveryNewData_value['purchase_type_ch'] = "国内";
                                    }

                                    if ($deliveryNewData_value['purchase_type_id'] == 2) {

                                        $deliveryNewData_value['purchase_type_ch'] = "海外";
                                    }

                                    if ($deliveryNewData_value['purchase_type_id'] == 3) {

                                        $deliveryNewData_value['purchase_type_ch'] = "FBA";
                                    }

                                    if ($deliveryNewData_value['purchase_type_id'] == 4) {

                                        $deliveryNewData_value['purchase_type_ch'] = "PFB";
                                    }

                                    $deliveryNewData_value['supplier_code'] = isset($supplierNewDatas[$push_data_value['sku']]['supplier_code']) ? $supplierNewDatas[$push_data_value['sku']]['supplier_code'] : '';
                                    $warehouseNewsNames = $this->db->from("warehouse")->where("warehouse_code", $deliveryNewData_value['warehouse_code'])
                                        ->select("warehouse_name")->get()->row_array();
                                    $deliveryNewData_value['warehouse_name'] = $warehouseNewsNames['warehouse_name'];

                                }
                                $push_data_value['other_message'] = $deliveryNewData;
                            } else {

                                $push_data_value['other_message'] = [];
                            }
                        }

                    }

                    $insertLogs = [
                        'sku' => $value['sku'],
                        'pushdata' => json_encode($push_data),
                        'source' => 1,
                        'pushtime' => date("Y-m-d H:i:s", time()),
                    ];
                    //创建消息队列对象
                    $mq = new Rabbitmq();
                    $mq->setExchangeName('PURCHASE_SKU_SCREE_INFO_EX_NAME');
                    $mq->setRouteKey('PURCHASE_SKU_SCREE_INFO_R_KEY');
                    $mq->setType(AMQP_EX_TYPE_FANOUT); //设置为多消费者模式 分发
                    //存入消息队列
                    $result = $mq->sendMessage($push_data);
                    $this->db->insert('sku_erp_log', $insertLogs);
                    $this->db->where("id", $value['id'])->update("product_scree", ['is_devliy_push_erp' => 1]);

                }
            } else {
                break;
            }
        }
    }

    /**
     * 需求：配合销售刊登提供采购单的预计到货时间、权均交期等数据
    有如下几个接口需要做：
    1、传SKU和业务线查询“等待到货”“部分到货等待剩余”的采购单；
    2、SKU屏蔽推送的时候一起推送权均交期数据（原有接口增加字段）；
    3、传SKU查询SKU货源状态；
     * @author:luxu
     **/
    public function getPurchaseOrdersData()
    {

        try {

            $clientData = json_decode(file_get_contents('php://input'), true);
            if (empty($clientData)) {

                echo json_encode(['status' => 0, 'message' => '数据错误', 'error_list' => $clientData]);
                die();

            }

            $resultdata = [];
            foreach ($clientData as $clientKey => $clientValue) {

                if (!isset($clientValue['sku']) || !isset($clientValue['purchase_type_id'])) {

                    echo json_encode(['status' => 0, 'message' => '缺少数据', 'error_list' => $clientData]);
                    die();
                }
                //采购单状态(1.等待采购询价,2.信息修改待审核,3.待采购审核,5.待销售审核,6.等待生成进货单,
                //7.等待到货,8.已到货待检测,9.全部到货,10.部分到货等待剩余到货,
                //11.部分到货不等待剩余到货,12.作废订单待审核,13.作废订单待退款,14.已作废订单,15.信息修改驳回)

                $sql = " SELECT items.purchase_number,items.sku,orders.supplier_code
                ,items.confirm_amount,warehouse.warehouse_name,warehouse.warehouse_code,items.plan_arrive_time,orders.audit_time FROM pur_purchase_order AS orders LEFT JOIN pur_purchase_order_items AS items
                ON orders.purchase_number=items.purchase_number";
                $sql .= " LEFT JOIN pur_warehouse as warehouse ON warehouse.warehouse_code=orders.warehouse_code";
                $sql .= " WHERE orders.purchase_order_status IN (7,10) AND items.sku ='{$clientValue['sku']}' AND orders.purchase_type_id='{$clientValue['purchase_type_id']}'";
                $result = $this->db->query($sql)->result_array();
                if (!empty($result)) {

                    array_push($resultdata, $result);
                }
            }
            $this->success_json($resultdata);
            die();
        } catch (Exception $exp) {

            echo json_encode(['status' => 0, 'message' => '获取数据错误', 'error_list' => $exp->getMessage()]);
            die();
        }
    }

    /**
     * 需求：配合销售刊登提供采购单的预计到货时间、权均交期等数据
    有如下几个接口需要做：
    传SKU查询SKU货源状态；
     * 案例：{"sku":["JM03491","JM03491"]}
     * @author:luxu
     **/
    public function getSkuSupplyData()
    {

        try {

            $clientData = json_decode(file_get_contents('php://input'), true);
            if (empty($clientData)) {

                echo json_encode(['status' => 0, 'message' => '数据错误', 'error_list' => $clientData]);
                die();

            }

            if (isset($clientData['sku']) || !empty($clientData['sku'])) {

                $resultData = [];

                $result = $this->db->from("product")->where_in("sku", $clientData['sku'])
                    ->select("sku,supplier_code,supply_status")->get()->result_array();

                $supply_code = array_column($result, "supplier_code");

                if (!empty($supply_code)) {

                    $supplierData = $this->db->from("supplier")->where_in("supplier_code", $supply_code)->select("supplier_code,status as cooperation_type")->get()->result_array();
                    $supplierData = array_column($supplierData, null, "supplier_code");
                    //1: "启用", 2: "禁用", 4: "待审", 5: "审核不通过", 6: "预禁用"
                    foreach ($result as &$data) {

                        if (isset($supplierData[$data['supplier_code']])) {

                            switch ($supplierData[$data['supplier_code']]['cooperation_type']) {

                                case 1:
                                    $data['cooperation'] = '启用';
                                    break;
                                case 2:
                                    $data['cooperation'] = '禁用';
                                    break;

                                case 4:
                                    $data['cooperation'] = '待审核';
                                    break;
                                case 5:
                                    $data['cooperation'] = '审核不通过';
                                    break;
                                case 6:
                                    $data['cooperation'] = '预禁用';
                                    break;
                                default:
                                    $data['cooperation'] = '';
                                    break;

                            }
                        }
                        //货源状态(1.正常,2.停产,3.断货，10停产找货中)
                        if ($data['supply_status'] == 1) {

                            $data['supply_status'] = "正常";
                        }

                        if ($data['supply_status'] == 2) {

                            $data['supply_status'] = "停产";
                        }

                        if ($data['supply_status'] == 3) {

                            $data['supply_status'] = "断货";
                        }

                        if ($data['supply_status'] == 10) {

                            $data['supply_status'] = "停产找货中";
                        }
                    }
                    $this->success_json($result);
                }
            }
            $this->success_json();

        } catch (Exception $exp) {

            $this->error_json();
        }
    }

    /**
     * 推送SKU 货源状态 到小平台或者EBAY
     * @author:luxu
     * @time:2021年1月30号
     **/
    public function pushSkuSupply()
    {
        ini_set('max_execution_time', '18000');
        $sql = " SELECT COUNT(*) AS total FROM pur_product_scree WHERE status=50 AND is_publish=0";
        $total = $this->db->query($sql)->row_array();
        $limit = 1500;
        $page = ceil($total['total'] / $limit);

        for ($i = 1; $i <= $page; ++$i) {

            $sql = " SELECT sku FROM pur_product_scree WHERE status=50 AND is_publish=0 LIMIT  " . ($i - 1) * $limit . "," . $limit;
            $screeSkus = $this->db->query($sql)->result_array();
            if (!empty($screeSkus)) {

                $skus = array_column($screeSkus, 'sku');
                $productSupply = $this->db->from("product")->where_in("sku", $skus)->select("sku,supply_status")->get()->result_array();
                if (!empty($productSupply)) {
                    foreach ($productSupply as $key => $value) {

                        //货源状态发生变化，推入消息队列
                        $this->_push_rabbitmq($value['sku'], '', $value['supply_status']);
                    }

                    $this->db->where_in("sku", $skus)->where("status", 50)->update('product_scree', ['is_publish' => 1]);
                }
            }
        }
    }

    public function testa()
    {

        $arr = [1, 2, 3, 4, 5, 6, 7];
        foreach ($arr as $key => $value) {
            echo "value=" . $value . "\r\n";
            try {

                $this->db->from("product")->where("tid", 4)->get()->row_array();
                echo $this->db->_error_number();
                if ($value == 5) {

                    throw new Exception("大丰收发多少");
                }
            } catch (Exception $exception) {

                echo $exception->getMessage();
            }

        }
    }

    public function updateScree()
    {
        ini_set('max_execution_time', '9000');

        $tskus = "'1010200132011',
'QC02091',
'1210190030111',
'1615190004811',
'QC02199',
'2016200007711',
'1911190007311',
'3117210061015',
'1411200444711',
'MX05362-02',
'QC00949-04',
'YS04837',
'ZP29677',
'JM01319',
'1411190123611',
'GS14805',
'1610190047511',
'QC31304',
'06EGS90000',
'1813190016711',
'2712190022612',
'2717200100111',
'MX06862',
'3117210064111',
'3117210064112',
'JY38187',
'1413200033211',
'YX01229-01',
'JM13837-01',
'XD04758-01',
'2411190004313',
'2720210104511',
'1614190041911',
'1811190016312',
'JY17066-02',
'TJ08051-04',
'GS18561-01',
'DS09119',
'JY12585',
'1411190217111',
'QC05980',
'1813190005011',
'1412200048511',
'JYA01522-02',
'YX04095',
'1215210005614',
'1218190004315',
'1218190004314',
'1412210099611',
'QC28741',
'1217190047311',
'AF03050-02',
'1410190037611',
'2718190050612',
'TJ11939-02',
'2718200038312',
'3115200123511',
'DS03479-02',
'YX03696',
'1610200059511',
'1616200161911',
'GS04623',
'1012200105511',
'GS07636',
'2610200098211',
'2612200005611',
'xd03518',
'xd07344',
'xd12750',
'1413200093911',
'GS17697',
'GS17472',
'JM25410-02',
'BB01850-06',
'JM06101-02',
'2613200092612',
'ZP15748',
'DS02577',
'XD02269-02',
'JM07968-02',
'YS02023',
'GY05053',
'1012200069811',
'2015210003511',
'2411200021911',
'BB05241-09',
'2718190068511',
'2719200031611',
'BB02931-03',
'1216210023811',
'TJ12421-02','2411200144411',
'1217200039711',
'TJ03872-02',
'3113200157911',
'2716210084811',
'2718200005411',
'JY35374-01',
'1411200149511',
'QC38046-03',
'GS06406-01',
'1414200063511',
'JY17031',
'QC33254',
'3112200109911',
'1211210029711',
'1211210029713',
'BB04759-01',
'2116190013911',
'SJ03606',
'SJ04301',
'2613200064011',
'2013210046012',
'2013210046011',
'JYA00834',
'2610200090111',
'AF02231',
'1110210001011',
'QC35790-01',
'1810200023512',
'JM08369',
'JM01142-02',
'JM02054-07',
'ZP14987-05',
'ZP01778-05',
'ZP07400-03',
'JM26319-02',
'XD00784-01',
'1411190029711',
'2610200094011',
'ZM03584-03',
'2016200015411',
'3114200044911',
'3118210154111',
'3118210154112',
'1412190047311',
'DS08260-04',
'1616210033511',
'2411200119411',
'GY04657',
'CW06406-01',
'JM07553-02',
'1214210004911',
'1411190067811',
'3115190055313',
'CW04423-01',
'1214190001911',
'2110200045711',
'2110200027511',
'JM15665',
'TJ21057-02',
'JYB00761',
'3118200210211',
'GS21845',
'3118200210212',
'3118200210213',
'MX05269-01',
'GY08309-01',
'GY08309-02',
'MX01218',
'3113190142611',
'1412190006011',
'3117200091212',
'3118210150311',
'ZP00181-01',
'ZP00181-03',
'QCQP31711',
'1411210132913',
'BB04759-07',
'JM01869',
'1411190030211',
'2013200068811',
'JY01122',
'3118200239511',
'JY20296',
'2610200181811',
'3111200167111',
'2719200073211',
'3114210000811',
'3114210000812',
'JY39737-02',
'QCQP30500',
'3113210079711',
'3113210079713',
'1413200015411',
'GS01135',
'3116190061513',
'3118210149411',
'3117200074011',
'3117200073712',
'3117200074012',
'1411190194411',
'3117200073912',
'3116190074013',
'3116190073912',
'3118200032712',
'3118210021611',
'3117200048411',
'ZP09158',
'1214200078813',
'1214200078815',
'1214200078816',
'BB03372-02',
'1214200078911',
'1214200078913',
'1214200078914',
'JM07532-01',
'3112190144511',
'2013200122312',
'2013200122316',
'2013200122317',
'2013200122411',
'2018200019711',
'2018200019712',
'1012200061211',
'3117200073612',
'3118200037311',
'2013200124511',
'2013200128712',
'DS05519',
'1413200114413',
'1411210165512',
'1411210165614',
'1411210165911',
'1411210165912',
'1411210165913',
'1411210165914',
'1411210165915',
'1411210166812',
'1411210166813',
'1411210166814',
'1411210166815',
'1410200062511',
'1412200051914',
'2214190067211',
'1013200036211',
'JY02935-04',
'3113190066311',
'SJ01179',
'YSA00201',
'JM09284-05',
'JM09284-07',
'1412200065812',
'3117210004511',
'XD05320',
'3117200048711',
'3113200047412',
'3117210032311',
'2718200103711',
'1411210113611',
'YS02978',
'JYA02824-05',
'JYA02824-01',
'JYA02824-02',
'2014210003811',
'2014210003812',
'2014210003813',
'2014210003814',
'GY06104',
'JY10829-02',
'1215190000711',
'1614200048811',
'2013200124512',
'2013200124513',
'AF01073',
'3111200127911',
'JM16995',
'JY33053',
'3117200142011',
'JY14369-03',
'TJ09473',
'JM29558-02',
'1310210029313',
'1614200094811',
'2013200158211',
'1411210137013',
'GS11349',
'1411210133111',
'JY20432',
'1411210097211',
'3118210166612',
'1410210049711',
'3113190137311',
'3111200095211',
'3118200061611',
'JM27473-02',
'JM27473-01',
'3111210021611',
'3113210091911',
'3113200090611',
'1411200345311',
'1412210002811',
'3117210092111',
'SMT-XDOT75300',
'2718200011412',
'TJB00979-01',
'2411200042011',
'AF01422',
'3113210095411',
'1410200086911',
'1410200087111',
'1410200087211',
'1410200087311',
'1410200087411',
'1410210013711',
'1412210016011',
'1412210081011',
'1412210081012',
'JM09282-01',
'JM09282-04',
'JM09282-06',
'JM09284-01',
'JM09284-02',
'JM09284-03',
'JM09284-04',
'JM09284-06',
'JM09284-08',
'JM09290-01',
'JM09290-02',
'JM09290-04',
'JM09290-05',
'JM09290-07',
'JM09290-08',
'JM09305-01',
'JM09305-02',
'1414200000811',
'3111210138711',
'2718200071211',
'1413200122011',
'1217200094011',
'2716200187211',
'1217200111511',
'JM16009-02',
'3117200114912',
'2610200021514',
'1411200243212',
'3111210138712',
'JY38183-01',
'JY38183-02',
'JY38183-03',
'JY22038-02',
'JY06232-03',
'JYA02824-03',
'CW02951-03',
'2110210042313',
'3118200278611',
'1011210304811',
'GS15838',
'JM25340',
'3112190147012',
'1411190207711',
'JY09287-03',
'3113200041413',
'JY14743',
'TJA00944-01',
'JY25321-02',
'TJB00976-02',
'TJB00717',
'3118200038112',
'3118200037211',
'1218210026411',
'JM26729-04',
'JM24551',
'JM16574-03',
'1412190057511',
'JM23824',
'3118210094511',
'1411200491211',
'QC33280-01',
'2210190018611',
'2013200133012',
'1215190000712',
'JYJJ6400',
'JY40800-02',
'JY40800-01',
'JY39545',
'SJ02463-06',
'XD12635',
'3115190006411',
'2716210128511',
'JY23407-02',
'JY10352',
'1310210029312',
'3117200050011',
'3118200160611',
'1412200147811',
'1411200271111',
'JYA01192-02',
'JY23607',
'JM13508-01',
'3113200041411',
'3111210230411',
'JY11137-08',
'GY04486',
'3117210087811',
'2111210014416',
'AF00989-01',
'JY31476',
'3113190065411',
'JM16733',
'TJB00216-02',
'TJB00216-01',
'JM22842-03',
'1413200104311',
'JM16668',
'1413190035912',
'JM15546',
'1413200118712',
'1413190036611',
'1413200052811',
'1413190021811',
'1413200005311',
'1413210012611',
'1618190226911',
'JY05336-04',
'1011210357211',
'3117200200111',
'1414200080111',
'3117210090112',
'3118210154811',
'3117210090111',
'JY06127-02',
'2412200019911',
'TJA02646-01',
'3111200314611',
'JM29055',
'JM27555-03',
'1411210163111',
'3112190147011',
'FR-QC10352',
'JY13022-02',
'3111210235912',
'3111210235911',
'3111210235913',
'1613210006911',
'BB05798-01',
'1413200129611',
'JY35327',
'JYA03278',
'MX02823',
'JM05573-02',
'1412190109211',
'2613210012111',
'QC11101',
'JM22585-01',
'2115200044311',
'2115200041611',
'CW02187-03',
'JMOT31800',
'JM21957',
'3119210051411',
'3119210051711',
'3119210052611',
'3119210052711',
'3119210052811',
'3119210052911',
'3119210062511',
'3119210062611',
'3119210062711',
'3119210062911',
'3119210063211',
'3119210063511',
'3119210079411',
'3119210079511',
'3119210079711',
'3119210079811',
'3119210080211',
'1413200133611',
'1413200133511',
'3118210084411',
'1412210002911',
'2115190035911',
'1616210050511',
'YX03531',
'3117200175112',
'1414200038012',
'2110190057525',
'2411190041111',
'2411200026111',
'2411200027011',
'2311200000111',
'2110190057122',
'2110190057111',
'2110190057116',
'BB00718-01',
'1411210003111',
'1413200112711',
'1411210126112',
'XD09429',
'2610200091111',
'2610200091211',
'1411200483912',
'BB06565',
'JY11228-01',
'TJA02817',
'GS19652',
'3112190068911',
'3112190068914',
'2718200009212',
'TJ20798-02',
'AF00647',
'1613200028513',
'2511200078511',
'TJ21223-01',
'3118200062312',
'3117200121411',
'1618200387211',
'GS00645',
'3118200279011',
'GSGJ6800-14',
'QC00503',
'3119210111011',
'1411210018112',
'1411200048013',
'JM15602-02',
'2714190018411',
'1411210018111',
'1413200064911',
'1616200177511',
'QC04079-04',
'TJB00977-02',
'1619210062811',
'JY11330',
'3115210062511',
'2610190066211',
'1413200170812',
'2716200186311',
'JM02291-02',
'1011190213911',
'CW07023-05',
'1218200047013',
'3112190163711',
'ZP14600',
'3113200061413',
'JY15914',
'GY09794-03',
'QC11791',
'2719200029613',
'2713200005113',
'QC27795-01',
'2511190000111',
'GB-JYJJ10800-25',
'US-JYJJ10800-45',
'QC21735-01',
'2610200019911',
'QC08994',
'CW03321',
'2720210086311',
'TJA00512',
'1412210003011',
'1613200028512',
'1613200028514',
'2115190030711',
'MX01001',
'1416200012112',
'JY11783-05',
'GSLJ0300',
'1910190140711',
'1610200044111',
'2216200004512',
'JM18624-02',
'JM18624-01',
'JM18624-03',
'1413210012612',
'1413200003811',
'JM27199-03',
'1413200183511',
'1413190080811',
'1410200096011',
'1813210006911',
'JY07845',
'JYOT35000',
'JY01937',
'JY08560',
'JY06864-01',
'JYA01738-01',
'JY12314-02',
'JY20413-02',
'JY16569',
'SJ01256-05',
'TJ14991-02',
'3118200153314',
'2014210003412',
'1812190008111',
'1616190124711',
'JYB02237',
'GSGJ27427',
'1616200187111',
'1414200037812',
'3118210075812',
'1011210035911',
'XD08027',
'2013200109212',
'ZP29670-03',
'JYA01635-03',
'3113200022311',
'1413200141911',
'1411200507911',
'1413190088011',
'JM01639-02',
'QC03473-01',
'GS07950-02',
'2110210046311',
'2110210046312',
'2110210046313',
'3113200191011',
'3113200191012',
'GS04505',
'JYB01537',
'GS01249-02',
'QC21732-07',
'MX05347',
'1616190117211',
'ZP19015',
'1811190013711',
'1811190013911',
'1811190014011',
'1811190014111',
'1811190014411',
'1811190014611',
'JY02389',
'JY02390',
'JY02448',
'ZP14596-01',
'1411200271112'";
        $t = array_map(function ($skus) {
            return trim($skus);
        }, explode(",", $tskus));

        $sql = 'SELECT * FROM pur_product WHERE sku IN (SELECT sku FROM pur_product_scree WHERE status=50 AND estimate_time>"2021-07-21" ORDER BY estimate_time DESC) AND supply_status=1

       AND sku NOT IN(' . implode(",", $t) . ')';

        $result = $this->db->query($sql)->result_array();

        foreach ($result as $key => $value) {

            $sql = " SELECT * FROM pur_product_scree WHERE sku='{$value['sku']}' AND status=50 AND estimate_time>'2021-07-21'";

            $res = $this->db->query($sql)->row_array();
            if (!empty($res)) {
                /*if(in_array($res['sku'],$t)){

                continue;
                }*/

                $url = "http://pms.yibainetwork.com:81/product/product/update_supply_status?uid=2371";

                if ($res['apply_remark'] == 2 || $res['apply_remark'] == 99) {
                    $update_supply['supply_status'] = 2;
                } else if ($res['apply_remark'] == 4) {
                    $update_supply['supply_status'] = 3;
                }

                if ($res['apply_remark'] == 10) {
                    $update_supply['supply_status'] = $res['apply_remark'];
                }

                $postData = [

                    'sku' => $res['sku'],
                    'supply_status' => $update_supply['supply_status'],
                ];
                $result = getCurlData($url, $postData, 'post');
                echo "'" . $res['sku'] . "'," . "\r\n";

            }
        }

    }

    public function updateddd()
    {

        $sql = "SELECT sku,purchase_time,apply_time,id FROM pur_product_scree WHERE purchase_time='' AND estimate_time>'2021-07-21' AND status=50";

        $result = $this->db->query($sql)->result_array();

        foreach ($result as $key => $value) {

            $this->db->where("id", $value['id'])->update("product_scree", ['purchase_time' => $value['apply_time']]);
        }
    }
/**
 * 全部sku总屏蔽次数初始化,终端执行
 *
 * @return void
 */
    public function caluScreeNum()
    {
        $sku_num = $this->db->from("product_scree")->where("status", 50)->group_by('sku')->select('COUNT(*) as count,sku')->get()->result_array();
        // echo $this->db->last_query();
        $sku_num_arr = array_column($sku_num, 'count', 'sku');
        var_dump($sku_num_arr);exit;
        if (!empty($sku_num_arr)) {
            foreach ($sku_num_arr as $key => $v) {
                try {
                    $this->db->where("sku", $key)->update('product', ['total_scree_num' => $v]);
                } catch (\Throwable $th) {
                    echo $th->getMessage() . PHP_EOL;
                }
            }
        }
        return true;
    }

}
