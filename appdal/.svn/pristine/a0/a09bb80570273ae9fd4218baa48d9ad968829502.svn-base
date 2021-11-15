<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2020/1/15
 * Time: 17:52
 */
class PurchaseConfirmService
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
        $this->_ci->load->model('purchase/Purchase_return_goods_model', 'return_goods_model');

        return $this;
    }

    /**
     * 采购驳回
     * @author Manson
     * @return bool
     * @throws Exception
     */
    public function confirm_purchase_reject($params)
    {
        if (empty($params['ids']) || !isset($params['ids'])) {
            throw new Exception('参数为空');
        }
        if (empty($params['remark']) || !isset($params['remark'])) {
            throw new Exception('请输入备注');
        }

        $ids = array_unique(array_filter(explode(',', $params['ids'])));
        if (empty($ids) || count($ids) > 200) {
            throw new Exception('参数异常');
        }
        $update_data = [];
        $main_number_list = $batch_insert_log_data = [];

        //检查状态
        $map = $this->_ci->m_return_part->get_part_info(['id' => $ids]);

        foreach ($ids as $id) {
            $return_status  = $map[$id]['return_status']??'';//处理状态
            if (!in_array($return_status, [RETURN_STATUS_WAITING_RETURN_NUMBER, RETURN_STATUS_WAREHOUSE_REJECT])) {
                $msg = '申请ID'.( $map[$id]['part_number']??'').',只有仓库驳回/待生成退货单才可点击';
                throw new Exception($msg);
            } else {
                $update_data[] = [
                    'id'             => $id,
                    'return_status'  => RETURN_STATUS_PURCHASE_REJECT,//RETURN_PROCESSING_STATUS_REJECT
                    'update_user'    => getActiveUserName(),
                    'update_time'    => date('Y-m-d H:i:s'),
                    'audit_time'     => date('Y-m-d H:i:s'),
                    'audit_user'     => getActiveUserName(),
                ];
                $main_number_list[] = $map[$id]['main_number'];
            }
        }

        if(!empty($map)){
            foreach ($map as $val){
                $batch_insert_log_data[] = [
                    'operate_user' => getActiveUserName(),
                    'operate_time' => date('Y-m-d H:i:s'),
                    'operate_content' => '采购驳回',
                    'remark' =>  $params['remark']??'',
                    'part_number' => $val['part_number'],
                    'log_time' => date('Y-m-d H:i:s'),
                ];
            }
        }

        if (empty($update_data)){
            throw new Exception('没有可更新的数据');
        }

        $db = $this->_ci->m_return_main->getDatabase();
        $db->trans_start();
        $db->update_batch($this->_ci->m_return_part->table_name(), $update_data, 'id');//更新退货状态

        //是否全部驳回 处理状态
        $result = $this->_ci->m_return_part->get_part_info(['main_number' => $main_number_list]);

        foreach ($result as $key => $item) {
            if ($item['return_status'] != RETURN_STATUS_PURCHASE_REJECT) {
                $un_reject_map[$item['main_number']][$item['id']] = 1;//未驳回的数据
            }
        }


        //处理状态更新
        //申请id下的子申请id是否全部驳回
        $un_reject_map = [];
        $main_number_list = array_keys($main_number_list);
        $new_info = $db->select('part.*, main.main_number, main.return_qty, main.wms_return_qty, main.pur_return_qty')
            ->from('pur_return_after_storage_part part')
            ->join('pur_return_after_storage_main main','main.main_number = part.main_number','left')
            ->where_in('part.main_number',$main_number_list)
            ->get()->result_array();
        foreach ($new_info as $key => $item){
            if ($item['return_status'] != RETURN_STATUS_PURCHASE_REJECT) {
                $un_reject_map[$item['main_number']] = 1;//未驳回的数据
            }
            $map[$item['id']] = $item;
        }

        foreach ($ids as $id) {
            $wms_return_qty = $map[$id]['wms_return_qty']??0;//仓库确认数量;
            $return_qty     = $map[$id]['return_qty']??0;//申请数量;
            $main_number = $map[$id]['main_number'];

            if (isset($update_main_data[$main_number])){
                continue;
            }
            //变更处理状态
            if (!isset($un_reject_map[$main_number])){//全部驳回
                $data['processing_status'] = RETURN_PROCESSING_STATUS_REJECT;
                $data['end_time'] = date('Y-m-d H:i:s');
            }elseif($wms_return_qty>0 && $wms_return_qty < $return_qty){
                $data['processing_status'] = RETURN_PROCESSING_STATUS_PART;
                $data['end_time'] = date('Y-m-d H:i:s');
            }elseif($wms_return_qty>0 && $wms_return_qty == $return_qty){
                $data['processing_status'] = RETURN_PROCESSING_STATUS_END;
                $data['end_time'] = date('Y-m-d H:i:s');
            }else{
                $data['processing_status'] = RETURN_PROCESSING_STATUS_ING;
            }

            $data['update_time'] = date('Y-m-d H:i:s');
            $data['update_user'] = getActiveUserName();
            $data['main_number'] = $main_number;
            $update_main_data[$main_number] = $data;
        }

        $db->update_batch('return_after_storage_main',$update_main_data,'main_number');//更新主申请id的处理状态

        //记录日志
        if (!empty($batch_insert_log_data)){
            $db->insert_batch($this->_ci->m_return_process_log->table_name(),$batch_insert_log_data);
        }

        $db->trans_complete();

        if ($db->trans_status() == false) {
            throw new Exception('数据库更新失败');
        } else {
            return true;
        }

    }

    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }

    /**
     * 采购经理审核 1通过 2驳回
     * @author Manson
     */
    public function confirm_purchasing_manager_audit($params)
    {
        if (isset($params['id']) && !empty($params['id'])){//兼容前端 单个点击驳回是传id 批量传ids
            $params['ids'] = $params['id'];
        }
        if (empty($params['ids']) || !isset($params['ids']))throw new Exception('参数为空');

        if (empty($params['audit_type']) || !isset($params['audit_type']) )throw new Exception('audit_type参数缺失');

        if ($params['audit_type'] == 1){//审核通过 待生成退货单
            $audit_type = RETURN_STATUS_WAITING_RETURN_NUMBER;
            $operate_content = '采购经理审核通过';
        }elseif ($params['audit_type'] == 2){//审核驳回 采购经理驳回
            if (empty($params['remark']) || !isset($params['remark']))throw new Exception('请输入备注');

            $audit_type = RETURN_STATUS_PURCHASE_MANGER;
            $operate_content = '采购经理审核驳回';
        }else{
            throw new Exception('audit_type参数异常');
        }

        $ids = array_unique(array_filter(explode(',', $params['ids'])));
        if (empty($ids) || count($ids) > 200)throw new Exception('id参数异常');


        $update_data = $batch_insert_log_data = [];

        //检查状态
        $map = $this->_ci->m_return_part->get_part_info(['id' => $ids]);
        if (empty($map))throw new Exception('未查询到勾选的数据');

        foreach ($ids as $id) {
            $return_status  = $map[$id]['return_status']??'';//退货状态

            if ($return_status != RETURN_STATUS_WAITING_AUDIT) {//不等于待采购经理审核  RETURN_STATUS_WAITING_AUDIT = 1
                throw new Exception($map[$id]['part_number'].'不是待采购经理审核状态');
            }

            $update_data[] = [
                'id'             => $id,
                'return_status'  => $audit_type,
                'update_user'    => getActiveUserName(),
                'update_time'    => date('Y-m-d H:i:s'),
                'audit_time'     => date('Y-m-d H:i:s'),
                'audit_user'     => getActiveUserName(),
                'audit_remark'   => $params['remark']??'',
                'wms_match_return_qty' => 0,//成功配库的数量
            ];

            //审核通过 审核失败的日志
            $batch_insert_log_data[] = [
                'operate_user' => getActiveUserName(),
                'operate_time' => date('Y-m-d H:i:s'),
                'operate_content' => $operate_content,
                'remark' =>  $params['remark']??'',
                'part_number' => $map[$id]['part_number'],
                'log_time' => date('Y-m-d H:i:s'),
            ];
            $main_number_list[$map[$id]['main_number']] = 1;
        }
        if (empty($update_data))throw new Exception('没有可更新的数据');

        //更新
        $db = $this->_ci->m_return_main->getDatabase();
        $db->trans_start();
        try{
            //调java接口 推送至仓库系统
            // 36164 入库后退货的数据取消配库的环节，直接推送数据到新仓库系统
            if (false && $params['audit_type'] == 1){//审核通过
//            if ($params['audit_type'] == 1){//审核通过
                $update_data  = [];//初始化更新数据

                // 如果 旧wms配库失败，则调用云仓配库
                // 直接到云仓配库，然后推送新wms
                $new_push_data = $this->_ci->m_return_part->get_detail_push_to_new_wms($ids);
                $res_cw = $this->new_wms_allot($new_push_data);
                $pk_success = [];
                $pk_error = [];
                if($res_cw['code'] == 1 && is_array($res_cw['msg'])){
                    $cw_data = $res_cw['msg'];
                    $push_to_new_wms = [];
                    foreach ($cw_data as $val){
                        // 配库失败
                        if(!isset($val['status']) || $val['status'] != 1 || !isset($val['data_list']['itemList']) || count($val['data_list']['itemList']) == 0){
                            $il_a = isset($val['data_list']['itemList']) && count($val['data_list']['itemList']) > 0? array_column($val['data_list']['itemList'], 'idempotent'): [];
                            if(count($il_a) > 0){
                                foreach ($il_a as $il_val){
                                    $il_val = explode("_", $il_val);
                                    $il_val = $il_val[0];
                                    $pk_error[] = $il_val;
                                }
                            }
                            continue;
                        }

                        // 配库成功
                        $sess_data = $val['data_list'];
                        foreach ($sess_data['itemList'] as $sval){
                            $idempotent = explode("_", $sval['idempotent']);
                            $idempotent = $idempotent[0];
                            if(isset($sval['number']) && $sval['number'] > 0){
                                $pk_success[] = $idempotent;
                                // 仓库系统调拨成功->待生成退货单状态
                                $return_status = RETURN_STATUS_WAITING_RETURN_NUMBER;//待生成退货单
                                $wms_new_qty = $sval['number'];
                                $remark = sprintf('new_wms成功配库数量:%s',$wms_new_qty);
                                $operate_content = '配库成功';

                                $update_data[] = [
                                    'id'             => $idempotent,
                                    'return_status'  => $return_status,
                                    'update_user'    => getActiveUserName(),
                                    'update_time'    => date('Y-m-d H:i:s'),
                                    'audit_time'     => date('Y-m-d H:i:s'),
                                    'audit_user'     => getActiveUserName(),
                                    'audit_remark'   => $params['remark']??'',//审核的备注
                                    'wms_match_return_qty' => $wms_new_qty,//成功配库的数量
                                ];

                                //配库日志
                                $batch_insert_log_data[] = [
                                    'operate_user' => getActiveUserName(),
                                    'operate_time' => date('Y-m-d H:i:s'),
                                    'operate_content' => $operate_content,
                                    'remark' =>  $remark??'',//仓库返回的信息备注
                                    'part_number' => $map[$idempotent]['part_number'],
                                    'log_time' => date('Y-m-d H:i:s'),
                                ];
                                $push_to_new_wms[] = $idempotent;
                                // 配库成功后 推送新wms
                                $this->_ci->return_goods_model->return_sku_push_new_wms([$idempotent=>$wms_new_qty]);
                            }else{
                                $pk_error[] = $idempotent;
                            }
                        }
                    }
                }

                // 配库失败数据
                foreach ($pk_error as $id){
                    $update_data[] = [
                        'id'             => $id,
                        'return_status'  => RETURN_STATUS_WAREHOUSE_REJECT, //驳回
                        'update_user'    => getActiveUserName(),
                        'update_time'    => date('Y-m-d H:i:s'),
                        'audit_time'     => date('Y-m-d H:i:s'),
                        'audit_user'     => getActiveUserName(),
                        'audit_remark'   => '配库失败，云仓匹配不到相应的库存',//审核的备注
                        'wms_match_return_qty' => '',
                    ];

                    //配库日志
                    $batch_insert_log_data[] = [
                        'operate_user' => getActiveUserName(),
                        'operate_time' => date('Y-m-d H:i:s'),
                        'operate_content' => '配库失败',
                        'remark' => '配库失败，云仓匹配不到相应的库存',//仓库返回的信息备注
                        'part_number' => $map[$id]['part_number'],
                        'log_time' => date('Y-m-d H:i:s'),
                    ];
                }

            }

            if (!empty($update_data)){
                $db->update_batch($this->_ci->m_return_part->table_name(), $update_data, 'id');//更新退货状态
            }

            if (!empty($batch_insert_log_data)){//审核的日志
                $db->insert_batch($this->_ci->m_return_process_log->table_name(),$batch_insert_log_data);
            }

            //处理状态更新
            //申请id下的子申请id是否全部驳回
            $un_reject_map = [];
            $main_number_list = array_keys($main_number_list);
            $new_info = $db->select('part.*, main.main_number, main.return_qty, main.wms_return_qty, main.pur_return_qty')
                ->from('pur_return_after_storage_part part')
                ->join('pur_return_after_storage_main main','main.main_number = part.main_number','left')
                ->where_in('part.main_number',$main_number_list)
                ->get()->result_array();
            foreach ($new_info as $key => $item){
                if ($item['return_status'] != RETURN_STATUS_PURCHASE_REJECT) {
                    $un_reject_map[$item['main_number']] = 1;//未驳回的数据
                }
                $map[$item['id']] = $item;
            }

            foreach ($ids as $id) {
                $wms_return_qty = $map[$id]['wms_return_qty']??0;//仓库确认数量;
                $return_qty     = $map[$id]['return_qty']??0;//申请数量;
                $main_number = $map[$id]['main_number'];

                if (isset($update_main_data[$main_number])){
                    continue;
                }
                //变更处理状态
                if (!isset($un_reject_map[$main_number])){//全部驳回
                    $data['processing_status'] = RETURN_PROCESSING_STATUS_REJECT;
                }elseif($wms_return_qty>0 && $wms_return_qty < $return_qty){
                    $data['processing_status'] = RETURN_PROCESSING_STATUS_PART;
                }elseif($wms_return_qty>0 && $wms_return_qty == $return_qty){
                    $data['processing_status'] = RETURN_PROCESSING_STATUS_END;
                    $data['end_time'] = date('Y-m-d H:i:s');
                }else{
                    $data['processing_status'] = RETURN_PROCESSING_STATUS_ING;
                }

                $data['update_time'] = date('Y-m-d H:i:s');
                $data['update_user'] = getActiveUserName();
                $data['main_number'] = $main_number;
                $update_main_data[$main_number] = $data;
            }

            $db->update_batch('return_after_storage_main',$update_main_data,'main_number');//更新主申请id的处理状态


            $db->trans_complete();

            if ($db->trans_status() == false) {
                throw new Exception('数据库更新失败');
            } else {
                // 审核成功后推送至新wms
                foreach ($ids as $id){
                    $this->_ci->return_goods_model->return_sku_push_new_wms([$id => 0]);
                }
                return true;
            }
        }catch (\Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 云仓配库
     */
    public function new_wms_allot($push_data)
    {
        $res = [
            "code"  => 0,
            "msg"   => ""
        ];
        if (empty($push_data)){
            $res['msg'] = '推送的数据为空';
            return $res;
        }

        try{
            $url = getConfigItemByName('api_config', 'wms_system', 'newWmsGetStock');
            $access_taken = getOASystemAccessToken();

            $url_api = $url."?access_token=".$access_taken;
            $push_data = json_encode($push_data);
            $results = getCurlData($url_api, $push_data, 'post', ['Content-Type: application/json']);
            operatorLogInsert(
                [
                    'id'      => "2021",
                    'type'    => 'new_wms_allot',
                    'content' => '云仓配库',
                    'detail'  => "request:{$push_data}......response:{$results}"
                ]
            );
            $results = json_decode($results, true);

            $res['msg'] = '调用配库接口失败';
            if (is_array($results) && count($results) > 0){//接口调用成功
                $res['code'] = 1;
                $res['msg'] = $results;
            }
        }catch (Exception $e){
            $res['msg'] = $e->getMessage();
        }
        return $res;
    }

    /**
     * 日志列表
     * @author Manson
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function get_log_list($params)
    {
        if (empty($params['part_number']) || !isset($params['part_number'])){
            throw new Exception('part_number参数为空');
        }
        $result = $this->_ci->m_return_process_log->get_log_by_part_number($params['part_number']);
        return $result;
    }


    /**
     * 仓库调拨
     * 调拨成功->
     * 调拨失败->
     * http://192.168.71.156/web/#/69?page_id=5694
     * @author Manson
     */
    public function java_wms_allot($push_data)
    {
        $res = [
            "code"  => 0,
            "msg"   => ""
        ];
        if (empty($push_data['push_data']) || empty($push_data['part_number_list'])){
            $res['msg'] = '推送的数据为空';
            return $res;
        }
        $params_api = $push_data['push_data'];
        $part_number_list = $push_data['part_number_list'];

        $url = getConfigItemByName('api_config', 'wms_system', 'push_return_detail_to_wms'); //获取推送url
        $access_taken = getOASystemAccessToken();
        if (empty($url)) {
            $res['msg'] = 'api不存在';
            return $res;
        }
        if (empty($access_taken)) {
            $res['msg'] = '获取access_token值失败';
            return $res;
        }

        $url_api = $url."?access_token=".$access_taken;
        $results = getCurlData($url_api, $params_api, 'post', ['Content-Type: application/json']);
        $push_data = json_encode($push_data);
        operatorLogInsert(
            [
                'id'      => "2021",
                'type'    => 'old_wms_allot',
                'content' => 'wms配库',
                'detail'  => "request:{$push_data}......response:{$results}"
            ]
        );
        $results = json_decode($results, true);

        $res['msg'] = '调用推送接口失败';
        if (isset($results['code']) && $results['code'] == 1 && isset($results['data'])){//接口调用成功
            $res['code'] = 1;
            $res['msg'] = $results['data'];
            return $res;
        }
        return $res;
    }

    /**
     * 仓库驳回
     */
    public function wms_reject($params)
    {
//        $test[] = [
//            'part_number' => 'TH202001200034-1',
//            'audit_user' => '驳回人',
//            'audit_time' => '2020-03-07 12:00:00',
//            'remark' => '备注',
//        ];
//        $params['data'] = json_encode($test);
//pr($params);exit;
        //批量数据

//        $params['data'] = '[{"part_number":["TH202005050001-0001"],"audit_user":"admin","audit_time":"2020-05-06 13:57:51","remark":"预估运费大于退货sku总价","data":{"sku_total_price":"1.14","estimate_freight":"35"}}]';


        if (!isset($params['data']) || empty($params['data'])){
            throw new Exception('data参数为空');
        }

        $data = json_decode($params['data'],true);
        if (empty($data)){
            throw new Exception('data数据为空');
        }

        $total = count($data);
        if ($total > 200){
            throw new Exception('数据异常,超出200条');
        }

        //初始化
        $update_data = $insert_log_data = [];

        //分为驳回成功和驳回失败
        $part_number_list = array_column($data,'part_number');
        if(is_array($part_number_list)){
            $part_number_list = $part_number_list[0];
        }
        //检查是否满足驳回条件
        $map = $this->_ci->m_return_part->get_return_status(['part_number'=>$part_number_list]);
        foreach ($data as $key => $item){
            if (!isset($item['part_number']) || empty($item['part_number'])){
                throw new Exception('申请单号为空');
            }
            if (!isset($item['audit_user']) || empty($item['audit_user'])){
                throw new Exception('审核人为空');
            }
            if (!isset($item['audit_time']) || empty($item['audit_time'])){
                throw new Exception('审核时间为空');
            }
            if (!isset($item['remark']) || empty($item['remark'])){
                throw new Exception('驳回备注为空');
            }
            foreach ($item['part_number'] as $val){
                if (empty($map[$val])){
                    throw new Exception(sprintf('未查找到申请子id,%s',$item['part_number']));
                }
                $return_status = $map[$val]['return_status']??'';
                $id = $map[$val]['id']??'';
                if ($return_status != RETURN_STATUS_WAITING_RETURN_NUMBER){
                    throw new Exception(sprintf('不是待生成退货单状态,%s',$val));
                }
                if (empty($id)){
                    throw new Exception(sprintf('未查找到申请子id,%s',$val));
                }
                $update_data[] = [
                    'id' => $id,
                    'return_status' =>  RETURN_STATUS_WAREHOUSE_REJECT,
                ];
                $insert_log_data[] = [
                    'operate_user' => $item['audit_user'],
                    'operate_time' => $item['audit_time'],
                    'operate_content' => '仓库驳回',
                    'remark' => $item['remark'],
                    'part_number' => $val,
                    'log_time' => date('Y-m-d H:i:s')
                ];
            }
        }

        //更新状态
        if (!empty($update_data) && !empty($insert_log_data)){
            $db = $this->_ci->m_return_part->getDatabase();
            //事务开始
            $db->trans_start();

            if (!$db->update_batch('pur_return_after_storage_part',$update_data,'id')){
                throw new Exception('更新数据失败');
            }


            if (!$db->insert_batch('pur_return_process_log',$insert_log_data)){
                throw new Exception('写入数据失败');
            }

            $db->trans_complete();
            if ($db->trans_status() === false) {
                throw new Exception('数据库操作失败,驳回失败');
            }else{
                return true;
            }
        }else{
            throw new Exception('驳回失败,数据异常');
        }
    }

}
