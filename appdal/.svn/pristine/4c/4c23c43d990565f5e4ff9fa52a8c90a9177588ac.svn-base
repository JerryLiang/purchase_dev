<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2020/1/15
 * Time: 17:52
 */
class ApplyReturnGoodsService
{
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('return/Return_after_storage_main_model', 'm_return_main', false, 'purchase');
        $this->_ci->load->model('return/Return_after_storage_part_model', 'm_return_part', false, 'purchase');
        $this->_ci->load->model('return/Return_process_log_model', 'm_return_process_log', false, 'purchase');
        return $this;
    }


    /**
     * 导入
     * @author Manson   yefanli 2020-07-10
     * @param $params
     * @return array
     */
    public function import($params){
        $report      = [
            'total'              => 0,
            'processed'          => 0,
            'undisposed'         => 0,
            'error_line_message' => [],
        ];
        $insert_data = [];
        //先确认sku列的索引
        $sku_index     = $params['map']['sku'];
        $index_to_cols = array_flip($params['map']);
        $selected      = json_decode($params['selected'], true);
        unset($params['selected']);

        $report['total'] = $report['undisposed'] = count($selected);//初始化,未处理数量
        if (empty($selected)) {
            return $report;
        }

        $this->_ci->load->model('prefix_number_model'); // 数据表前缀
        $this->_ci->load->model('product_model', 'm_product', false, 'product');//产品表

        //批量查询sku相关信息
        $sku_list = array_column($selected, $sku_index, 'sku');
        $sku_list = array_unique(array_filter($sku_list));

        $sku_map = $this->_ci->m_return_main->get_import_sku_info($sku_list);

        //定义
        $batch_insert_return = $batch_insert_log = $error_mess = [];
        $warehouse_map       = array_flip(getReturnWarehouse());//仓库
        $season_map       = array_flip(getReturnSeason());//退货原因

        // yefanli 2020-07-10
        // 定义导入数据不符合会犯的错误提示
        $row_err = '';
        $db = $this->_ci->m_return_main->getDatabase();

        //校验,组织数据
        $row_x = 1;
        foreach ($selected as $item) {
            $temp                            = [];
            $error_mess[$item['line_index']] = '';
            $row_x ++;
//            pr($item);exit;
            $supplier = '';
            foreach ($item as $key => $value) {
                $row_item = "";
                if (!isset($index_to_cols[$key])) {//不规范数据
                    continue;
                }
                if ($index_to_cols[$key] == 'sku') {
                    if (empty($value) || !isset($sku_map[$value])) {
                        $error_mess[$item['line_index']] .= ' SKU为没有填写或不存在该SKU; ';
                        $row_item .= " SKU填写错误或不存在该SKU ，";
                    }
                }

                if ($index_to_cols[$key] == 'supplier_code') {
                    if(!empty($value)){
                        // 检测是否存在
                        $has_supplier = $db
                            ->from("supplier")->select('supplier_code,supplier_name')
                            ->where(["supplier_code" => $value])
                            ->get()->result_array();
                        if(!$has_supplier || count($has_supplier) == 0){
                            $error_mess[$item['line_index']] .= "该供应商不存在，请检查; ";
                            $value = '';
                        }else{
                            $supplier = $has_supplier[0]['supplier_name'];
                        }
                    }
                }

                if ($index_to_cols[$key] == 'return_unit_price') {
                    if($value < 0){
                        $error_mess[$item['line_index']] .= "退货单价小于0，请检查; ";
                    }else{
                        $value = 0;
                    }
                }

                if ($index_to_cols[$key] == 'return_qty') {
                    if (empty($value) || !positiveInteger($value, 3)) {
                        $error_mess[$item['line_index']] .= ' 申请退货数量没有填写或不是正整数; ';
                        $row_item .= " 申请退货数量填写有误 ，";
                    }
                }
                if ($index_to_cols[$key] == 'return_reason') {
                    if (empty($value) || !in_array($value, ['库内异常退货', '滞销'])) {
                        $error_mess[$item['line_index']] .= ' 申请原因没有填写,且只能填写滞销或库内异常退货; ';
                        $row_item .= " 申请原因填写有误，只能填写滞销或库内异常退货，";
                    }else{
                        $value = $season_map[$value]??'';
                    }
                }
                if ($index_to_cols[$key] == 'return_warehouse_code') {
                    if (empty($value) || !isset($warehouse_map[$value])) {
                        $error_mess[$item['line_index']] .= ' 退货仓库没有填写,且只能填写-小包仓_虎门、小包仓_塘厦、小包仓_慈溪;';
                        $row_item .= " 退货仓库填写有误 ，";
                    }else{
                        $value = $warehouse_map[$value]??'';
                    }
                }
                if ($index_to_cols[$key] == 'proposer') {
                    if (empty($value)) {
                        $error_mess[$item['line_index']] .= ' 申请人没有填写; ';
                        $row_item .= " 申请人填写有误";
                    }
                }
                if($row_item != ""){
                    $row_err .= "第 ".$row_x." 行".$row_item."\n";
                }
                $temp[$index_to_cols[$key]] = $value;
            }
//            pr($sku_map);exit;
            if (empty($error_mess[$item['line_index']])) {//正常的数据
                //生成一个新的单号
                $new_main_number = $this->_ci->prefix_number_model->get_prefix_new_number('TH'.date('Ymd'),1,4);
                if (!$new_main_number){
                    throw new \RuntimeException('新的申请ID,生成失败',500);
                }
                $temp['main_number'] = $new_main_number;
                $temp['supplier_name'] = $supplier;

                $temp['sample_packing_weight'] = $sku_map[$temp['sku']]['sample_packing_weight']??'';
                $temp['product_name'] = $sku_map[$temp['sku']]['product_name']??'';
                $temp['unit_price_without_tax'] = $sku_map[$temp['sku']]['unit_price_without_tax']??'';
                $temp['buyer_id'] = $sku_map[$temp['sku']]['buyer_id']??'';
                $temp['buyer_name'] = $sku_map[$temp['sku']]['buyer_name']??'';
                $temp['create_time'] = date('Y-m-d H:i:s');
                $temp['create_user'] = getActiveUserName();

                $insert_data[] = $temp;//要写入主表的数据
            }
        }
        $report['error_line_message'] = array_filter($error_mess);

        if($row_err != ""){
            throw new \RuntimeException(sprintf("导入失败，错误信息：".$row_err), 500);
        }

        if (empty($insert_data)){
            throw new \RuntimeException(sprintf('没有可写入数据'), 500);
        }
        //加载
        $db = $this->_ci->m_return_main->getDatabase();

        //事务
        try {
            $db->trans_start();
            //批量插入
            $insert_rows = $db->insert_batch($this->_ci->m_return_main->getTable(), $insert_data);
            $db->trans_complete();

            if ($db->trans_status() === false) {
                throw new \RuntimeException(sprintf('批量导入申请退货,插入数据失败'), 500);
            }

            $report['processed']  = count($insert_rows);
            $report['undisposed'] = $report['total'] - $report['processed'];

            return $report;
        } catch (\Throwable $e) {
            log_message('ERROR', sprintf('批量导入申请退货出现异常: %s', $e->getMessage()));
        }
    }


    /**
     * 采购确认详情
     * type=1 申请明细列表的
     * type=2 采购明细列表的
     * @author Manson
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function apply_purchase_confirm_detail($params = []){
        if (empty($params['ids']) || !isset($params['ids'])){
            throw new Exception('参数为空');
        }
        if (empty($params['type']) || !isset($params['type'])){
            throw new Exception('类型参数为空');
        }
        $ids = array_unique(array_filter(explode(',',$params['ids'])));
        if (empty($ids) || count($ids)>200){
            throw new Exception('参数异常');
        }
        $type = $params['type'];
        if (!in_array($type,[1,2])){
            throw new Exception('未存在该类型');
        }

        if ($type == 1){
            $result = $this->_ci->m_return_main->get_purchase_confirm_detail($ids);
        }elseif($type == 2){
            $result = $this->_ci->m_return_part->get_purchase_confirm_detail($ids);
        }
//pr($result);exit;
        if (empty($result)){
            return [];
        }
        $sku_list = array_column($result,'sku');
        $this->_ci->load->model('Warehouse_model', 'm_warehouse', false, 'warehouse');
        $purchase_on_way_map = $this->_ci->m_warehouse->get_total_purchase_on_way($sku_list);

        foreach ($result as $item){
            $detail[] = [
                'id' => $item['id'],
                'main_number' => $item['main_number']??'',//申请主ID
                'part_number' => $item['part_number']??'',//申请子ID
                'sku' => $item['sku']??'',//sku
                'sample_packing_weight' => $item['sample_packing_weight']??'',//样品包装重量
                'product_name' => $item['product_name']??'',//产品名称
                'supplier_name' => $item['supplier_name']??'',//供应商名称
                'can_match_inventory' => 0,//可配库库存
                'purchase_on_way' => $purchase_on_way_map[$item['sku']]??0,//采购在途
                'unit_price_without_tax' => $item['unit_price_without_tax']??'',//未税单价
                'return_qty' => $item['return_qty']??'',//退货数量(申请)
                'pur_return_qty' => $item['pur_return_qty']??'',//退货数量(采购确认)
                'return_cost' => $item['return_cost']??'',//退货产品成本
                'return_unit_price' => $item['return_unit_price']??'',//退货单价
                'return_amount' => $item['return_amount']??'',//退货金额
                'freight' => $item['freight']??'',//运费
                'freight_payment_type' => $item['freight_payment_type']??'',//运费类型$item['return_reason']
                'return_reason' => $item['return_reason'],//退货原因
                'restricted_supplier' => empty($item['restricted_supplier'])?'':json_decode($item['restricted_supplier'],true),//需限制的供应商
                'contact_person' => $item['contact_person']??'',//退货联系人
                'contact_number' => $item['contact_number']??'',//退货联系方式
                'contact_province' => $item['contact_province']??'',//收货地址(省)
                'contact_addr' => $item['contact_addr']??'',//收货地址(详细地址)
                'remark' => $item['remark']??'',//采购备注
                'return_warehouse_code' => !is_null($item['return_warehouse_code']) ? getWarehouse($item['return_warehouse_code']) : '',//getWarehouse($item['return_warehouse_code'])??'',//申请退货仓库
                'is_new' => 2,//不是新增的 写死的标记
            ];
        }
        return $detail;
    }


    /**
     * 采购驳回
     * @author Manson
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function apply_purchase_reject($params)
    {
        if (empty($params['ids']) || !isset($params['ids'])){
            throw new Exception('参数为空');
        }
        if (empty($params['remark']) || !isset($params['remark'])){
            throw new Exception('请输入备注');
        }

        $ids = array_unique(array_filter(explode(',',$params['ids'])));
        if (empty($ids) || count($ids)>200){
            throw new Exception('参数异常');
        }
        $update_data = [];
        //检查状态

        $map = $this->_ci->m_return_main->get_main_info(['id'=>$ids]);
        foreach ($ids as $id){
            $processing_status = $map[$id]['processing_status']??'';//处理状态
            if ($processing_status != RETURN_PROCESSING_STATUS_NO){
                throw new Exception(sprintf('%s,不是未处理状态',$map[$id]['main_number']??''));
            }else{
                $update_data[] = [
                    'id' => $id,
                    'processing_status' => RETURN_PROCESSING_STATUS_REJECT,
                    'end_time' => date('Y-m-d H:i:s'),
                    'reject_user' => getActiveUserName(),
                    'reject_time' => date('Y-m-d H:i:s'),
                    'reject_remark' =>$params['remark']

                ];
            }
        }

        $db = $this->_ci->m_return_main->getDatabase();
        $db->trans_start();
        $db->update_batch($this->_ci->m_return_main->table_name(),$update_data,'id');
        $db->trans_complete();

        if ($db->trans_status()==false){
            throw new Exception('数据库更新失败');
        }else{
            return true;
        }
    }


    /**
     * 采购确认
     * @author Manson
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function apply_purchase_confirm($params)
    {
        //接收参数 处理参数
        if (!isset($params['data']) || empty($params['data']) || !isset($params['type']) || empty($params['type'])){
            throw new Exception('参数为空');
        }
//        pr($params['data']);exit;
        $params['data'] = json_decode($params['data'],true);
//pr($params);exit;
        if ($params['type'] == 1 ) {//
            $number_type = 'main_number';
        }elseif ($params['type'] == 2){
            $number_type = 'part_number';
        }else{
            throw new Exception('type参数异常');
        }

        $skip_confirm = $params['skip_confirm']??'';

        //变量初始化
        $this->_ci->load->model('prefix_number_model'); // 数据表前缀
        $insert_part_data = $update_main_data = $update_part_data = $batch_insert_log_data = [];
        $supplier_fright_type = $data = $uni_id_supplier = [];
        $confirm_message = '';
        $validate_arr = [ //不能为空的参数
            'is_new' => 'is_new',
            'main_number' => '申请主ID',
            'part_number' => '申请子ID',
            'supplier_code' => '供应商编码',
            'supplier_name' => '供应商名称',
            'pur_return_qty' => '采购确认数量',
            'return_cost' => '退货产品成本',
            'return_unit_price' => '退货单价',
            'return_amount' => '退货金额',
            'freight_payment_type' => '运费支付方式',
            'contact_person' => '退货联系人',
            'contact_number' => '退货联系方式',
            'contact_province' => '收货地址-省',
            'contact_addr' => '收货地址-详细地址',
            'remark' => '备注'
        ];

        if ($params['type'] == 1 ){
            $number_type = 'main_number';
        }elseif($params['type'] == 2){
            $number_type = 'part_number';
        }
//pr($params['data']);exit;

        //校验和组织数据
        foreach ($params['data'] as $item){
            $result = validate_empty($item,$validate_arr);
            if ($result['status'] == 0){
                throw new Exception($result['errorMess']);
            }

            if(!positiveInteger($item['pur_return_qty'],3)){
                throw new Exception(sprintf('%s，采购确认数量,必须填写正整数且不为0，请重新填写', $item['main_number']));
            }
            if(!positiveInteger($item['return_unit_price'],2)){
                throw new Exception(sprintf('%s，退货单价,必须填写大于0，请重新填写', $item['main_number']));
            }

            if (empty($item[$number_type])){
                throw new Exception('参数异常');
            }
            $data[$item[$number_type]][] = $item;
        }



        if ($params['type'] == 1 ){//
            $number_type = 'main_number';
            $main_number_list = array_column($params['data'],'main_number');
            if (empty($main_number_list)){
                throw new Exception('数据异常main_number为空');
            }
            $map = $this->_ci->m_return_main->get_main_info(['main_number'=>$main_number_list]);


        }elseif($params['type'] == 2){
            $number_type = 'part_number';
            $part_number_list = array_column($params['data'],'part_number');
            if (empty($part_number_list)){
                throw new Exception('数据异常part_number为空');
            }
            $map = $this->_ci->m_return_part->get_part_info(['part_number'=>$part_number_list]);

        }else{
            throw new Exception('type参数异常');
        }

//pr($map);exit;
//        pr($data);exit;
        foreach ($data as $key => $item){
            $total_pur_return_qty = 0;
            foreach ($item as $k => $val){
                if ($params['type'] == 1){
                    $val['is_new'] = 1;//申请页面的采购确认全部为新增的数据
                    $processing_status = $map[$val['main_number']]['processing_status']??'';//处理状态
                    if ($processing_status != RETURN_PROCESSING_STATUS_NO){
                        throw new Exception(sprintf('%s,不是未处理状态',$val[$number_type]??''));
                    }
                }elseif ($params['type'] == 2){
                    $return_status = $map[$val['part_number']]['return_status']??'';//退货状态
                    if ($val['is_new'] != 1){//除+号新增的 需判断状态
                        if ( !in_array($return_status,[RETURN_STATUS_PURCHASE_MANGER,RETURN_STATUS_WAREHOUSE_REJECT])){
                            throw new Exception(sprintf('%s,不是采购经理驳回,仓库驳回状态',$val[$number_type]??''));
                        }
                    }
                }


                if ($params['type'] == 1){
                    $uni_id_supplier_key = sprintf('%s%s',$val['main_number'],$val['supplier_code']);//供应商和申请编码唯一

                    if (!isset($uni_id_supplier[$uni_id_supplier_key])){
                        $uni_id_supplier[$uni_id_supplier_key] = 1;

                    }else{
                        throw new Exception(sprintf('%s，同一申请编码，供应商存在一致，请重新填写', $val[$number_type]));
                    }
                }

                $total_pur_return_qty += $val['pur_return_qty'];

                if (isset($supplier_fright_type[$val['supplier_code']])){
                    if ($supplier_fright_type[$val['supplier_code']] != $val['freight_payment_type']) {//一个供应商有不同的运费方式时给予提醒
                        $confirm_message .= sprintf('温馨提示：供应商%s，运费既有乙方承担，又有甲方承担，是否确定提交?', $val['supplier_code']);
                    }
                }else{
                    $supplier_fright_type[$val['supplier_code']] = $val['freight_payment_type'];
                }
//                pr($supplier_fright_type);exit;


                $restricted_supplier_code = empty($val['restricted_supplier'])?[]:array_keys($val['restricted_supplier']);
                if (in_array($val['supplier_code'],$restricted_supplier_code)){
                    throw new Exception(sprintf('%s，违禁的供应商不能跟该申请ID的已填的供应商一样，请重新填写', $val[$number_type]));
                }

                if ($val['is_new'] == 1){//新增
                    //查询出新的part_number
                    //生成一个新的子申请ID
                    $new_part_number = $this->_ci->prefix_number_model->get_prefix_new_number($val['main_number'].'-',1,4);
                    if (!$new_part_number){
                        throw new \RuntimeException('新的申请ID,生成失败',500);
                    }

                    $insert_part_data[] = [
                        'main_number' => $val['main_number']??'',
                        'part_number' => $new_part_number,
                        'supplier_code' => $val['supplier_code']??'',
                        'supplier_name' => $val['supplier_name']??'',
                        'pur_return_qty' => $val['pur_return_qty']??'',
                        'return_cost' => $val['return_cost']??'',//退货产品成本 = 未税单价*采购确认数量
                        'return_unit_price' => $val['return_unit_price']??'',
                        'return_amount' => $val['return_amount']??'',//退货金额 = 退货单价*采购确认的退货数量
                        'freight_payment_type' => $val['freight_payment_type']??'',
                        'restricted_supplier' => json_encode($val['restricted_supplier'])??'',
                        'contact_person' => $val['contact_person']??'',
                        'contact_number' => $val['contact_number']??'',
                        'contact_province' => $val['contact_province']??'',
                        'contact_addr' => $val['contact_addr']??'',
                        'remark' => $val['remark']??'',
                        'return_status' => RETURN_STATUS_WAITING_AUDIT,//待采购经理审核
                        'create_time' => date('Y-m-d H:i:s'),
                        'create_user' => getActiveUserName(),
                        'update_time' => date('Y-m-d H:i:s'),
                        'update_user' => getActiveUserName(),
                    ];

                    //记录日志 导入的日志
                    $batch_insert_log_data[] = [
                        'operate_user' => $map[$val['main_number']]['create_user']??'',
                        'operate_time' => $map[$val['main_number']]['create_time']??'',
                        'operate_content' => '导入',
                        'remark' => getReturnSeason($map[$val['main_number']]['return_reason']??''),
                        'part_number' => $new_part_number,
                        'log_time' => date('Y-m-d H:i:s'),
                    ];

                    //采购确认的日志
                    $batch_log_data[] = [
                        'operate_user' => getActiveUserName(),
                        'operate_time' => date('Y-m-d H:i:s'),
                        'operate_content' => '采购确认',
                        'remark' =>  $val['remark']??'',
                        'part_number' => $val['part_number']??'',
                        'log_time' => date('Y-m-d H:i:s'),
                    ];

                }elseif ($val['is_new'] == 2){//更新
                    $id = $map[$val['part_number']]['id'];
                    if (empty($id)){
                        throw new Exception('申请子ID异常,%s',$val['part_number']);
                    }

                    $update_part_data[] = [
                        'id' => $id,
                        'supplier_code' => $val['supplier_code']??'',
                        'supplier_name' => $val['supplier_name']??'',
                        'pur_return_qty' => $val['pur_return_qty']??'',
                        'return_cost' => $val['return_cost']??'',//退货产品成本 = 未税单价*采购确认数量
                        'return_unit_price' => $val['return_unit_price']??'',
                        'return_amount' => $val['return_amount']??'',//退货金额 = 退货单价*采购确认的退货数量
                        'freight_payment_type' => $val['freight_payment_type']??'',
                        'restricted_supplier' => json_encode($val['restricted_supplier'])??'',
                        'contact_person' => $val['contact_person']??'',
                        'contact_number' => $val['contact_number']??'',
                        'contact_province' => $val['contact_province']??'',
                        'contact_addr' => $val['contact_addr']??'',
                        'remark' => $val['remark']??'',
                        'return_status' => RETURN_STATUS_WAITING_AUDIT,//待采购经理审核
                        'update_time' => date('Y-m-d H:i:s'),
                        'update_user' => getActiveUserName(),
                    ];


                    //采购确认的日志
                    $batch_insert_log_data[] = [
                        'operate_user' => getActiveUserName(),
                        'operate_time' => date('Y-m-d H:i:s'),
                        'operate_content' => '采购确认',
                        'remark' =>  $val['remark']??'',
                        'part_number' => $val['part_number']??'',
                        'log_time' => date('Y-m-d H:i:s'),
                    ];
                }else{
                    throw new Exception('is_new参数异常');
                }

            }
            if ($total_pur_return_qty > $map[$key]['return_qty']){
                throw new Exception(sprintf('%s, 采购确认数量=10，超过申请确认数量，请重新编辑',$key));
            }
            $update_main_data[] = [
                'id' => $map[$key]['id'],
                'processing_status' => RETURN_PROCESSING_STATUS_ING,//处理中
            ];
        }
//pr($supplier_fright_type);
//pr($confirm_message);
//pr($insert_part_data);
//pr($update_part_data);
//pr($update_main_data);exit;


        if ((!empty($confirm_message) && $skip_confirm == 1) || empty($confirm_message)){//跳过确认
            $db = $this->_ci->m_return_main->getDatabase();
            $db->trans_start();


            if (!empty($update_main_data)){
                $db->update_batch($this->_ci->m_return_main->table_name(),$update_main_data,'id');
            }
            if (!empty($insert_part_data)){
                $db->insert_batch($this->_ci->m_return_part->table_name(),$insert_part_data);
            }

            if (!empty($update_part_data)){
                $db->update_batch($this->_ci->m_return_part->table_name(),$update_part_data,'id');
            }

            if (!empty($batch_insert_log_data)){
                $db->insert_batch($this->_ci->m_return_process_log->table_name(),$batch_insert_log_data);
            }

            $db->trans_complete();

            if ($db->trans_status()==false){
                throw new Exception('数据库操作失败');
            }else{
                return ['errorMess'=>'执行成功'];
            }
        }elseif (!empty($confirm_message)){

            return ['is_confirm'=> 1, 'errorMess' => $confirm_message];
        }else{
            throw new Exception('数据异常');
        }
    }

    /**
     * 根据供应商查询对应的联系信息
     *
     * @author Manson
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function get_supplier_contact($params)
    {

        if (empty($params['supplier_code']) || !isset($params['supplier_code'])){
            throw new Exception('参数为空');
        }
        $this->_ci->load->model('Supplier_contact_model', 'm_supplier_contact', false, 'supplier');

        return $this->_ci->m_supplier_contact->get_supplier_contact_by_supplier($params['supplier_code']);

    }
}
