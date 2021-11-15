<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/1/9
 * Time: 14:15
 */

class Check_product extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('check/Check_product_model', 'check_product_model');
    }

    /**
     * 获取验货列表数据
     * /supplier/check/check_product/get_data_list?uid=1528&purchase_type_id=&product_line_id=&enable_status=&sort_by=&offset=1&limit=20
     */
    public function get_data_list()
    {
        $params = [
            'check_code' => $this->input->get_post('check_code'),                           // 申请编码
            'sku' => $this->input->get_post('sku'),                                         // sku
            'purchase_number' => $this->input->get_post('purchase_number'),                 // 采购单
            'supplier_code' => $this->input->get_post('supplier_code'),                     // 供应商
            'demand_number' => $this->input->get_post('demand_number'),                     // 备货单号
            'apply_user_id' => $this->input->get_post('apply_user_id'),                     // 申请人
            'is_abnormal' => $this->input->get_post('is_abnormal'),                         // 是否异常
            'buyer_id' => $this->input->get_post('buyer_id'),                               // 采购员
            'confirm_user_id' => $this->input->get_post('confirm_user_id'),                 // 提交人
            'is_urgent' => $this->input->get_post('is_urgent'),                             // 是否加急
            'purchase_type_id' => $this->input->get_post('purchase_type_id'),               // 业务线
            'status' => $this->input->get_post('status'),                                   // 检验状态
            'product_line_id' => $this->input->get_post('product_line_id'),                 // 一级产品线
            'create_time_start' => $this->input->get_post('create_time_start'),             // 申请时间开始
            'create_time_end' => $this->input->get_post('create_time_end'),                 // 申请时间结束
            'check_expect_time_start' => $this->input->get_post('check_expect_time_start'), // 期望验货时间开始
            'check_expect_time_end' => $this->input->get_post('check_expect_time_end'),     // 期望验货时间结束
            'check_time_start' => $this->input->get_post('check_time_start'),               // 实际验货时间开始
            'check_time_end' => $this->input->get_post('check_time_end'),                   // 实际验货时间结束
            'is_special' => $this->input->get_post('is_special'),                           // 特批出货
            'check_times' => $this->input->get_post('check_times'),                         // 检验次数
            'supplier' => $this->input->get_post('supplier'),                               // 供应商
            'user_group' => $this->input->get_post('user_group'),                           // 采购组别
        ];
        if( isset($params['user_group']) && !empty($params['user_group'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['user_group']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) OR $page < 0) {
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data = $this->check_product_model->get_list_data($params, $offsets, $limit, $page);
        $this->success_json($data);
    }

    /**
     * 获取批量确认数据
     * /supplier/check/check_product/get_check_batch_confirm_data
     * 32107
     */
    public function get_check_batch_confirm_data()
    {
        $ids = $this->input->get_post('ids');   // 申请编码
        $data = $this->check_product_model->get_check_batch_confirm_data($ids);
    }

    /**
     * 保存批量确认
     * /supplier/check/check_product/check_batch_confirm_save
     */
    public function check_batch_confirm_save()
    {}


    /**
     * 获取PO详情
     * /supplier/check/check_product/get_po_detail?uid=1528&purchase_number=
     */
    public function get_po_detail()
    {
        $purchase_number = $this->input->get_post('purchase_number');// 采购单号
        $id = $this->input->get_post('id');// ID

        if (empty($purchase_number) OR empty($id)) {
            $this->error_json('采购单号和ID都不能为空');
        }
        $data = $this->check_product_model->get_po_detail($purchase_number, $id);
        $this->success_json($data);
    }

    /**
     * 手工创建验货单-验证验货PO是否属于等待到货及之后的状态，并返回sku和供应商信息
     * /supplier/check/check_product/check_po_status?uid=1528&purchase_number=PO000091
     */
    public function check_po_status()
    {
        // 采购单号
        $purchase_number = $this->input->get_post('purchase_number');
        if (empty($purchase_number)) {
            $this->error_json('验货PO不能为空');
        }

        $result = $this->check_product_model->check_po_status($purchase_number);

        if ($result['status']) {
            $this->success_json($result['data']);
        } else {
            $this->error_json($result['msg']);
        }
    }

    /**
     * 获取备货单信息
     */
    public function get_check_suggest_data()
    {
        $suggest = $this->input->get_post('suggest_number');
        if(!empty($suggest))$suggest = explode(" ", $suggest);
        if(empty($suggest))$this->error_json("备货单号不能为空！");
        $res = $this->check_product_model->get_check_suggest_data($suggest);
        if(!empty($res))$this->success_json($res, [], "获取成功！");
        $this->error_json("暂无数据！");
    }

    /**
     * 创建验货列表
     * /supplier/check/check_product/create_inspection
     */
    public function create_inspection()
    {
        $contentType = !empty($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        $data = null;
        if (strpos($contentType, 'json') > 0) {
            $params = file_get_contents('php://input');
            $params = json_decode($params, true);
            $data = $params['data'];
        }

        if(empty($data)){
            $this->error_json('提交的数据错误！');
        }

        $res_success = [];
        $res_error = [];
        foreach ($data as $val){
            $pur = $val['purchase_number'];
            if(empty($pur)){
                $this->error_json($pur.'验货PO不能为空');
            }elseif(empty($val['sku'])){
                $this->error_json($pur.'验货PO的SKU不能为空');
            } elseif (!in_array($val['is_urgent'], [0, 1])) {
                $this->error_json($pur.'是否加急参数错误');
            } elseif (empty($val['contact_person'])) {
                $this->error_json($pur.'联系人不能为空');
            } elseif ((empty($val['contact_province']) || empty($val['contact_city']) || empty($val['contact_area'])) || empty($val['address'])) {
                $this->error_json($pur.'联系地址不完整');
            } elseif (!in_array($val['order_type'], [1, 2, 3, 4])) {
                $this->error_json($pur.'验货类型参数错误');
            }

            $res_one = $this->check_product_model->create_inspection($val['purchase_number'], $val);
            if ($res_one['flag']) {
                $res_success[] = $val['purchase_number'];
            } else {
                $res_error[] = $val['purchase_number'].$res_one['msg'];
            }
        }

        if(count($res_success) > 0){
            $msg = '创建成功';
            if(count($res_error) > 0){
                $msg = '部分创建成功！失败部分：'.implode(",", $res_error);
            }
            $this->success_json([], null, $msg);
        }
        $this->error_json('创建验货记录失败！');
    }

    /**
     * 手工创建验货单（提交）(废弃--yefanli)
     * /supplier/check/check_product/create_inspection?uid=1528&purchase_number=PO000091
     */
    public function create_inspection_bak()
    {
        $purchase_number = trim($this->input->get_post('purchase_number'));     //验货po
        $sku = $this->input->get_post('sku');                                   //验货sku（多个sku用逗号分隔）
        $is_urgent = $this->input->get_post('is_urgent');                       //是否加急(0-否，1-是)
        $contact_person = $this->input->get_post('contact_person');             //联系人
        $phone_number = $this->input->get_post('phone_number');                 //联系电话
        $contact_province = $this->input->get_post('contact_province');         //（联系地址）所在省id
        $contact_city = $this->input->get_post('contact_city');                 //（联系地址）所在市id
        $contact_area = $this->input->get_post('contact_area');                 //（联系地址）所在区id
        $contact_address = trim($this->input->get_post('address'));             //（联系地址）详细地址
        $complete_address = trim($this->input->get_post('complete_address'));   //完整的联系地址
        $order_type = $this->input->get_post('type');                           //验货类型（1-常规，2-首次，3-直发，4-客诉）
        $remark = $this->input->get_post('remark');                             //申请备注
        $sku = array_filter(explode(',', $sku));

        //验证必填字段
        if (empty($purchase_number)) {
            $this->error_json('验货PO不能为空');
        } elseif (empty($sku)) {
            $this->error_json('验货SKU不能为空');
        } elseif (!in_array($is_urgent, [0, 1])) {
            $this->error_json('是否加急参数错误');
        } elseif (empty($contact_person)) {
            $this->error_json('联系人不能为空');
        } elseif ((empty($contact_province) && empty($contact_city) && empty($contact_area)) OR empty($contact_address) OR empty($complete_address)) {
            $this->error_json('联系地址不完整');
        } elseif (!in_array($order_type, [1, 2, 3, 4])) {
            $this->error_json('验货类型参数错误');
        }

        $params = array(
            'sku' => $sku,
            'is_urgent' => $is_urgent,
            'contact_person' => $contact_person,
            'phone_number' => $phone_number,
            'contact_province' => $contact_province,
            'contact_city' => $contact_city,
            'contact_area' => $contact_area,
            'contact_address' => $contact_address,
            'complete_address' => $complete_address,
            'order_type' => $order_type,
            'remark' => $remark,
        );

        $data = $this->check_product_model->create_inspection($purchase_number, $params);
        if ($data['flag']) {
            $this->success_json([], null, '创建成功');
        } else {
            $this->error_json($data['msg']);
        }
    }

    /**
     * 根据验货ID获取数据（采购确认和编辑页面展示数据）
     * /supplier/check/check_product/get_order_detail?uid=1528&id=62
     */
    public function get_order_detail()
    {
        $id = $this->input->get_post('id');
        $type = $this->input->get_post('type');//（默认：1-采购确认页面，2-编辑页面）
        $type = empty($type) ? 1 : $type;

        if (empty($id)) {
            $this->error_json('请求参数id不能为空');
        } elseif (!in_array($type, [1, 2])) {
            $this->error_json('请求类型参数错误');
        }
        $data = $this->check_product_model->get_order_detail($id, $type);
        if ($data['flag']) {
            $this->success_json($data['data']);
        } else {
            $this->error_json('没有获取到验货单详细信息');
        }
    }

    /**
     * 采购确认（提交）
     * /supplier/check/check_product/order_confirm
     */
    public function order_confirm()
    {
        $id = trim($this->input->get_post('id'));                               //id
        $is_urgent = $this->input->get_post('is_urgent');                       //是否加急（0-否，1-是）
        $contact_person = $this->input->get_post('contact_person');             //联系人
        $phone_number = $this->input->get_post('phone_number');                 //联系电话
        $contact_province = $this->input->get_post('contact_province');         //（联系地址）所在省id
        $contact_city = $this->input->get_post('contact_city');                 //（联系地址）所在市id
        $contact_area = $this->input->get_post('contact_area');                 //（联系地址）所在区id
        $contact_address = trim($this->input->get_post('address'));             //（联系地址）详细地址
        $complete_address = trim($this->input->get_post('complete_address'));   //完整的联系地址
        $order_type = $this->input->get_post('type');                           //验货类型（1-常规，2-首次，3-直发，4-客诉）
        $is_check = $this->input->get_post('is_check');                         //是否验货(1-验货，2-免检)
        $remark = $this->input->get_post('remark');                             //申请备注(是否验货为'免检'时,备注必填)
        $check_expect_time = $this->input->get_post('check_expect_time');       //期望验货时间
        $is_force_submit = $this->input->get_post('is_force_submit');           //是否强制提交（0-否，1-是）

        if (empty($id)) {
            $this->error_json('请求参数id不能为空');
        } elseif (!in_array($is_urgent, [0, 1])) {
            $this->error_json('是否加急参数错误');
        } elseif (!in_array($is_check, [1, 2])) {
            $this->error_json('是否验货参数错误');
        } elseif (1 == $is_check && (empty($check_expect_time) OR '0000-00-00' == $check_expect_time)) {
            $this->error_json('是否验货为‘验货’时,期望验货时间不能为空');
        } elseif (2 == $is_check && empty($remark)) {
            $this->error_json('是否验货为‘免检’时,申请备注不能为空');
        } elseif ((empty($contact_province) && empty($contact_city) && empty($contact_area)) OR empty($contact_address) OR empty($complete_address)) {
            $this->error_json('联系地址不完整');
        } elseif (!in_array($order_type, [1, 2, 3, 4])) {
            $this->error_json('验货类型参数错误');
        }

        $params = array(
            'is_urgent' => $is_urgent,
            'contact_person' => $contact_person,
            'phone_number' => $phone_number,
            'contact_province' => $contact_province,
            'contact_city' => $contact_city,
            'contact_area' => $contact_area,
            'contact_address' => $contact_address,
            'complete_address' => $complete_address,
            'order_type' => $order_type,
            'is_check' => $is_check,
            'remark' => $remark,
            'check_expect_time' => $check_expect_time,
            'is_force_submit' => $is_force_submit,
        );

        $data = $this->check_product_model->order_confirm($id, $params);
        if ($data['flag']) {
            $this->success_json([], null, '验货单确认成功');
        } else {
            $this->error_data_json(!empty($data['is_warning']) ? $data['is_warning'] : array(), $data['msg']);
        }
    }

    /**
     * 编辑（重验申请提交）
     * /supplier/check/check_product/order_edit
     */
    public function order_edit()
    {
        $id = trim($this->input->get_post('id'));                               //id
        $contact_person = $this->input->get_post('contact_person');             //联系人
        $phone_number = $this->input->get_post('phone_number');                 //联系电话
        $remark = $this->input->get_post('remark');                             //申请备注
        $check_expect_time = $this->input->get_post('check_expect_time');       //期望验货时间

        //验证必填参数
        if (empty($id)) {
            $this->error_json('请求参数id不能为空');
        } elseif (empty($contact_person)) {
            $this->error_json('联系人不能为空');
        } elseif (empty($phone_number)) {
            $this->error_json('联系电话不能为空');
        } elseif (empty($remark)) {
            $this->error_json('申请备注不能为空');
        }elseif (empty($check_expect_time)) {
            $this->error_json('期望验货时间不能为空');
        }

        $params = array(
            'contact_person' => $contact_person,
            'phone_number' => $phone_number,
            'remark' => $remark,
            'check_expect_time' => $check_expect_time,
        );

        $data = $this->check_product_model->order_edit($id, $params);
        if ($data['flag']) {
            $this->success_json([], null, '操作成功');
        } else {
            $this->error_json($data['msg']);
        }
    }

    /**
     * 转合格申请
     * /supplier/check/check_product/qualify_for_apply
     */
    public function qualify_for_apply()
    {
        $id = $this->input->get_post('id');
        $img_url = $this->input->get_post('img_url');       //图片url(json格式)
        $reason = trim($this->input->get_post('reason'));   //驳回原因
        $img_url = json_decode($img_url,true);

        //验证必填参数
        if (empty($id)) {
            $this->error_json('请求参数id不能为空');
        } elseif (empty($img_url) OR !is_array($img_url)) {
            $this->error_json('证明凭证不能为空');
        } elseif (empty($reason)) {
            $this->error_json('驳回原因不能为空');
        }

        $params = array(
            'img_url' => $img_url,
            'reason' => $reason
        );
        $data = $this->check_product_model->qualify_for_apply($id, $params);
        if ($data['flag']) {
            $this->success_json([], null, '申请成功');
        } else {
            $this->error_json($data['msg']);
        }
    }

    /**
     * 作废验货单
     * /supplier/check/check_product/make_order_invalid
     */
    public function make_order_invalid()
    {
        $id = $this->input->get_post('id');//id（多个ID用逗号分隔）
        $id_arr = array_filter(explode(',', $id));

        $data = $this->check_product_model->make_order_invalid($id_arr);
        if ($data['flag']) {
            $this->success_json([], null, '操作成功');
        } else {
            $this->error_json($data['msg']);
        }
    }

    /**
     * 根据ID获取操作日志
     * /supplier/check/check_product/get_log?id=
     */
    public function get_log()
    {
        $id = $this->input->get_post('id');
        if (!is_numeric($id)) {
            $this->error_json('请求参数错误');
        }
        $data = $this->check_product_model->get_log($id);
        $this->success_json($data);
    }

    /**
     * 验货管理列表导出
     * /supplier/check/check_product/data_list_export
     */
    public function data_list_export()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);
        $this->load->helper(['export_csv','status_order','file_remote']);

        $ids = $this->input->get_post('ids');
        $params = array();
        if (!empty($ids)) {
            $params['ids'] = array_filter(explode(',', $ids));
        } else {
            $params = [
                'check_code' => $this->input->get_post('check_code'),                           // 申请编码
                'sku' => $this->input->get_post('sku'),                                         // sku
                'purchase_number' => $this->input->get_post('purchase_number'),                 // 采购单
                'demand_number' => $this->input->get_post('demand_number'),                     // 备货单号
                'apply_user_id' => $this->input->get_post('apply_user_id'),                     // 申请人
                'is_abnormal' => $this->input->get_post('is_abnormal'),                         // 是否异常
                'buyer_id' => $this->input->get_post('buyer_id'),                               // 采购员
                'confirm_user_id' => $this->input->get_post('confirm_user_id'),                 // 提交人
                'is_urgent' => $this->input->get_post('is_urgent'),                             // 是否加急
                'purchase_type_id' => $this->input->get_post('purchase_type_id'),               // 业务线
                'status' => $this->input->get_post('status'),                                   // 检验状态
                'product_line_id' => $this->input->get_post('product_line_id'),                 // 一级产品线
                'create_time_start' => $this->input->get_post('create_time_start'),             // 申请时间开始
                'create_time_end' => $this->input->get_post('create_time_end'),                 // 申请时间结束
                'check_expect_time_start' => $this->input->get_post('check_expect_time_start'), // 期望验货时间开始
                'check_expect_time_end' => $this->input->get_post('check_expect_time_end'),     // 期望验货时间结束
                'check_time_start' => $this->input->get_post('check_time_start'),               // 实际验货时间开始
                'check_time_end' => $this->input->get_post('check_time_end'),                   // 实际验货时间结束
                'is_special' => $this->input->get_post('is_special'),                           // 特批出货
                'check_times' => $this->input->get_post('check_times'),                         // 检验次数
                'user_group' => $this->input->get_post('user_group'),                         // 检验次数
            ];
        }
        if( isset($params['user_group']) && !empty($params['user_group'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['user_group']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }

        //获取记录条数
        $page = 1;
        $limit = 1;
        $offsets = ($page - 1) * $limit;
        $data = $this->check_product_model->get_list_data($params, $offsets, $limit, $page, true);
        $total = $data['total'];

        //文件名
        $file_name = 'Inspection_' . date('YmdHis');
        $columns = array(
            'check_code' => '申请编码', 'apply_user_name' => '申请人', 'create_time' => '申请时间', 'apply_remark' => '申请备注',
            'buyer_name' => '采购员', 'supplier_name' => '供应商名称', 'contact_person' => '联系人', 'phone_number' => '联系电话',
            'complete_address' => '联系地址', 'purchase_number' => 'PO号', 'check_times' => '检验次数', 'status_cn' => '验货状态',
            'order_type_cn' => '类型', 'confirm_user_name' => '提交人', 'confirm_time' => '提交时间', 'approval_user_name' => '审核人',
            'approval_time' => '审核时间', 'approval_remark' => '审核备注', 'check_expect_time' => '验货期望时间',
            'check_time' => '验货实际时间', 'is_special_cn' => '特批出货'
        );
        $down_path = $this->_export_csv($file_name, $total, $columns, $params);
        $this->success_json($down_path);
    }

    /**
     * 验货管理验货报告
     * /supplier/check/check_product/get_report
     */
    public function get_report(){
        $id = $this->input->get_post('id');
        if (!is_numeric($id)) {
            $this->error_json('请求参数错误');
        }
        $data = $this->check_product_model->get_report($id);
        $this->success_json($data);
    }

    /**
     * 导出csv文件
     * 导出数据小于5万条，生成单个csv文件
     * 导出数据大于5万条，生成多个文件，压缩成一个zip格式压缩包
     * @param $file_name |文件名（不包含文件后缀）
     * @param $total_count |导出数据总条数
     * @param $column_data |表头数据
     *           array(
     *                 'purchase_number'=>'采购单号'
     *           )
     * @param $params |查询条件
     * @return string
     */
    private function _export_csv($file_name, $total_count, $column_data, $params)
    {
        //服务器保存路径
        $tmp_path = date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d');
        $save_path = get_export_path($tmp_path);
        if (!file_exists($save_path)) @mkdir($save_path, 0777, true);

        $limit = 10000;                           //每次查询的条数
        $total_page = ceil($total_count / $limit);//总页数
        $limit_csv = 50000;                       //csv每页的数据量
        $count_csv = 0;                           //统计数据条数

        //表头中文编码转换
        foreach ($column_data as &$v) {
            $v = iconv("UTF-8", "GB2312//IGNORE", $v) . "\t";
        }
        //总条数超过$limit_csv时，分多个csv导出，并压缩打包
        if ($total_count > $limit_csv) {
            $file_id = 1;//文件序号
            $file_name_arr = array();
            //一页一页取出数据处理
            for ($page = 1; $page <= $total_page; $page++) {
                $offsets = ($page - 1) * $limit;
                //获取导出数据
                $result = $this->check_product_model->get_list_data($params, $offsets, $limit, $page, true);
                $count_csv += $result['total'];
                $file_tmp = $save_path . $file_name . '_' . $file_id . '.csv';//文件全路径及文件名
                //生成临时文件，写入表头
                if (!is_file($file_tmp)) {
                    $fp = fopen($file_tmp, 'w');
                    chmod($file_tmp, 0777);
                    fputcsv($fp, $column_data);//将数据通过fputcsv写到文件句柄
                }
                //使用生成器迭代
                $content_data = yieldData($result['values']);
                //数据写入文件
                writeCsvContent($fp, array_keys($column_data), $content_data);
                //每当数据条数等于$limit_csv时，生成一个文件
                if (($count_csv % $limit != 0) OR ($count_csv % $limit_csv == 0)) {
                    $file_name_arr[] = $file_tmp;
                    ob_flush();//刷新一下输出buffer，防止由于数据过多造成问题
                    flush();
                    fclose($fp);   //每生成一个文件关闭
                    $file_id++;    //文件序号递增
                    $count_csv = 0;//统计csv文件条数归零
                    unset($fp);
                }
            }
            //进行多个文件压缩,并删除原文件
            $file_name = CreateZipFile($file_name_arr, $save_path . $file_name);
        } else {
            //导出单个csv文件
            $file_tmp = $save_path . $file_name . '.csv';//文件全路径及文件名

            $file_name = $file_name . '.csv';

            //生成文件，写入表头
            $fp = fopen($file_tmp, 'w');
            chmod($file_tmp, 0777);
            fputcsv($fp, $column_data);//将数据通过fputcsv写到文件句柄
            //一页一页取出数据处理
            for ($page = 1; $page <= $total_page; $page++) {
                $offsets = ($page - 1) * $limit;
                //获取导出数据
                $result = $this->check_product_model->get_list_data($params, $offsets, $limit, $page, true);
                //使用生成器迭代
                $content_data = yieldData($result['values']);
                //数据写入文件
                writeCsvContent($fp, array_keys($column_data), $content_data);
                ob_flush();//刷新一下输出buffer，防止由于数据过多造成问题
                flush();
            }
            fclose($fp);//每生成一个文件关闭
            unset($fp);
        }
        //前端下载地址
        return get_export_path_replace_host(get_export_path($tmp_path),CG_SYSTEM_WEB_FRONT_IP) . $file_name;
    }

}