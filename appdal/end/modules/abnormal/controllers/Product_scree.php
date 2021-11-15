<?php
/**
 * sku屏蔽申请列表
 * User: Jolon
 * Date: 2019/01/16 15:00
 */

class Product_scree extends MY_Controller{

	public function __construct(){
        parent::__construct();

        $this->load->helper('status_product');
        $this->load->model('product_scree_model');
        $this->load->model('product_model','',false,'product');
        $this->load->model('supplier_model','',false,'supplier');
        $this->load->model('product_line_model','product_line',false,'product');
        $this->load->helper('common');
    }



    /**
     * 获取状态下拉框列表
     * @author Jolon
     * @param string $status_type  状态类型
     * @param string $get_all      获取所有
     * @return array|bool|mixed|null
     */
    public function status_list($status_type = null,$get_all = null){
        if($get_all){
            $status_list = ['status','apply_reason'];
        }else{
            $status_list = [$status_type];
        }

        $data_list_all = [];
        foreach($status_list as $status_type){
            switch(strtolower($status_type)){
                case 'status':
                    $data_list = getProductScreeStatus();
                    break;

                case 'apply_reason':
                    $data_list = getScreeApplyReason();
                    break;

                default :
                    $data_list = null;
            }
            if($get_all){// 返回所有
                $data_list_all[$status_type] = $data_list;
            }else{// 只返回查询的
                $data_list_all = $data_list;
            }
        }

        return $data_list_all;
    }

    /**
     * 屏蔽列表 相关状态 下拉列表
     * @author Jolon
     */
    public function get_status_list(){
        $status_type  = $this->input->get_post('type');
        $get_all      = $this->input->get_post('get_all');

        $data_list = $this->status_list($status_type,$get_all);
        if($data_list){
            $this->success_json($data_list);
        }else{
            $this->error_json('未知的状态类型');
        }
    }

    /**
     * 获取 指定的一个 SKU屏蔽 信息
     * @author Jolon
     */
    public function get_scree_one(){
        $scree_id = $this->input->get_post('scree_id');

        // 参数错误
        if(empty($scree_id)) $this->error_json('参数【scree_id】错误');

        $scree_info = $this->product_scree_model->get_scree_one($scree_id);

        if(!empty($scree_info)){
            $this->success_json($scree_info);
        }else{
            $this->error_json('未找到对应的SKU屏蔽信息');
        }
    }

    /**
     * 查询 屏蔽列表
     * @author Jolon
     */
    public function get_scree_list(){
        $params = [
            'sku'              => $this->input->get_post('sku'),// SKU
            'old_supplier_code'=> $this->input->get_post('supplier_code'),// 供应商
            'developer_id'     => $this->input->get_post('developer_id'),// 开发员
            'apply_user_id'    => $this->input->get_post('apply_user_id'),// 申请人
            'status'           => $this->input->get_post('status'),// 审核状态
            'apply_time_start' => $this->input->get_post('apply_time_start'),// 申请时间-开始
            'apply_time_end'   => $this->input->get_post('apply_time_end'),// 申请时间-结束
            'product_line_id'  => $this->input->get_post('product_line_id'), // 产品线
            'oper_time_start'  => $this->input->get_post('oper_time_start'),
            'oper_time_end'    => $this->input->get_post('oper_time_end'),
            'estimate_time_start'    => $this->input->get_post('estimate_time_start'),
            'estimate_time_end'    => $this->input->get_post('estimate_time_end'),
            'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
            'estima_status'    => $this->input->get_post('estima_status'),
            'nowdate'          => $this->input->get_post('nowdate'),
            '90_sales_start'         => $this->input->get_post('ninety_sales_start'),
            '90_sales_end'      => $this->input->get_post('ninety_sales_end'),
            '30_sales_start'         => $this->input->get_post('thirty_sales_start'),
            '30_sales_end'      => $this->input->get_post('thirty_sales_end'),
            'apply_reason'      => $this->input->get_post('apply_reason'),
            '100_days_start'    => $this->input->get_post('hundred_days_start'),
            '100_days_end'      => $this->input->get_post('hundred_days_end'),
            'scree_source'      => $this->input->get_post('scree_source'),
            'scree_num_start'      => $this->input->get_post('scree_num_start'),
            'scree_num_end'      => $this->input->get_post('scree_num_end'),
        ];
//        if($params['sku'] and strpos($params['sku'],' ') !== false){
//            $sku_arr = array_filter(explode(' ',$params['sku']));
//            $params['sku'] = $sku_arr;
//        }

        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }

        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page <= 0 ) $page  = 1;
        $limit         = query_limit_range($limit);
        $offset        = (intval($page) - 1) * $limit;

        $drop_down_box                    = $this->status_list(null, true);

        $this->load->model('user/purchase_user_model');
        $developer_list                   = $this->purchase_user_model->get_user_developer_list();
        $user_list                        = $this->purchase_user_model->get_user_all_list();
        $developer_list                   = array_column($developer_list, 'name', 'id');
        $user_list                        = !empty($user_list)?array_column($user_list, 'name', 'id'):[];
        $drop_down_box['developer_list']  = $developer_list;
        $drop_down_box['apply_user_list'] = $user_list;
        $drop_down_box['scree_source_list'] = ['1' => '取消未到货','2' => 'sku申请屏蔽'];

        $product_line_list = $this->product_line->get_product_line_list(0);
        $drop_down_box['product_line_id'] =array_column($product_line_list, 'linelist_cn_name','product_line_id');

        $scree_info = $this->product_scree_model->get_scree_list($params,$offset,$limit,$page);
        $key_arr = ['审核状态','sku','产品线','申请信息','开发员','产品图片','产品名称','聊天凭证','库存量','30天销量','原供应商/单价','替换供应商/单价','确认人/时间','替换供应商驳回原因','预计供货时间','最小起订量','审核通过时间/审核备注','操作'];

        $status_list = $drop_down_box['status'];
        $data_list = $scree_info['data_list'];
        $role_name=get_user_role();//当前登录角色

        $data_role= getRolexiao();
        $data_list = ShieldingData($data_list,['new_supplier_name','old_supplier_name','new_supplier_code','old_supplier_code'],$role_name,NULL);
        if($data_list){
            $this->load->model('warehouse/stock_model');
            foreach($data_list as &$list){
                $chat_evidence = explode(",",$list['chat_evidence']);
                $list['status'] = isset($status_list[$list['status']])?$status_list[$list['status']]:'未知';
                $list['chat_evidence'] = $chat_evidence;
                $list['affirm_time'] = $list['purchase_time'];
                $list['affirm_user'] = $list['purchase_person'];
                $list['affirm_remark'] = $list['purchase_remark'];
                $list['product_img_url'] = isset($list['product_img_url']) ? erp_sku_img_sku($list['product_img_url']): '';
                $list['image_url'] = $list['product_img_url'];

            }
        }

        $this->success_json(['key' => $key_arr,'value' => $data_list,'drop_down_box' => $drop_down_box],$scree_info['paging_data']);
    }
    /**
      * 添加SKU 屏蔽信息(修改)
     **/
    public function set_scree_sku() {

        /**
           *接受客户端传入POST 信息
         **/
        $clientData =  $this->input->get_post('data');
        $clientData = json_decode( $clientData,True );
        if( !isset($clientData) || empty($clientData) ) {

            $this->error_json('请传入要屏蔽的SKU信息');
            die();
        }
        $verify = [];
        foreach($clientData as $values){

            if( !isset($values['apply']) || empty($values['apply'])){

                $this->error_json("sku:".$values['sku']."，请选择屏蔽原因");
            }
            if( !in_array($values['sku'],$verify)){
                $verify[] = $values['sku'];
            }else{

                $this->error_json("请勿重复申请SKU");
            }
        }

        // 获取SKU
        $skus = array_column( $clientData,'sku');
        $scree_skus =  $this->product_scree_model->get_scree_skus_data($skus);
        if( !empty($scree_skus) ) {
            $scree_skus = array_column($scree_skus,NULL,"sku");
            foreach($clientData as $key=>$data ) {
                if( isset($scree_skus[$data['sku']]) ) {
                    unset($clientData[$key]);
                }
            }
            $scree_skus = array_keys($scree_skus);
           if( empty($clientData) ) {

                $this->error_json("申请SKU:".implode(",",$scree_skus)."正在审核中");
            }

        }
        $errors = $success =[];

        foreach($clientData as $values) {
            $result = $this->product_scree_model->set_scree_create([$values], $values['apply'], $values['estimate'], $values['remark']);
            // 如果返回数组
            if (is_array($result)) {

                //$this->error_json("申请SKU，" . implode(",", $result) . "申请失败");
                foreach($result as $errorSkus){

                    $errors[] = $errorSkus;
                }
            }

            if (True === $result) {

                $success[] =$result;
            }
        }

        if(empty($errors)){

            $this->success_json();
        }else{
            $this->error_json("申请SKU，" . implode(",", $errors) . "申请失败");

        }


    }

    /**
     * 创建 SKU屏蔽 记录
     * @author Jolon
     */
    public function scree_create(){
        $skuStr       = $this->input->get_post('sku');// SKU
        $apply_remark = $this->input->get_post('apply_remark');// 申请原因
        $other_reason = $this->input->get_post('remark');// 申请备注
        $images       = $this->input->get_post('images');// SKU聊天凭证图片地址


        $search     = array('，'," ","　"," ","\n","\r","\t");
        $replace    = array(' '," "," "," "," "," "," ");
        $skuStr     = str_replace($search,$replace ,$skuStr);

        if(strpos($skuStr,' ') !== false){
            $skuArr = explode(' ',$skuStr);
            $skuArr = array_unique($skuArr);
            $skuArr = array_diff($skuArr,['']);
        }else{
            $skuArr = array($skuStr);
        }

        if( empty($skuArr) || empty($apply_remark) ){//or !in_array($apply_remark,getScreeApplyReason())
            $this->error_json('SKU 或 申请原因缺失');
        }

        $apply_remark = ($apply_remark != PRODUCT_SCREE_APPLY_REASON_OTHER)?getScreeApplyReason($apply_remark):$other_reason;

        $success_list = [];// 成功结果
        $error_list = [];// 失败结果

        foreach($skuArr as $sku){
            if(empty($sku)) continue;// 空的不保存
            $now_images = isset($images[$sku])?$images[$sku]:[];
            $now_images = array_filter($now_images);
            if(empty($now_images) or !is_array($now_images)){
                $error_list[] = $sku."：SKU必须上传相应的聊天凭证";
                continue;
            }
            if(count($now_images) > 5){
                $error_list[] = $sku."：SKU最多上传 5 张聊天凭证";
                continue;
            }

            $sku    = trim($sku);
            $addInfo = [
                'sku'                   => $sku,
                'apply_remark'          => $apply_remark,
                'chat_evidence'         => json_encode($now_images,256),
                'apply_content'         => $other_reason,
            ];

            $result = $this->product_scree_model->scree_create($addInfo);
            if(empty($result['code'])){
                $error_list[] = $sku.'：'.$result['msg'];
            }else{
                $success_list[] = $sku;
            }
        }
        $this->success_json(['success_list' => $success_list,'error_list' => $error_list]);
    }

    /**
     * 采购经理审核
     * @author Jolon
     */
    public function scree_audit(){
        $scree_ids       = $this->input->get_post('scree_ids');// scree_ids
        $check_status    = $this->input->get_post('check_status');// 审核结果(1.审核通过，2.驳回)
        $reject_remark   = $this->input->get_post('reject_remark');// 驳回原因

        if(empty($scree_ids) or !is_array($scree_ids) or empty($check_status)){
            $this->error_json('参数【scree_ids或check_status】错误');
        }
        if($check_status != 1 and empty($reject_remark)){
            $this->error_json('驳回必须填写驳回备注');
        }

        // $this->product_scree_model->purchase_db->trans_begin();
        foreach($scree_ids as $scree_id){
            $result = $this->product_scree_model->scree_audit($scree_id,$check_status,$reject_remark);

            if(empty($result['code'])){
                $this->product_scree_model->purchase_db->trans_rollback();
                $this->error_json($scree_id.'：'.$result['msg']);
            }
        }
        // $this->product_scree_model->purchase_db->trans_commit();

        $this->success_json([],null,'审核操作成功');
    }

    /**
     * 查询 操作日志
     * @author Jolon
     */
    public function get_logs(){
        $scree_id       = $this->input->get_post('scree_id');// scree_id
        $sku            = $this->input->get_post('sku');

//        if(empty($scree_id) || is_array($scree_id)){
//            $this->error_json('参数【scree_id】错误');
//        }

        $result = $this->product_scree_model->get_scree_log($scree_id,$sku);
        if( NULL == $sku ) {
            foreach ($result as $key => &$value) {
                $value['image'] = !empty($value['image']) ? explode(",", $value['image']) : [];
            }
        }
        $this->success_json($result,null,'日志获取成功');

    }


    /**
     * 采购确认 - 替换供应商
     * @author Jolon
     */
    public function affirm_supplier(){
        $scree_id      = $this->input->get_post('scree_id');// scree_id
        $check_status  = $this->input->get_post('check_status');// 审核结果(1.审核通过，其他.审核驳回)
        $affirm_remark = $this->input->get_post('affirm_remark');

        if(empty($scree_id) or is_array($scree_id)){
            $this->error_json('参数【scree_id】错误');
        }
        if($check_status != 1 and empty($affirm_remark)){
            $this->error_json('驳回必须填写驳回备注');
        }

        $result = $this->product_scree_model->affirm_supplier($scree_id,$check_status,$affirm_remark);
        if($result['code']){
            $this->success_json([],null,'操作成功');
        }else{
            $this->error_json($result['msg']);
        }
    }

    /**
     * 删除 申请记录
     * @author Jolon
     */
    public function batch_delete(){
        $scree_ids       = $this->input->get_post('scree_ids');// scree_ids

        if(empty($scree_ids) or !is_array($scree_ids)){
            $this->error_json('参数【scree_ids】错误');
        }

        $this->product_scree_model->purchase_db->trans_begin();

        foreach($scree_ids as $scree_id){
            $result = $this->product_scree_model->screen_delete($scree_id);

            if(empty($result['code'])){
                $this->product_scree_model->purchase_db->trans_rollback();
                $this->error_json($scree_id.'：'.$result['msg']);
            }
        }

        $this->product_scree_model->purchase_db->trans_commit();

        $this->success_json([],null,'删除操作成功');
    }


    /**
     * 导出CSV
     *      1、勾选-按照 ID 导出
     *      2、未勾选-按照 查询条件导出
     * @author Jolon
     */
    public function scree_export_csv(){
        set_time_limit(0);
        ini_set('memory_limit','2500M');
        $this->load->helper('export_csv');

        $scree_ids       = $this->input->get_post('scree_ids');// scree_ids
        if($scree_ids){
            if(!is_array($scree_ids)){
                $this->error_json('参数【scree_ids】错误,必须是数组');
            }
            $scree_list = $this->product_scree_model->get_scree_one($scree_ids);
        }else{

            $params = [];

            if( !empty($_GET)) {

                foreach( $_GET as $key=>$value ) {
                    if( $key != 'uid') {
                        $params[$key] = $this->input->get($key);
                    }
                }


            }

            $params['90_sales_start']     = $this->input->get_post('ninety_sales_start');
             $params['90_sales_end']      = $this->input->get_post('ninety_sales_end');
             $params['30_sales_start']    = $this->input->get_post('thirty_sales_start');
            $params['30_sales_end']      = $this->input->get_post('thirty_sales_end');
            $params['apply_reason']      = $this->input->get_post('apply_reason');
            $params['100_days_start']    = $this->input->get_post('hundred_days_start');
            $params['100_days_end']     = $this->input->get_post('hundred_days_end');
            $params['scree_num_start']      = $this->input->get_post('scree_num_start');
            $params['scree_num_end']      = $this->input->get_post('scree_num_end');

            if( isset($params['group_ids']) && !empty($params['group_ids'])){

                $this->load->model('user/User_group_model', 'User_group_model');
                $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
                $groupdatas = [];
                if(!empty($groupids)){
                    $groupdatas = array_column($groupids,'value');
                }

                $params['groupdatas'] = $groupdatas;
            }
            $scree_list = $this->product_scree_model->get_scree_list($params);
            $scree_list = $scree_list['data_list'];
        }

        $columns = [
            '审核状态',
            'SKU',
            '一级产品线',
            '二级产品线',
            '三级产品线',
            '四级产品线',
            '申请人',
            '申请时间',
            '申请备注',
            '开发员',
            '产品图片',
            '产品名称',
            '聊天凭证',
            '库存量',
            '30天销量',
            '原供应商',
            '原单价',
            '替换供应商',
            '替换单价',
            '确认人',
            '确认时间',
            '替换供应商驳回原因',
            '预计供货时间',
            '最小起订量',
            '审核通过时间',

            '审核备注',
            '审核人',
            '所属小组',
            '30天销量',
            '90天销量',
            '近100天找货次数',
            'sku屏蔽次数'
        ];

        $scree_list_tmp = [];
        if($scree_list){
            $this->load->model('warehouse/stock_model');
            $scree_list = ShieldingData($scree_list,['old_supplier_code','old_supplier_name','new_supplier_name','new_supplier_code'],get_user_role(),NULL);
            foreach($scree_list as $v_value){
                $chat_evidence = $v_value['chat_evidence'];
                $chat_evidence = ($chat_evidence and is_json($chat_evidence))?json_decode($chat_evidence,true):[];

                $v_value_tmp                       = [];
                $v_value_tmp['status']             = getProductScreeStatus($v_value['status']);
                $v_value_tmp['sku']                = $v_value['sku'];
                if( isset($v_value['category_all'][0]) && !empty($v_value['category_all'][0]))
                {
                    $v_value_tmp['one_line_name'] =$v_value['category_all'][0]['product_line_name'];
                }else{
                    $v_value_tmp['one_line_name'] = NULL;
                }
                if( isset($v_value['category_all'][1]) && !empty($v_value['category_all'][1]))
                {
                    $v_value_tmp['two_line_name'] = $v_value['category_all'][1]['product_line_name'];
                }else{
                    $v_value_tmp['two_line_name'] = NULL;
                }
                if( isset($v_value['category_all'][2]) && !empty($v_value['category_all'][2]))
                {
                    $v_value_tmp['three_line_name'] = $v_value['category_all'][2]['product_line_name'];
                }else{
                    $v_value_tmp['three_line_name'] = NULL;
                }
                if( isset($v_value['category_all'][3]) && !empty($v_value['category_all'][3]))
                {
                    $v_value_tmp['four_line_name'] = $v_value['category_all'][3]['product_line_name'];
                }else{
                    $v_value_tmp['four_line_name'] = NULL;
                }
                $v_value_tmp['apply_user']         = $v_value['apply_user'];
                $v_value_tmp['apply_time']         = $v_value['apply_time'];

                if( $v_value['apply_remark'] == 4) {

                    $v_value['apply_remark'] = "缺货";
                }

                if( $v_value['apply_remark'] == 99) {

                    $v_value['apply_remark'] = "需要起订量";
                }


                if( $v_value['apply_remark'] == 2) {

                    $v_value['apply_remark'] = "停产";
                }

                $v_value_tmp['apply_remark']       = $v_value['apply_remark'];
                $v_value_tmp['developer_name']     = $v_value['developer_name'];
                $v_value_tmp['product_img_url']    = erp_sku_img_sku($v_value['product_img_url']);
                $v_value_tmp['product_name']       = $v_value['product_name'];
                $v_value_tmp['chat_evidence']      = implode(',',$chat_evidence);
                $stock_list = $this->stock_model->get_stock_total_stock($v_value['sku']);// 库存量
                $v_value_tmp['wms_stock']          = isset($stock_list['real_stock'])?$stock_list['real_stock']:null;
                $v_value_tmp['days_sales_30']      = $v_value['days_sales_30'];
                $v_value_tmp['old_supplier_name']  = $v_value['old_supplier_name'];
                $v_value_tmp['old_supplier_price'] = $v_value['old_supplier_price'];
                $v_value_tmp['new_supplier_name']  = $v_value['new_supplier_name'];
                $v_value_tmp['new_supplier_price'] = $v_value['new_supplier_price'];
                $v_value_tmp['erp_oper_user']        = $v_value['erp_oper_user'];
                $v_value_tmp['erp_oper_time']        = $v_value['erp_oper_time'];
                $v_value_tmp['affirm_remark']      = $v_value['affirm_remark'];
                $v_value_tmp['estimate_time']      = $v_value['estimate_time'];
                $v_value_tmp['start_number']       = $v_value['start_number'];
                $v_value_tmp['affirm_time']         = $v_value['affirm_time'];
                $v_value_tmp['audit_remark']      = $v_value['audit_remark'];
                $v_value_tmp['affirm_user']        = $v_value['affirm_user'];
                $v_value_tmp['groupName']        = $v_value['groupName'];
                $v_value_tmp['sales']            = $v_value['sales'];
                $v_value_tmp['90_sales']         = $v_value['thirty_sales'];
                $v_value_tmp['100_days_data']    = $v_value['hundred_days']['100_day_sales'];
                $v_value_tmp['total_scree_num']    = $v_value['total_scree_num'];
                $scree_list_tmp[] = $v_value_tmp;
            }
        }

        unset($scree_list);

        $data = [
            'key' => $columns,
            'value' => $scree_list_tmp,
        ];
        $this->success_json($data);
    }

    /**
     * function:修改SKU 屏蔽缺货SKU 的预计到货时间
     * @author:luxu
     **/

    public function update_estimate_time()
    {
        try{

            $param = $this->input->get_post('datas');
            if( empty($param))
            {
                throw new Exception("请传入相关参数");
            }
            $param = json_decode( $param,True);

            foreach($param  as $key=>$value) {
                $this->product_scree_model->update_estimate_time($value);
            }

            $this->success_json();
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }

    public function get_scree_estimatetime()
    {
        $scree_id       = $this->input->get_post('scree_id');// scree_id

        if(empty($scree_id) || is_array($scree_id)){
            $this->error_json('参数【scree_id】错误');
        }

        $result = $this->product_scree_model->get_scree_estimatetime($scree_id);
        if( !empty($result))
        {
            foreach( $result as $key=>&$value)
            {
                $value['image'] = explode(",",$value['image']);
                $value['type'] = "修改预计供货时间";
            }
        }
        $this->success_json($result,null,'日志获取成功');
    }

    /**
      * 需求号:27731 SKU屏蔽申请弹框优化
      *  Sku后面增加一列数据“近100天找货次数”，按照申请时间往前推100天，
      *  以SKU维度统计SKU申请屏蔽原因等于“停产找货中”并且“审核通过”的次数；
      * @author:luxu
      * @time:2020/12/7
     **/
    public function getPrevData(){

        try{
            $skus = $this->input->get_post('skus');
            if( empty($skus) || !is_array($skus)){
                //throw new Exception("请传入正确的SKU数据");
            }

            $endTime = date("Y-m-d H:i:s",time());
            $startTime = date("Y-m-d H:i:s",strtotime("-100 day"));
            $result = $this->product_scree_model->getPrevData($skus,$startTime,$endTime);
            $this->success_json($result);
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * sku 屏蔽代码补齐
     * @param : HTTP  JSON
     * @author:luxu
     **/
    public function scree_import_product(){

       try{

           $import_json = file_get_contents('php://input');
           $result_list = json_decode(stripslashes($import_json),True);
           $result_list = $result_list['import_arr'];
           if(!empty($result_list) && isset($result_list[1])){

               $skus = array_map(function($datas){

                   if($datas[0] != 'sku'){
                       return $datas[0];
                   }
               },$result_list);

               $skus = array_filter($skus);

               if(empty($skus)){

                   throw new Exception("请导入正常数据");
               }
               $no_verify_skus = [];
               if(count($skus)>1000){

                   $skusArray = array_chunk($skus,100);

                   foreach($skusArray as $skuDatas){

                       $scree_skus =  $this->product_scree_model->get_scree_skus_data($skuDatas);
                       if(!empty($scree_skus)){

                           $no_verify = array_column($scree_skus,'sku');
                           foreach($no_verify as $nosku){
                               $no_verify_skus[] = $nosku;
                           }

                       }
                   }
               }else {
                   $scree_skus = $this->product_scree_model->get_scree_skus_data($skus);

                   if (!empty($scree_skus)) {

                       $no_verify_skus = array_column($scree_skus, 'sku');
                   }
               }
               $error_list = $success_list =[];
               foreach( $result_list as $key=>&$value){

                   if($key == 0){
                       continue;
                   }
                   $sku    = trim($value[0]);
                   if(in_array($sku,$no_verify_skus)){

                       $value[6] = "审核中";
                       $error_list[] = $value;
                       continue;
                   }
                   // 申请原因验证.停产，缺货,停产找货中,需要起订量
                   if( !in_array($value[1],["停产","缺货","停产找货中"])){

                      // $error_list[] = "sku:".$sku.",申请原因填写错误.申请原因请在(停产，缺货,停产找货中,需要起订量)中选择";
                       $value[6] = "申请原因填写错误.申请原因请在(停产，缺货,停产找货中)中选择";
                       $error_list[] = $value;
                       continue;
                   }

                   if( in_array($value[1],["缺货","停产找货中"]) && empty($value[2])){
                       $value[6] = "请填写预计到货时间";
                       $error_list[] = $value;
                       continue;

                   }
                   $addInfo = [
                       'sku'                   => $sku,
                       'chat_evidence'         => '',
                       'apply_content'         => $value[4],
                   ];

                   if($value[1] == '停产'){

                       $applyremark = 2;
                   }else if($value[1] == '缺货'){
                       $applyremark = 4;
                   }else if($value[1] == '需要起订量'){
                       $applyremark = 99;
                   }else if($value[1] == '停产找货中'){
                       $applyremark = 10;
                   }

                   $addInfo['apply_remark'] = $applyremark;

                   if(in_array($addInfo['apply_remark'],[4])){

                       $now_time = date("Y-m-d");
                       if(strtotime($now_time)>strtotime($value[2])){

                           $value[6] = "sku:".$sku." 预计到货时间填写错误，请填写大于当前日期";
                           $error_list[] = $value;
                           continue;
                       }

                       $next_time = date("Y-m-d",strtotime("+45 day"));
                       if(strtotime($value[2]) > strtotime($next_time)){

                           $value[6] = "sku:".$sku." 预计到货时间填写错误，请填写大于:".$now_time.",小于:".$next_time;
                           $error_list[] = $value[6];
                           continue;
                       }
                   }

                   if(  $addInfo['apply_remark'] == 99 && empty($value[3])){

                       $value[6] = "sku:".$sku." 申请原因为需要起订量,最小起订量必填";
                       $error_list[] = $value;
                       continue;
                   }

                   if(empty($addInfo['apply_content'])){
                       $value[6] = "sku:".$sku." 请填写备注";
                       $error_list[] = $value;
                       continue;
                   }

                   if($addInfo['apply_remark'] != 99) {
                       $result = $this->product_scree_model->set_scree_create([$addInfo], $addInfo['apply_remark'], $value[2], $addInfo['apply_content'], True);
                   }else{
                       $result = $this->product_scree_model->set_scree_create([$addInfo], $addInfo['apply_remark'], $value[3], $addInfo['apply_content'], True);
                   }
                   if($result !== True){
                       $error_list[] = $result[0];
                   }else{
                       $success_list[] = $sku;
                   }

               }



               if(empty($error_list)) {
                   $this->success_json();
               }else{
                   $heads = ['sku','申请原因','预计供货时间','最小起订量','备注','错误原因'];


                   $file_name = 'scree'.time().'.csv';
                   $product_file = get_export_path('scree').$file_name;
                   if (file_exists($product_file)) {
                       unlink($product_file);
                   }
                   fopen($product_file,'w');
                   $fp = fopen($product_file, "a");
                   foreach($heads as $key => $item) {
                       $title[$key] = $item;
                   }

                   //将标题写到标准输出中
                   fputcsv($fp, $title);
                   foreach($error_list as $error_key=>$error_value)
                   {

                       foreach($error_value as $ekey => $evalue) {
                           $error_value[$ekey] =  $evalue;

                       }
                       fputcsv($fp, $error_value);

                   }

                   ob_flush();
                   flush();
                   $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
                   $error_sku = $result;
                   $down_file_url=get_export_path_replace_host(get_export_path('scree'),$down_host).$file_name;
                   $return_data = array(

                       'error_message' => "共有".count($error_list)."条数据错误，请确认是否下载",
                       'error_list' =>$down_file_url
                   );


                   $this->error_json($return_data);
               }
           }
       }catch ( Exception $exp ){

           $this->error_json($exp->getMessage());
       }
    }
}