<?php

use PhpParser\Node\Expr\AssignOp\Concat;

/**
 * Created by PhpStorm.
 * sku屏蔽申请列表
 * User: Jolon
 * Date: 2019/01/16 0029 11:50
 */
class Product_scree_model extends Purchase_model
{
    protected $table_name = 'product_scree'; // 数据表名称

    /**
     * Product_scree_model constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('product_model', '', false, 'product');
        $this->load->model('product_line_model');
        $this->load->model('user/User_group_model');
        $this->load->model('Message_model');
        $this->load->helper('status_product');

    }
    /**
     * 获取 屏蔽列表信息
     * @author Jolon
     * @param int $scree_id  屏蔽列表ID
     * @return mixed
     */
    public function get_scree_one($scree_id)
    {
        if (empty($scree_id)) {
            return false;
        }

        $query_builder = $this->purchase_db->from($this->table_name);
        if (is_array($scree_id)) {
            $query_builder->where_in('id', $scree_id);
            $results = $query_builder->get()->result_array();
        } else {

            $results = $query_builder->where('id', (int) $scree_id)->get()->row_array();
        }

        if (!empty($results)) {
            if (is_array($scree_id)) {
                foreach ($results as $key => $value) {

                    $category_all = $this->product_line_model->get_all_parent_category($value['product_line_id']);
                    $results[$key]['category_all'] = $category_all;

                }
            } else {
                $results['category_all'] = $this->product_line_model->get_all_parent_category($results['product_line_id']);
            }

        }
        return $results;
    }

    /**
     * 分页  获取列表
     * @author Jolon
     * @param      $params
     * @param int  $offset
     * @param int  $limit
     * @param int  $page
     * @return array
     * 2: "停产", 4: "缺货", 99: "需要起订量"
     */
    public function get_scree_list($params, $offset = null, $limit = null, $page = 1)
    {

        $daysData = [];
        $endTime = date("Y-m-d H:i:s", time());
        $startTime = date("Y-m-d H:i:s", strtotime("-100 day"));
        if ((isset($params['100_days_start']) && !empty($params['100_days_start'])) || (isset($params['100_days_end']) && !empty($params['100_days_end']))) {
            //找货组id为16
            $goods_users = $this->User_group_model->getUserByGroup([16]);
            $query = $this->purchase_db->from("product_scree")->where("apply_time>=", $startTime)
                ->where("apply_time<=", $endTime)->where("status", 50)->where("apply_remark", 10)->where_in("apply_user_id", $goods_users)->group_by('sku')->select('COUNT(*) as count,sku');

            if ((isset($params['100_days_start']) && !empty($params['100_days_start']))) {

                $query->having("count>=", $params['100_days_start']);
            }

            if ((isset($params['100_days_end']) && !empty($params['100_days_end']))) {

                $query->having("count<=", $params['100_days_end']);
            }

            $daysData = $query->get()->result_array();
        }

        $params = $this->table_query_filter($params); // 过滤为空的元素
        $category_all_ids = [];
        if (isset($params['product_line_id']) && !empty($params['product_line_id'])) {
            $category_all_ids = $this->product_line_model->get_all_category($params['product_line_id']);
        }

        $query_builder = $this->purchase_db->from("product_scree AS scree")->JOIN("pur_product AS product", "scree.sku=product.sku", "LEFT");
        if (isset($params['sku']) || !empty($params['sku'])) {
            $query_builder->where_in('scree.sku', $params['sku']);
            unset($params['sku']);
        }

        if (isset($params['estima_status']) && !empty($params['estima_status'])) {

            $query_builder->where("scree.apply_remark", 4);
            unset($params['estima_status']);
        }

        if (isset($params['90_sales_start']) && !empty($params['90_sales_start'])) {

            $query_builder->where('product.days_sales_90>=', $params['90_sales_start']);
        }

        if (isset($params['90_sales_end']) && !empty($params['90_sales_end'])) {

            $query_builder->where('product.days_sales_90<=', $params['90_sales_end']);
        }

        if (isset($params['30_sales_start']) && !empty($params['30_sales_start'])) {

            $query_builder->where('product.days_sales_30>=', $params['30_sales_start']);
        }

        if (isset($params['apply_reason']) && !empty($params['apply_reason'])) {

            $query_builder->where_in('scree.apply_remark', $params['apply_reason']);
        }
        if (isset($params['scree_num_start']) && !empty($params['scree_num_start'])) {

            $query_builder->where('product.total_scree_num>=', $params['scree_num_start']);
        }
        if (isset($params['scree_num_end']) && !empty($params['scree_num_end'])) {

            $query_builder->where('product.total_scree_num<=', $params['scree_num_end']);
        }
        

        if (!empty($daysData)) {

            $daysSkus = array_unique(array_column($daysData, 'sku'));

            if (count($daysSkus) >= 2000) {

                $datasSkus = array_chunk($daysSkus, 1);
                $this->purchase_db->group_start();

                foreach ($datasSkus as $skust) {

                    $query_builder->or_where_in("scree.sku", $skust);
                }
                $this->purchase_db->group_end();

            } else {

                $query_builder->where_in("scree.sku", $daysSkus);
            }
        }

        if (isset($params['100_days_start']) && !empty($params['100_days_start'])) {

            if (empty($daysData)) {

                $query_builder->where_in("scree.sku", ['no']);
            }
        }
        if (isset($params['30_sales_end']) && !empty($params['30_sales_end'])) {

            $query_builder->where('product.days_sales_30<=', $params['30_sales_end']);
        }

        if (isset($params['nowdate']) && !empty($params['nowdate'])) {

        }
        if (isset($params['apply_time_start']) and $params['apply_time_start']) {
            $query_builder->where('scree.apply_time >=', (strpos($params['apply_time_start'], ':') !== false) ? $params['apply_time_start'] : $params['apply_time_start'] . ' 00:00:00');
            unset($params['apply_time_start']);
        }
        if (isset($params['apply_time_end']) and $params['apply_time_end']) {
            $query_builder->where('scree.apply_time <=', (strpos($params['apply_time_end'], ':') !== false) ? $params['apply_time_end'] : $params['apply_time_end'] . ' 23:59:59');
            unset($params['apply_time_end']);
        }

        if (isset($params['product_line_id']) && !empty($params['product_line_id'])) {

            $children_id = $category_all_ids;
            $children_ids = explode(",", $children_id);
            $children_ids = array_filter($children_ids);
            $query_builder->where_in('scree.product_line_id', $children_ids);
            unset($params['product_line_id']);
        }

        if (isset($params['oper_time_start']) && !empty($params['oper_time_start'])) {

            $query_builder->where('scree.affirm_time >=', $params['oper_time_start']);
            unset($params['oper_time_start']);
        }

        if (isset($params['oper_time_end']) && !empty($params['oper_time_end'])) {

            $query_builder->where('scree.affirm_time <=', $params['oper_time_end']);
            unset($params['oper_time_end']);
        }

        if (isset($params['estimate_time_start']) && !empty($params['estimate_time_start'])) {

            $query_builder->where('scree.estimate_time >=', $params['estimate_time_start']);
            unset($params['estimate_time_start']);
        }

        if (isset($params['estimate_time_end']) && !empty($params['estimate_time_end'])) {

            $query_builder->where('scree.estimate_time <=', $params['estimate_time_end']);
            unset($params['estimate_time_end']);
        }

        if (isset($params['status']) && !empty($params['status'])) {

            $query_builder->where('scree.status', $params['status']);
            unset($params['status']);
        }

        if (isset($params['old_supplier_code']) && !empty($params['old_supplier_code'])) {
            $query_builder->where('scree.old_supplier_code', $params['old_supplier_code']);
        }
        if (isset($params['developer_id']) && !empty($params['developer_id'])) {
            $query_builder->where('scree.developer_id', $params['developer_id']);
        }

        if (isset($params['apply_user_id']) && !empty($params['apply_user_id'])) {
            if (is_array($params['apply_user_id'])) {
                $query_builder->where_in('scree.apply_user_id', $params['apply_user_id']);
            } else {
                $query_builder->where('scree.apply_user_id', $params['apply_user_id']);
            }
        }

        if (isset($params['group_ids']) && !empty($params['group_ids'])) {

            $query_builder->where_in('scree.apply_user_id', $params['groupdatas']);
        }

        if (isset($params['is_push_erp'])) {
            $query_builder->where('scree.is_push_erp', $params['is_push_erp']);
        }

        if (isset($params['scree_source']) && !empty($params['scree_source'])) {
            if ($params['scree_source'] == '1') {
                $query_builder->where('scree.apply_content ="取消未到货生成。"');
            } elseif ($params['scree_source'] == '2') {
                $query_builder->where('scree.apply_content <>"取消未到货生成。" OR scree.apply_content IS NULL');
            }
        }

//        print_r($params);exit;

        //$query_builder->where($params);

        $query_builder_count = clone $query_builder; // 克隆一个查询 用来计数
        $total_count = $query_builder_count->count_all_results();

        $results = $query_builder->order_by('scree.apply_time', 'desc')->select("scree.*,product.days_sales_30 AS sales,product.days_sales_90 as thirty_sales,product.total_scree_num as total_scree_num")->limit($limit, $offset)->get()->result_array();
        if (!empty($results)) {
            $product_line_ids = array_unique(array_column($results, "product_line_id"));

            $product_lines = $this->purchase_db->from("pur_product_line")->where_in("product_line_id", $product_line_ids)->get()->result_array();
            $product_lines_mess = [];
            if (!empty($product_lines)) {

                $product_lines_mess = array_column($product_lines, "linelist_cn_name", "product_line_id");
            }

            $buyerIds = array_unique(array_column($results, "apply_user_id"));
            $buyerName = $this->User_group_model->getBuyerGroupMessage($buyerIds);
            $buyerName = array_column($buyerName, null, 'user_id');

            foreach ($results as $key => &$value) {

                $category_all = $this->product_line_model->get_all_parent_category($value['product_line_id']);
                $value['apply_remark_id'] = $value['apply_remark'];
                if ($value['apply_remark'] == 3) {
                    $value['apply_remark'] = "缺货";
                }
                if ($value['apply_remark'] == 4) {

                    $value['apply_remark'] = "缺货";
                }

                if ($value['apply_remark'] == 99) {

                    $value['apply_remark'] = "需要起订量";
                }

                if ($value['apply_remark'] == 2) {

                    $value['apply_remark'] = "停产";
                }

                if ($value['apply_remark'] == 10) {

                    $value['apply_remark'] = "停产找货中";
                }

                $value['audit_time'] = $value['affirm_time'];
                $t = $this->getPrevData([$value['sku']], $startTime, $endTime);
                $value['hundred_days'] = isset($t[$value['sku']]) ? $t[$value['sku']] : 0;

                $value['product_line_name'] = (isset($product_lines_mess[$value['product_line_id']])) ? $product_lines_mess[$value['product_line_id']] : null;
                $stocks = $this->purchase_db->from("warehouse as warehouse")->JOIN("stock AS stock", "stock.warehouse_code=warehouse.warehouse_code", "LEFT")->select("SUM(stock.on_way_stock) AS on_way_stock ,SUM(stock.available_stock) AS available_stock,warehouse.warehouse_name")->where("stock.sku", $value['sku'])->get()->row_array();
                $value['available_stock'] = $stocks['available_stock'];
                $value['on_way_stock'] = $stocks['on_way_stock'];
                $stock_numbers = $this->purchase_db->select(" SUM(available_stock) AS available_stock")->where(['sku' => $value['sku']])->get('pur_stock')->row_array();
                $value['wms_stock'] = $stock_numbers['available_stock'];
                $value['category_all'] = $category_all;
                $value['groupName'] = isset($buyerName[$value['apply_user_id']]) ? $buyerName[$value['apply_user_id']]['group_name'] : '';
            }
        }

        $return_data = [
            'data_list' => $results,
            'paging_data' => [
                'total' => $total_count,
                'offset' => $page,
                'limit' => $limit,
            ],
        ];

        return $return_data;
    }

    /**
     * 获取SKU 状态
     * @param : $sku     array    SKU 信息
     *          $status  array    状态
     **/
    public function get_scree_skus_data($skus, $status = [
        PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,
        PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM,
        PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM,
    ]) {

        if (empty($skus) || empty($status)) {

            return null;
        }

        // 验证是否已经存在 待处理的 SKU屏蔽 记录
        $old_scree = $this->purchase_db->select("sku,id,status,apply_remark")->where_in('sku', $skus)
            ->where_in('status', $status)
            ->get($this->table_name)
            ->result_array();
        return $old_scree;

    }

    /**
     * 创建屏蔽SKU
     * @param $addInfo   array 屏蔽SKU 信息
     * @param $apply
     * @param $estimate
     * @param $remark
     * @param bool $import
     * @param bool $is_cancel
     * @return array|bool
     */
    public function set_scree_create($addInfo, $apply, $estimate, $remark, $import = false, $is_cancel = false)
    {

        if (empty($addInfo)) {
            return false;
        }

        $this->load->helper('status_product');
        $supplyStatusList = getProductsupplystatus();

        $error_arr = [];
        foreach ($addInfo as $key => $value) {
            $sku = trim($value['sku']);
            $product_info = $this->product_model->get_product_info($sku);

            if (empty($product_info) && true == $import) {

                if ($value['apply_remark'] == 2) {
                    $oneapplyremark = '停产';
                } else if ($value['apply_remark'] == 4) {
                    $oneapplyremark = '缺货';
                } else if ($value['apply_remark'] == 99) {
                    $oneapplyremark = '需要起订量';
                } else if ($value['apply_remark'] == 10) {
                    $oneapplyremark = '停产找货中';
                }

                if ($apply == PRODUCT_SCREE_APPLY_REASON_NEED_MINIMUM) {

                    $value = [
                        $sku,
                        $oneapplyremark,
                        '',
                        $estimate,
                        $remark,
                        "sku:" . $sku . "，不存在",
                    ];
                    $error_arr[] = $value;
                    continue;
                } else {

                    $value = [
                        $sku,
                        $oneapplyremark,
                        $estimate,
                        '',
                        $remark,
                        "sku:" . $sku . "，不存在",
                    ];
                    $error_arr[] = $value;
                    continue;
                }

            }
            $stock_numbers = $this->purchase_db->select(" SUM(available_stock) AS available_stock")->where(['sku' => $sku])->get('pur_stock')->row_array();
            // 添加数据
            $insert_data = [
                'sku' => $sku,
                'product_name' => $product_info['product_name'],
                'product_line_id' => $product_info['product_line_id'],
                'product_line_name' => getProductLineName($product_info['product_line_id']),
                'product_img_url' => $product_info['product_img_url'],
                'days_sales_30' => $product_info['days_sales_30'], // 获取SKU 30天销量
                'developer_id' => $product_info['create_id'],
                'developer_name' => $product_info['create_user_name'], // 开发员
                'apply_user_id' => getActiveUserId(),
                'apply_user' => getActiveUserName(),
                'apply_time' => date('Y-m-d H:i:s'),
                'apply_remark' => $apply, // 申请原因
                'chat_evidence' => isset($value['imageurl']) ? implode(",", $value['imageurl']) : null, // 聊天凭证
                'old_supplier_code' => $product_info['supplier_code'],
                'old_supplier_name' => $product_info['supplier_name'],
                'old_supplier_price' => $product_info['purchase_price'],
                'apply_content' => $remark,
                'status' => PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,
                'wms_stock' => $stock_numbers['available_stock'],
            ];

            if (true == $import && !$is_cancel) {
                $insert_data['status'] = PRODUCT_SCREE_STATUS_END;
                $insert_data['affirm_time'] = date("Y-m-d H:i:s");
                $insert_data['purchase_time'] = date("Y-m-d H:i:s");
            }
            if ($apply == PRODUCT_SCREE_APPLY_REASON_OUT_STOCK || $apply == PRODUCT_SCREE_APPLY_REASON_STOP_GOODS || $apply == PRODUCT_SCREE_APPLY_REASON_ZAOHUO) {

                $insert_data['estimate_time'] = $estimate;
            }

            if ($apply == PRODUCT_SCREE_APPLY_REASON_NEED_MINIMUM) {

                $insert_data['start_number'] = $estimate;
            }
            $result = $this->purchase_db->insert($this->table_name, $insert_data);
            $insertIds = $this->purchase_db->insert_id();
            if ($result && true == $import) {
                if ($insert_data['apply_remark'] == 2 || $insert_data['apply_remark'] == 99) {
                    $update_supply['supply_status'] = 2;
                } else if ($insert_data['apply_remark'] == 4 || $insert_data['apply_remark'] == 3) {
                    $update_supply['supply_status'] = 3;
                }

                if ($insert_data['apply_remark'] == 10) {
                    $update_supply['supply_status'] = $insert_data['apply_remark'];
                }
                if (isset($update_supply) && !empty($update_supply)) {

                    $this->product_model->update_erp_purchase_supplier_status($sku,$update_supply['supply_status']);

                    $productOldStatus = $this->purchase_db->from("product")
                        ->select("supply_status")
                        ->where("sku",$sku)
                        ->get()
                        ->row_array();
                    $this->product_model->_push_rabbitmq_data($sku, $productOldStatus['supply_status'], $update_supply['supply_status']);
                    $this->product_model->update_product_supply_status($sku,$update_supply['supply_status'],$productOldStatus['supply_status']);

                    $scree_log = array(
                        'username' =>getActiveUserName(),
                        'operation_time' => date('Y-m-d H:i:s',time()),
                        'operation_type' => $is_cancel? "取消未到货生成" : '导入数据系统自动审核通过',
                        'operation_content' =>  $is_cancel? "取消未到货生成" : "审核通过,产品货源状态变更为:".(($update_supply['supply_status'] == 2)?"停产":"断货"),
                        'scree_id' => $insertIds,
                        'remark'   => $remark,
                        'sku'      => $insert_data['sku']
                    );

                    $scree_log['supply_status_ch'] = isset($supplyStatusList[$update_supply['supply_status']])?$supplyStatusList[$update_supply['supply_status']]:'正常';

                    $this->purchase_db->insert('pur_product_scree_log', $scree_log);
                }

                if ($insert_data['apply_remark'] == 4) {
                    $this->update_suggest_estimate_time($insert_data['sku'], $insert_data['estimate_time']);
                }
            }

            if (!$result && true == $import) {
                if ($value['apply_remark'] == 2) {

                    $applyremark = '停产';
                } else if ($value['apply_remark'] == 4) {
                    $applyremark = '缺货';
                } else if ($value['apply_remark'] == 99) {
                    $applyremark = '需要起订量';
                } else if ($value['apply_remark'] == 10) {
                    $applyremark = '停产找货中';
                }

                if ($apply == PRODUCT_SCREE_APPLY_REASON_NEED_MINIMUM) {

                    $value = [
                        $sku,
                        $applyremark,
                        '',
                        $estimate,
                        $remark,
                        "sku:" . $sku . ",数据导入失败",
                    ];
                    $error_arr[] = $value;
                } else {

                    $value = [
                        $sku,
                        $applyremark,
                        $estimate,
                        '',
                        $remark,
                        "sku:" . $sku . ",数据导入失败",
                    ];
                    $error_arr[] = $value;
                }

            } else if (!$result) {
                $error_arr[] = $value['sku'];
            }
        }

        if (!empty($error_arr)) {

            return $error_arr;
        }

        return true;
    }

    /**
     * 添加一个 SKU到屏蔽列表
     * @param array $addInfo  屏蔽列表信息
     * @return array
     * @example $addInfo = array(
     *              'sku' => 'abc',
     *              'apply_remark' => '申请原因'
     *          )
     */
    public function scree_create($addInfo, $status = false)
    {
        $return = ['code' => false, 'data' => '', 'msg' => ''];

        if (!isset($addInfo['sku']) or empty($addInfo['sku'])) {
            $return['msg'] = 'sku不存在';
            return $return;
        }
        $sku = trim($addInfo['sku']);
        $product_info = $this->product_model->get_product_info($sku); // 获取产品信息
        if (empty($product_info)) {
            $return['msg'] = 'sku不存在';
            return $return;
        }

        // 验证是否已经存在 待处理的 SKU屏蔽 记录
        $old_scree = $this->purchase_db->where(['sku' => $sku])
            ->where_in('status', [PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT, PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM, PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM])
            ->get($this->table_name)
            ->row_array();

        if ($old_scree) {
            $return['msg'] = '此SKU存在未完结状态的申请';
            return $return;
        }

        // 添加数据
        $insert_data = [
            'sku' => $sku,
            'product_name' => $product_info['product_name'],
            'product_line_id' => $product_info['product_line_id'],
            'product_line_name' => getProductLineName($product_info['product_line_id']),
            'product_img_url' => $product_info['product_img_url'],
            'days_sales_30' => $product_info['days_sales_30'], // 获取SKU 30天销量
            'developer_id' => $product_info['create_id'],
            'developer_name' => $product_info['create_user_name'], // 开发员
            'apply_user_id' => getActiveUserId(),
            'apply_user' => getActiveUserName(),
            'apply_time' => date('Y-m-d H:i:s'),
            'apply_remark' => $addInfo['apply'], // 申请原因
            'chat_evidence' => is_array($addInfo['chat_evidence']) ? implode(',', $addInfo['chat_evidence']) : $addInfo['chat_evidence'], // 聊天凭证
            'old_supplier_code' => $product_info['supplier_code'],
            'old_supplier_name' => $product_info['supplier_name'],
            'old_supplier_price' => $product_info['purchase_price'],
            'status' => $status !== false ? $status : PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT,
            'apply_content' => $addInfo['apply_content'],
        ];
        if (isset($addInfo['estimate'])) {
            $insert_data['estimate_time'] = $addInfo['estimate'];
        }

        $result = $this->purchase_db->insert($this->table_name, $insert_data);
        if ($result) {
            $return['code'] = true;
        } else {
            $return['msg'] = '数据保存时出错';
        }

        return $return;
    }

    /**
     * 修改备货单，预计到货时间
     * @param: $sku            string         产品SKU
     *         $estimage_time  string         预计到货时间
     * @author:luxu
     **/

    public function update_suggest_estimate_time($sku, $estimate_time)
    {
        if (empty($sku) || empty($estimate_time)) {
            return false;
        }
        //更新预计供货时间
        $suggest_data = $this->purchase_db->where("sku", $sku)->update('purchase_suggest', ['estimate_time' => $estimate_time]);
        if ($suggest_data) {
            return true;
        }
        return false;
    }

    /**
     * 审核 SKU屏蔽列表
     * @author Jolon
     * @param int    $scree_id      屏蔽列表ID
     * @param int    $check_status  审核结果（1.审核通过，2.驳回）
     * @param string $reject_remark 驳回原因
     * @return array
     */
    public function scree_audit($scree_id, $check_status, $reject_remark)
    {
        $return = ['code' => false, 'data' => '', 'msg' => ''];

        $scree_info = $this->get_scree_one($scree_id);
        if (empty($scree_info) || $scree_info['status'] != PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT) {
            $return['msg'] = '记录状态不对,非[待采购经理审核]状态';
            return $return;
        }
        if ($check_status == 1) { // 审核通过
            $new_status = PRODUCT_SCREE_STATUS_END; // 审核完毕
            // 采购经理审核通过，采购系统根据申请原因，对SKU 货源状态做处理 apply_remark
            // 2: "停产", 4: "缺货", 99: "需要起订量"
            $apply_remark = $this->purchase_db->from('product_scree')->where("id={$scree_id}")->select("apply_remark,sku,estimate_time")->get()->row_array();
            // $this->purchase_db->where('sku',$apply_remark['sku'])->update('product',['total_scree_num'=>""]);
            if( !empty($apply_remark) ) {
                if( $apply_remark['apply_remark'] == 2 || $apply_remark['apply_remark'] == 99 ){
                    $update_supply['supply_status'] = 2;
                }else if(in_array($apply_remark['apply_remark'], [3, 4])){
                    $update_supply['supply_status'] = 3;
                }else if($apply_remark['apply_remark'] == 10){
                    $update_supply['supply_status'] = $apply_remark['apply_remark'];
                }
                if( isset($update_supply) && !empty($update_supply)) {
                    $this->product_model->update_erp_purchase_supplier_status($apply_remark['sku'],$update_supply['supply_status']);

                    $productOldStatus = $this->purchase_db->from("product")
                        ->select("supply_status")
                        ->where("sku",$apply_remark['sku'])
                        ->get()->row_array();
                    $this->product_model->_push_rabbitmq_data($apply_remark['sku'], $productOldStatus['supply_status'], $update_supply['supply_status']);

                    $this->product_model->update_product_supply_status($apply_remark['sku'],$update_supply['supply_status'],$productOldStatus['supply_status']);
                }
            }
            $this->purchase_db->query("UPDATE pur_product SET total_scree_num = total_scree_num+1 WHERE sku = '".$apply_remark['sku']."'");
        } else {
            $new_status = PRODUCT_SCREE_STATUS_PURCHASE_REJECTED; // 11.采购经理驳回

            $this->Message_model->AcceptMessage('scree', ['data' => [$scree_id], 'message' => $reject_remark, 'user' => getActiveUserName(), 'type' => '待采购经理']);
        }

        $result = $this->update_status($scree_id, $new_status, $reject_remark);
        if ($result) {
            // 货源状态变更为:'.($update_supply['supply_status'] == 2)?"停产":"断货",
            $scree_log = array(

                'username' => getActiveUserName(),
                'operation_time' => date('Y-m-d H:i:s', time()),
                'operation_type' => '采购经理审核',
                'operation_content' => ($check_status == 1) ? "审核通过,产品货源状态变更为:" . (($update_supply['supply_status'] == 2) ? "停产" : "断货") : "审核驳回 驳回原因:" . $reject_remark,
                'scree_id' => $scree_id,
                'remark' => $reject_remark,
                'sku' => $scree_info['sku'],
            );

            if ($check_status == 1) {

                $this->load->helper('status_product');
                $supplyStatusList = getProductsupplystatus();

                $scree_log['supply_status_ch'] = isset($supplyStatusList[$update_supply['supply_status']])?$supplyStatusList[$update_supply['supply_status']]:'正常';

                $this->purchase_db->insert('pur_product_scree_log', $scree_log);

                if ($apply_remark['apply_remark'] == 4) {
                    $this->update_suggest_estimate_time($apply_remark['sku'], $apply_remark['estimate_time']);
                }
            }
            $return['code'] = true;
        } else {
            $return['msg'] = $result['msg'];
        }

        return $return;
    }

    private function send_http($url, $data = null)
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
     * function: 审核结果推送到产品系统
     * @param: $scree_id    int   屏蔽SKU ID
     *         $check_status  int   审核结果
     *         $affirm_remark   string   驳回备注
     **/
    private function push_to_product($sku, $operater, $scree_id, $check_status, $affirm_remark)
    {

        if (!empty($this->config->item('product_system'))) {

            $product_system = $this->config->item('product_system');
            $_url_push_to_push = isset($product_system['sku_affirm_to_product']) ? $product_system['sku_affirm_to_product'] : '';

            if (empty($_url_push_to_push)) {
                exit('产品系统地址配置信息 IP或product_scree->sku_scree_to_product 参数缺失');
            }

            $apply_code = $apply_name = null;
            if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', trim($operater), $arr)) {
                $apply_code = str_replace($arr[0], '', $operater);
                $apply_name = $arr[0];

            }

            $send_to_product = array(

                'sku' => $sku,
                'skuId' => $scree_id,
                'operateName' => $apply_name,
                'operateId' => $apply_code,
            );

            if ($check_status == 1) {

                $send_to_product['status'] = 6;
            }

            if ($check_status == 0) {

                $send_to_product['status'] = 7;
                $send_to_product['operateContent'] = $affirm_remark;
            }

            $_url_push_to_push = $_url_push_to_push . "?access_token=" . getOASystemAccessToken();
            $response = $this->send_http($_url_push_to_push, $send_to_product);
            operatorLogInsert(
                [
                    'id' => $scree_id,
                    'type' => $this->table_name,
                    'content' => '采购确认推送产品系统',
                    'detail' => json_encode($send_to_product) . $response,
                ]);
            return ['code' => true, 'data' => '', 'msg' => ''];
        }
    }

    /**
     * 采购确认 - 替换供应商
     * @author Jolon
     * @param int    $scree_id      屏蔽列表ID
     * @param int    $check_status  审核结果(1.审核通过,其他.审核驳回)
     * @param string $affirm_remark 驳回原因
     * @return array
     */
    public function affirm_supplier($scree_id, $check_status, $affirm_remark)
    {
        $return = ['code' => false, 'data' => '', 'msg' => ''];

        $scree_info = $this->get_scree_one($scree_id);
        if (empty($scree_info) or $scree_info['status'] != PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM) {
            $return['msg'] = '记录状态不对,非[待采购确认]状态';
            return $return;
        }
        $erp_system = $this->config->item('erp_system');

        $this->load->model('product_model', '', false, 'product');
        try {
            $this->purchase_db->trans_strict(true);
            $this->purchase_db->trans_begin();

            // 确认人 确认信息
            $update_data = [
                'affirm_user' => getActiveUserName(),
                'affirm_time' => date('Y-m-d H:i:s'),
            ];
            $audit_flag = null;
            if ($check_status == 1) { // 审核通过
                $update_data['affirm_remark'] = '';
                $new_status = PRODUCT_SCREE_STATUS_CHANGED; // 40.以变更
                $result2 = $this->update_status($scree_id, $new_status); // 更新状态
                if (!$result2['code']) {
                    throw new Exception($result2['msg']);
                }
                $audit_flag = "AGREE";
            } else {
                $update_data['affirm_remark'] = $affirm_remark;
                $update_data['status'] = PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM;
                $new_status = PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM; //待开发确认
                $audit_flag = "DISAGREE";
            }

            $result2 = $this->purchase_db->where('id', $scree_id)->update($this->table_name, $update_data);
            if (!$result2) {
                throw new Exception('屏蔽列表数据库操作失败');
            } else {
                operatorLogInsert(
                    [
                        'id' => $scree_id,
                        'type' => $this->table_name,
                        'content' => '采购确认',
                        'detail' => ($new_status == PRODUCT_SCREE_STATUS_CHANGED) ? '通过' : '驳回：' . $affirm_remark,
                    ]);
            }

            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('SKU屏蔽列表-采购确认操作事务提交出错');
            } else {
                $this->purchase_db->trans_commit();
            }

            $scree_log = array(

                'username' => getActiveUserName(),
                'operation_time' => date('Y-m-d H:i:s', time()),
                'operation_type' => '采购确认',
                'operation_content' => ($check_status == 1) ? "审核通过" : "审核驳回 驳回原因:" . $affirm_remark,
                'scree_id' => $scree_id,
            );
            $this->purchase_db->insert('pur_product_scree_log', $scree_log);

            if ($scree_info['is_product'] == 1) {

                // 推送SKU 采购确认信息到产品系统
                $return = $this->push_to_product($scree_info['sku'], getActiveUserName(), $scree_id, $check_status, $affirm_remark);
            } else {
                $scree_log = array(

                    'username' => getActiveUserName(),
                    'operation_time' => date('Y-m-d H:i:s', time()),
                    'operation_type' => '采购确认',
                    'operation_content' => ($audit_flag == "AGREE") ? "采购确认通过" : "采购确认驳回 驳回原因:" . $affirm_remark,
                    'scree_id' => $scree_id,
                );
                $this->purchase_db->insert('pur_product_scree_log', $scree_log);

                // 推送审核结果到ERP 系统
                $send_erp_data = array(

                    "sku" => $scree_info['sku'],
                    "check_user" => getActiveUserName(),
                    "check_reason" => $scree_info['affirm_remark'],
                    "new_supplier_name" => $scree_info['new_supplier_name'],
                    "check_status" => $audit_flag,
                );

                $_url_erp_ip = isset($erp_system['ip']) ? $erp_system['ip'] : '';
                $_url_push_to_erp = isset($erp_system['product_scree']['send_supplier_audit']) ? $erp_system['product_scree']['send_supplier_audit'] : '';
                $result = $this->send_http($_url_push_to_erp, ['block_list' => [$send_erp_data]]);
                operatorLogInsert([
                    'id' => 0,
                    'type' => $this->table_name,
                    'content' => '采购确认ERP返回',
                    'detail' => ['block_list' => [$send_erp_data], 'result' => $result],
                ]);
                $return = ['code' => true, 'data' => '', 'msg' => ''];
            }
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return = ['code' => false, 'msg' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * 更新数据
     * @author Jolon
     * @param int   $scree_id  ID
     * @param array $params    更新的键值
     * @return bool
     */
    public function scree_update($scree_id, $params)
    {
        $scree_info = $this->get_scree_one($scree_id);

        // 状态变更
        if (isset($params['status']) and $params['status'] != $scree_info['status']) {
            $result = $this->update_status($scree_id, $params['status']);
            if (empty($result)) {
                return false;
            }
        }

        $result = $this->purchase_db->where('id', $scree_id)->update($this->table_name, $params);
        if ($result) {
            tableChangeLogInsert($params); // 自动保存变更的数据
            return true;
        } else {
            return false;
        }
    }

    /**
     * 更新 SKU屏蔽列表状态（自动插入变更日志）
     * @author Jolon
     * @param int $scree_id  屏蔽列表ID
     * @param int $new_status 新状态
     * @return array
     */
    public function update_status($scree_id, $new_status, $remark = null)
    {
        $return = ['code' => false, 'data' => '', 'msg' => ''];

        $scree_info = $this->get_scree_one($scree_id);
        $update_data = ['status' => $new_status, 'affirm_time' => date("Y-m-d H:i:s", time()), 'affirm_user' => getActiveUserName(),
            'purchase_person' => getActiveUserName(), 'purchase_time' => date("Y-m-d H:i:s", time()),
            'purchase_remark' => $remark,
        ];
        if (null != $remark) {
            $update_data['audit_remark'] = $remark;
        }
        if (is_array($scree_id)) {
            $result = $this->purchase_db->where_in('id', $scree_id)->update($this->table_name, $update_data);
        } else {
            $result = $this->purchase_db->where('id', $scree_id)->update($this->table_name, $update_data);
        }
        if ($result) {
            $old_status = getProductScreeStatus($scree_info['status']);
            $new_status = getProductScreeStatus($new_status);
            $status_change = '审核屏蔽记录成功，状态从【' . $old_status . '】改为【' . $new_status . '】';

            operatorLogInsert(
                [
                    'id' => $scree_id,
                    'type' => $this->table_name,
                    'content' => '采购经理审核',
                    'detail' => $status_change,
                ]);

            $return['code'] = true;
            return $return;
        } else {
            $return['msg'] = '更新数据时出错';
            return $return;
        }
    }

    /**
     * 删除 一个SKUP屏蔽里诶啊哦数据
     * @author Jolon
     * @param int $scree_id  屏蔽列表ID
     * @return array
     */
    public function screen_delete($scree_id)
    {
        $return = ['code' => false, 'data' => '', 'msg' => ''];

        $scree_info = $this->get_scree_one($scree_id);
        if ($scree_info) {
            if ($scree_info['status'] > PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT) {
                $return['msg'] = '非待审状态不能删除';
                return $return;
            }
            $res = $this->purchase_db->where('id', $scree_id)->delete($this->table_name);
            if ($res) {
                operatorLogInsert(
                    [
                        'id' => $scree_id,
                        'type' => $this->table_name,
                        'content' => '删除记录',
                        'detail' => $scree_info,
                    ]);
                $return['code'] = true;
            } else {
                $return['msg'] = '删除数据时失败';
            }
        } else {
            $return['msg'] = '未找到记录';
        }

        return $return;

    }

    /**
     * @desc 获取未推送替换供应商的审核后的sku屏蔽数据
     * @author Jeff
     * @Date 2019/5/8 19:17
     * @param null $offset
     * @param null $limit
     * @param int $page
     * @return array
     * @return
     */
    public function get_un_push_audit_scree($offset = null, $limit = null)
    {

        $query_builder = $this->purchase_db;
        $query_builder = $query_builder->where('is_push_change_supplier', 0);
        $query_builder = $query_builder->where('affirm_user!=', '');
        $query_builder = $query_builder->where_in('status', [PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM, PRODUCT_SCREE_STATUS_CHANGED]);

        $results = $query_builder->order_by('apply_time', 'desc')->get($this->table_name, $limit, $offset)->result_array();
        /*$return_data = [
        'data_list'   => $results,
        'paging_data' => [
        'total'     => $total_count,
        'offset'    => $page,
        'limit'     => $limit,
        ]
        ];*/

        return $results;
    }

    public function get_product_sku_scree($skus)
    {

        return $this->purchase_db->from("pur_product_scree")->select("sku")->where_in("sku", $skus)->where_in("status", [PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT, PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM, PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM])->get()->row_array();

    }

    public function get_product_sku_scree_array($skus)
    {

        $result = $this->purchase_db->from("pur_product_scree")->select("sku,MAX(estimate_time) AS estimate_time")->where_in("sku", $skus)
            ->where_in("status", [PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT, PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM, PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM])->group_by("sku")->get()->result_array();
        return array_column($result, 'sku');
    }

    /**
     * function:获取SKU 屏蔽操作日志
     * @param  $scree_id    int     SKU 屏蔽ID
     * @return array     日志信息
     **/

    public function get_scree_log($scree_id, $sku = null)
    {

        if (null == $sku) {
            return $this->purchase_db->from("pur_product_scree_log")->where("scree_id", $scree_id)->order_by("id DESC")->get()->result_array();
        } else if (null != $sku) {
            return $this->purchase_db->from("pur_product_scree_log")->where("sku", $sku)->where_in("operation_type", [ESTIMATED_TIME, PURCHASEING_MANAGER, MODIFY_SOURCE])->get()->result_array();
        }
    }

    /**
     * SKU 屏蔽预计到货时间修改
     * @param:  $param   array  HTTP 传入参数
     **/
    public function update_estimate_time($param)
    {
        try {
            // 判断SKU 状态
            if (!isset($param['scree_id']) || empty($param['scree_id'])) {
                throw new Exception("请传入SKU 屏蔽ID", 404);
            }

            $scree_message = $this->purchase_db->from("product_scree")->where("id", $param['scree_id'])->get()->row_array();
            if ($scree_message['apply_remark'] != 4 and $scree_message['apply_remark'] != 3) {
                throw new Exception("SKU:" . $scree_message['sku'] . " 申请屏蔽原因不为断货，不能修改预计到货时间", 404);
            }
            if (empty($scree_message)) {
                throw new Exception("SKU 不存在", 404);
            }
            if ($scree_message['status'] != PRODUCT_SCREE_STATUS_END) {
                throw new Exception("sku:" . $scree_message['sku'] . "处于屏蔽申请中，只有已结束状态才可修改", 505);
            }

            if (!isset($param['estimate_time']) || $param['estimate_time'] < date("Y-m-d H:i:s")) {
                throw new Exception("sku:" . $scree_message['sku'] . "已经超过预计供货时间，无法修改，请申请sku屏蔽", 505);
            }

            $update_arr = [
                'estimate_time' => $param['estimate_time'],
                'estimate_image' => $param['estimate_image'],
                'estimate_remark' => $param['remark'],
                'chat_evidence' => $param['estimate_image'],
            ];
            $update_result = $this->purchase_db->where("id", $param['scree_id'])->update("product_scree", $update_arr);

            if ($update_result) {
                // 处理备货单
                $this->update_suggest_estimate_time($scree_message['sku'], $param['estimate_time']);
                $logs = array(

                    'username' => getActiveUserName(),
                    'operation_time' => date("Y-m-d H:i:s"),
                    'operation_content' => '修改为:' . $param['estimate_time'],
                    'operation_type' => '修改预计供货时间',
                    'remark' => $param['remark'],
                    'image' => $param['estimate_image'],
                    'scree_id' => $param['scree_id'],
                );
                $this->purchase_db->insert('pur_product_scree_log', $logs);

                //推送erp信息状态改为待推送
                if ($scree_message['status'] == PRODUCT_SCREE_STATUS_END) {
                    $this->purchase_db->where("sku", $scree_message['sku'])->where("id", $param['scree_id'])->update('product_scree', ['is_push_erp_queue' => 2]);
                    $this->handle_push_to_erp($scree_message['sku']);
                }

                return true;
            }
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage());
        }
    }

    public function get_scree_estimatetime($scree_id)
    {
        return $this->purchase_db->from("pur_product_scree_log")->where("scree_id", $scree_id)->order_by("id DESC")->get()->result_array();
    }

    /**
     * 修改预计供货时间和状态变为已结束时推送
     * @author Manson
     * @param string $sku_list
     */
    public function handle_push_to_erp($sku_list = '')
    {
        if (!empty($sku_list)) {
            $params['sku'] = $sku_list;
        }
        $i = 0;
        $max_i = 3; //执行3次
        while (true) {
            $result = $this->get_push_data($sku_list);
            if (empty($result)) {
                break;
            } else {
                $i++;
            }
            if ($i > $max_i) {
                break;
            }
            $this->push_to_erp($result);
        }
    }

    /**
     * 获取要推送的数据
     * @author Manson
     * @param $params
     */
    public function get_push_data($params)
    {
        $this->purchase_db->select('id,sku,estimate_time')
            ->from('pur_product_scree')
            ->where('is_push_erp_queue', 2) //待推送
            ->where('status', PRODUCT_SCREE_STATUS_END); //已结束
        if (isset($params['id']) && !empty($params['id'])) {
            $this->purchase_db->where_in('id', query_string_to_array($params['id']));
        }

        if (isset($params['sku']) && !empty($params['sku'])) {
            $this->purchase_db->where_in('sku', query_string_to_array($params['sku']));
        }

        if (isset($params['limit']) && !empty($params['limit'])) {
            $this->purchase_db->limit($params['limit']);
        } else {
            $this->purchase_db->limit(200);
        }

        $result = $this->purchase_db->order_by('id', 'asc')->get()->result_array();

        return $result;
    }

    /**
     * 判断条件如下 ：yibai_prod_sku_select_attr.attr_type_id=1是 attr_value_id 只含有67为国内仓。含有其他id的订单属性为海外仓
     * sku  array|string  SKU
     * 获取SKU是否海外仓
     * @author:luxu
     * @time:2020/7/21
     **/
    private function getSkuWarehousedata($skus)
    {

        $skusData = array_column($skus, "sku");
        $tmsIds = $this->getProdAttributesTms();
        $tmsIds = array_column($tmsIds, "id");
        $result = $this->getSkuWarehouse($skusData, $tmsIds);
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

    public function push_to_erp($params)
    {

        if (empty($params)) {
            throw new Exception('参数不能为空');
        }

        $sku_list = array_column($params, 'sku');

        //权均交期
        $delivSkus = $this->purchase_db->select('MIN(avg_delivery_time) AS avg_delivery_time,sku')
            ->from('sku_avg_delivery_time')
            ->where_in('sku', $sku_list)
            ->get()->result_array();
        $delivSkus = array_column($delivSkus, null, "sku");
        $productSkus = $this->purchase_db->from("product")->where_in("sku", $sku_list)->get()->result_array();
        $productSkus = array_column($productSkus, null, "sku");
        $getSkuWarehouse = $this->getSkuWarehousedata($productSkus);

        foreach ($params as $paramKeys => $paramValue) {

            if (isset($delivSkus[$paramValue['sku']]) && $delivSkus[$paramValue['sku']]['avg_delivery_time'] != 0.000) {

                //SKU 权均交期不等于0,预计到货时间=预计供货时间+最小交期
                $arrival_time = (int) bcadd(strtotime($paramValue['estimate_time']), $delivSkus[$paramValue['sku']]['avg_delivery_time'] * 3600 * 24);

                $arrival_time = date('Y-m-d H:i:s', $arrival_time);
                $avg_delivery_time = $delivSkus[$paramValue['sku']]['avg_delivery_time'];

            } else {

                //SKU 权均交期等于0，判断SKU 是海外仓或者 国内仓
                if (isset($getSkuWarehouse[$paramValue['sku']]) && $getSkuWarehouse[$paramValue['sku']] == 1) {
                    // 如果SKU 是海外仓,交期不等于0，预计到货时间 = 预计供货时间+交期
                    if (!empty($productSkus[$paramValue['sku']]['devliy']) && $productSkus[$paramValue['sku']]['devliy'] != 0.00) {

                        $arrival_time = (int) bcadd(strtotime($paramValue['estimate_time']), $productSkus[$paramValue['sku']]['devliy'] * 3600 * 24);
                        $avg_delivery_time = $productSkus[$paramValue['sku']]['devliy'];
                    } else {

                        if (!empty($productSkus[$paramValue['sku']]['original_devliy']) && $productSkus[$paramValue['sku']]['original_devliy'] != 0.00) {

                            $arrival_time = (int) bcadd(strtotime($paramValue['estimate_time']), $productSkus[$paramValue['sku']]['original_devliy'] * 3600 * 24);
                            $avg_delivery_time = $productSkus[$paramValue['sku']]['original_devliy'];
                        } else {
                            //交期等于0，预计到货时间 = 预计供货时间+40
                            $arrival_time = (int) bcadd(strtotime($paramValue['estimate_time']), 40 * 3600 * 24);
                            $avg_delivery_time = 40;
                        }
                    }

                } else {
                    //如果SKU 是国内仓,预计到货时间 = 预计供货时间+7
                    $arrival_time = (int) bcadd(strtotime($paramValue['estimate_time']), 7);
                    $avg_delivery_time = 7;
                }
                $arrival_time = date('Y-m-d H:i:s', $arrival_time);
            }
            $push_data[] = [
                'sku' => $paramValue['sku'],
                'estimate_time' => $paramValue['estimate_time'], //预计供货时间
                'avg_delivery_time' => $avg_delivery_time, //最小权均交期
                'arrival_time' => $arrival_time, //预计到货时间
            ];

            $update_data[] = [
                'id' => $paramValue['id'],
                'is_push_erp_queue' => 1,
                'push_erp_time' => date('Y-m-d H:i:s'),
            ];

        }

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
            'sku' => $paramValue['sku'],
            'pushdata' => json_encode($push_data),
            'source' => 2,
            'pushtime' => date("Y-m-d H:i:s", time()),
        ];

        $this->load->library('Rabbitmq');
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数

        $mq->setExchangeName('PURCHASE_SKU_SCREE_INFO_EX_NAME');
        $mq->setRouteKey('PURCHASE_SKU_SCREE_INFO_R_KEY');
        $mq->setType(AMQP_EX_TYPE_FANOUT); //设置为多消费者模式 分发
        //存入消息队列
        $mq->sendMessage($push_data);
        //修改推送状态
        $this->purchase_db->insert('sku_erp_log', $insertLogs);
        return $this->purchase_db->update_batch('product_scree', $update_data, 'id');
    }

    /**
     * 以sku维度推送推送至erp
     * @author Manson
     * @param $push_data
     */
    public function push_to_erp_1($params)
    {
        if (empty($params)) {
            throw new Exception('参数不能为空');
        }

        $update_data = [];
        $push_data = [];
        $sku_list = array_column($params, 'sku');

        //权均交期
        $avg_dt_info = $this->purchase_db->select('sku,avg_delivery_time')
            ->from('sku_avg_delivery_time')
            ->where_in('sku', $sku_list)
            ->order_by('avg_delivery_time', 'asc')
            ->get()->result_array();
        $avg_dt_map = [];
        foreach ($avg_dt_info as $item) {
            if (!isset($avg_dt_map[$item['sku']])) {
                $avg_dt_map[$item['sku']] = $item['avg_delivery_time'];
            }
        }
        unset($avg_dt_info);

        foreach ($params as $item) {
            $avg_delivery_time = isset($avg_dt_map[$item['sku']]) ? $avg_dt_map[$item['sku']] : 0;
            $estimate_time_temp = strtotime($item['estimate_time']);
            $arrival_time = (int) bcadd($estimate_time_temp, $avg_delivery_time);

            $arrival_time = date('Y-m-d H:i:s', $arrival_time);

            $push_data[] = [
                'sku' => $item['sku'],
                'estimate_time' => $item['estimate_time'], //预计供货时间
                'avg_delivery_time' => $avg_delivery_time, //最小权均交期
                'arrival_time' => $arrival_time, //预计到货时间
            ];

            $update_data[] = [
                'id' => $item['id'],
                'is_push_erp_queue' => 1,
                'push_erp_time' => date('Y-m-d H:i:s'),
            ];
        }

        $this->load->library('Rabbitmq');
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setExchangeName('PURCHASE_SKU_SCREE_INFO_EX_NAME');
        $mq->setRouteKey('PURCHASE_SKU_SCREE_INFO_R_KEY');
        $mq->setType(AMQP_EX_TYPE_FANOUT); //设置为多消费者模式 分发
        //存入消息队列
        $mq->sendMessage($push_data);
        //修改推送状态
        return $this->purchase_db->update_batch('product_scree', $update_data, 'id');

    }

    /**
     * 获取产品系统同步到采购系统表pur_prod_attributes_tms，订单属性
     * @author:luxu
     * @time:2020/8/6
     **/
    public function getProdAttributesTms()
    {

        return $this->purchase_db->from("prod_attributes_tms")->where("parent_id=", 66)->select("id")->get()->result_array();
    }

    public function getSkuWarehouse($skus, $tmsIds)
    {
        $result = $this->purchase_db->from("prod_sku_select_attr")->where_in("sku", $skus)->where("attr_type_id", 1)
            ->where("is_del", 0)->select("sku,id,attr_value_id")->get()->result_array();

        if (!empty($result)) {

            $data = [];
            foreach ($result as $key => $value) {

                if (!isset($data[$value['sku']])) {

                    $data[$value['sku']] = [];
                }
                if (in_array($value['attr_value_id'], $tmsIds)) {
                    $data[$value['sku']][] = $value['attr_value_id'];
                }
            }
            return $data;
        }
    }

    /**
     * 需求号:27731 SKU屏蔽申请弹框优化
     *  Sku后面增加一列数据“近100天找货次数”，按照申请时间往前推100天，
     *  以SKU维度统计SKU申请屏蔽原因等于“停产找货中”并且“审核通过”的次数；
     * -------------------需求变更（2021/11/10）--------------------------------
     * 需求变更（2021/11/10）：43947    【sku屏蔽申请】的近100天找货次数统计逻辑修改
     * @author:luxu
     * @time:2020/12/7
     **/

    public function getPrevData($skus, $startTime = null, $endTime = null)
    {

        try {

            // $result = $this->purchase_db->from("product_scree")->where_in("sku",$skus)->where("apply_time>=",$startTime)
            //     ->where("apply_time<=",$endTime)->where("status",50)->where("apply_remark",10)->group_by('sku')->select('COUNT(*) as count,sku')
            //     ->get()->result_array();
            //找货组id为16
            $goods_users = $this->User_group_model->getUserByGroup([16]);
            if(!empty($goods_users)){
                $this->purchase_db->where_in("apply_user_id", $goods_users);
            }
            $result = $this->purchase_db->from("product_scree")->where_in("sku", $skus)->where("apply_time>=", $startTime)->where("apply_time<=", $endTime)->where("status", 50)->group_by('sku')->select('COUNT(*) as count,sku')->get()->result_array();
            $applyData = [];
            if (!empty($result)) {

                $applyData = array_column($result, null, 'sku');
            }
            $return = [];

            $productSalesData = $this->purchase_db->from("product")->where_in("sku", $skus)->select("supplier_code,supplier_name,sku,days_sales_30,days_sales_90")->get()->result_array();
            $salesData = array_column($productSalesData, null, "sku");
            $daytime = date("Y-m-d H:i:s", time());
            $prvetime = date("Y-m-d", strtotime("$daytime -10 day"));
            foreach ($skus as $key => $value) {

                $return[$value]['100_day_sales'] = isset($applyData[$value]) ? $applyData[$value]['count'] : 0;
                $return[$value]['90_day_sales'] = isset($salesData[$value]) ? $salesData[$value]['days_sales_90'] : 0;
                $return[$value]['30_day_sales'] = isset($salesData[$value]) ? $salesData[$value]['days_sales_30'] : 0;
                $return[$value]['supplier_name'] = isset($salesData[$value]) ? $salesData[$value]['supplier_name'] : '';
                $return[$value]['supplier_code'] = isset($salesData[$value]) ? $salesData[$value]['supplier_code'] : '';

                $skuLogs = $this->purchase_db->from("product_update_log")->where("sku", $value)->where("new_supplier_code!=old_supplier_code")
                    ->where("new_supplier_name!=old_supplier_name")->where("audit_status", 3)->where("audit_time>=", $prvetime)
                    ->where("audit_time<=", $daytime)->select("id")->get()->row_array();

                if (empty($skuLogs)) {
                    $return[$value]['is_update_supplier'] = 0;
                } else {
                    $return[$value]['is_update_supplier'] = 1;
                }
            }
            return $return;
        } catch (Exception $exp) {

        }
    }
}
