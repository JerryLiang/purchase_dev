<?php
/**
 * 产品修改审整合列表
 * User: Jolon
 * Date: 2019/03/16 21:00
 */

class Product_mod_integrate extends MY_Controller{
    private $_self_status = [3,4];// 只查看 审核通过或审核不通过的数据

	public function __construct(){
        parent::__construct();
        $this->load->model('Product_mod_audit_model','product_mod');
        $this->load->helper('status_product');
    }

    /**
    * 获取供应商整合列表
    * /product/product_mod_integrate/get_integrate_list
    * @author Jolon
    */
    public function get_integrate_list(){
        $params = [
            'create_user_id'    => $this->input->get_post('create_user_id'),
            'sku'               => $this->input->get_post('sku'),
            'audit_status'      => $this->input->get_post('audit_status'),
            'integrate_status'  => $this->input->get_post('integrate_status'),
            'apply_department'  => $this->input->get_post('apply_department'),
            'type'              => $this->input->get_post('type'),
            'old_supplier_code' => $this->input->get_post('old_supplier_code'),
            'new_supplier_code' => $this->input->get_post('new_supplier_code'),
            'create_time_start' => $this->input->get_post('create_time_start'),
            'create_time_end'   => $this->input->get_post('create_time_end'),
            'audit_time_start'  => $this->input->get_post('audit_time_start'),
            'audit_time_end'    => $this->input->get_post('audit_time_end'),
        ];

        if(empty($params['audit_status']) or !in_array($params['audit_status'],$this->_self_status))
            $params['audit_status'] = $this->_self_status;

        $page_data = $this->format_page_data();

        $drop_down_box = $this->product_mod->get_down_list();
        $drop_down_box['integrate_status_list'] = getProductIntegrateStatus();
        unset($drop_down_box['audit_status_list'][''],$drop_down_box['audit_status_list'][1],$drop_down_box['audit_status_list'][2],$drop_down_box['audit_status_list'][5]);

        $field = 'a.id,a.audit_status,a.audit_user_name,a.audit_time,a.create_user_name,a.create_time,a.integrate_status,
                a.sku,a.product_name,a.old_supplier_price,a.new_supplier_price,a.old_supplier_code,a.old_supplier_name,a.new_supplier_code,a.new_supplier_name,
                a.is_sample,a.integrate_note,a.integrate_check_result,a.product_line_name,
                b.product_img_url,b.product_thumb_url,b.create_user_name as develop_user_name,b.create_time as develop_time,b.product_status';
        $result       = $this->product_mod->get_product_list($params, $page_data['limit'], $page_data['offset'], $field, $page_data['page']);
        $format_data  = $this->product_mod->formart_product_list($result['data_list']);
        $data_list    = [
            'key'   => ['ID', '审核状态', '申请情况', '整合状态', 'SKU创建时间', '产品线', '产品图片', 'SKU',
                '品名', '原单价', '现单价', '原供应商', '现供应商', '是否拿样', '质检测试样品结果', '整合备注'],
            'value' => $format_data,
            'drop_down_box' => $drop_down_box
        ];
        $this->success_json($data_list, $result['paging_data']);

    }

    /**
    * 整合成功 或 整合失败
    * /product/product_mod_integrate/integrate_result
    * @author Jolon
    */
    public function integrate_result(){
        $ids         = $this->input->get_post('ids');
        $status      = $this->input->get_post('status');
        $fail_reason = $this->input->get_post('fail_reason');

        if(empty($ids) or !is_array($ids) or empty($status) or !in_array($status,[1,2])){// 1.整合成功，2.整合失败
            $this->error_json('IDS或STATUS 参数错误');
        }

        $integrate_list = $this->product_mod->get_info_by_ids($ids);
        if($integrate_list){
            $this->product_mod->purchase_db->trans_begin();
            try{
                foreach($integrate_list as $integrate){
                    $id = $integrate['id'];
                    if($integrate['integrate_status'] != SUPPLIER_INTEGRATE_STATUS_TO_CONFIRM){
                        $this->error_json('记录ID['.$id.']只有【待确认】状态下才能操作');
                    }

                    // 更新整合结果
                    $update = [];
                    if($status == 1){
                        $update['integrate_status'] = SUPPLIER_INTEGRATE_STATUS_SUCCESS;
                    }else{
                        $update['integrate_status']      = SUPPLIER_INTEGRATE_STATUS_FAILED;
                        $update['integrate_fail_reason'] = $fail_reason;
                    }
                    $update['integrate_user_name'] = getActiveUserName();
                    $update['integrate_time']      = date('Y-m-d H:i:s');

                    $result = $this->product_mod->update($update,['id' => $integrate['id']]);
                    if($result){
                        operatorLogInsert(
                            ['id'      => $integrate['id'],
                             'type'    => $this->product_mod->tableName(),
                             'content' => '供应商整合结果',
                             'detail'  => $status == 1 ? '整合成功' : '整合失败'
                            ]);
                    }else{
                        throw new Exception('更新供应商整合结果遇到了错误！');
                    }
                }

                $this->product_mod->purchase_db->trans_commit();
                $this->success_json();
            }catch(Exception $e){
                $this->error_json($e->getMessage());
            }

        }else{
            $this->error_json('未到找对应的记录');
        }
    }

    /**
     * 添加整合备注
     * /product/product_mod_integrate/integrate_note_add
     * @author Jolon
     */
    public function integrate_note_add(){
        $ids            = $this->input->get_post('ids');
        $integrate_note = $this->input->get_post('integrate_note');

        if(empty($ids) or !is_array($ids) or empty($integrate_note) ){
            $this->error_json('IDS或INTEGRATE_NOTE 参数错误');
        }

        $integrate_list = $this->product_mod->get_info_by_ids($ids);
        if($integrate_list){
            $this->product_mod->purchase_db->trans_begin();
            try{
                foreach($integrate_list as $integrate){
                    $id             = $integrate['id'];
                    $old_note       = $integrate['integrate_note'];
                    $integrate_note = empty($old_note) ? $integrate_note : $old_note.';'.$old_note;
                    if($integrate['integrate_status'] != SUPPLIER_INTEGRATE_STATUS_SUCCESS AND $integrate['integrate_status'] != SUPPLIER_INTEGRATE_STATUS_FAILED){
                        $this->error_json('记录ID['.$id.']只有【整合成功或整合失败】状态下才能操作');
                    }

                    $update['integrate_note'] = $integrate_note;

                    $result = $this->product_mod->update($update,['id' => $id]);
                    if($result){
                        operatorLogInsert(
                            ['id'      => $integrate['id'],
                             'type'    => $this->product_mod->tableName(),
                             'content' => '供应商整合添加整合备注',
                             'detail'  => $integrate_note
                            ]);
                    }else{
                        throw new Exception('添加整合备注遇到了错误！');
                    }
                }

                $this->product_mod->purchase_db->trans_commit();
                $this->success_json();
            }catch(Exception $e){
                $this->error_json($e->getMessage());
            }

        }else{
            $this->error_json('未到找对应的记录');
        }
    }

    /**
     * 取消供应商整合
     * /product/product_mod_integrate/integrate_cancel
     * @author Jolon
     */
    public function integrate_cancel(){
        $ids         = $this->input->get_post('ids');
        $cancel_note = $this->input->get_post('cancel_note');

        if(empty($ids) or !is_array($ids) or empty($cancel_note) ){
            $this->error_json('IDS或CANCEL_NOTE 参数错误');
        }

        $integrate_list = $this->product_mod->get_info_by_ids($ids);
        if($integrate_list){
            $this->product_mod->purchase_db->trans_begin();
            try{
                foreach($integrate_list as $integrate){
                    $id             = $integrate['id'];
                    if($integrate['integrate_status'] != SUPPLIER_INTEGRATE_STATUS_SUCCESS AND $integrate['integrate_status'] != SUPPLIER_INTEGRATE_STATUS_FAILED){
                        $this->error_json('记录ID['.$id.']只有【整合成功或整合失败】状态下才能操作');
                    }

                    $update['integrate_status'] = SUPPLIER_INTEGRATE_STATUS_TO_CONFIRM;

                    $result = $this->product_mod->update($update,['id' => $id]);
                    if($result){
                        operatorLogInsert(
                            ['id'      => $integrate['id'],
                             'type'    => $this->product_mod->tableName(),
                             'content' => '供应商整合取消整合',
                             'detail'  => $cancel_note
                            ]);
                    }else{
                        throw new Exception('供应商取消整合遇到了错误！');
                    }
                }

                $this->product_mod->purchase_db->trans_commit();
                $this->success_json();
            }catch(Exception $e){
                $this->error_json($e->getMessage());
            }

        }else{
            $this->error_json('未到找对应的记录');
        }
    }

    /**
     * 供应商整合列表导出
     * /product/product_mod_integrate/integrate_export
     * @author Jolon
     */
    public function integrate_export(){
        $ids = $this->input->get_post('ids');
        if(!empty($ids)){
            $params['id']   = $ids;
        }else{
            $params = [
                'create_user_id'    => $this->input->get_post('create_user_id'),
                'sku'               => $this->input->get_post('sku'),
                'audit_status'      => $this->input->get_post('audit_status'),
                'integrate_status'  => $this->input->get_post('integrate_status'),
                'apply_department'  => $this->input->get_post('apply_department'),
                'type'              => $this->input->get_post('type'),
                'old_supplier_code' => $this->input->get_post('old_supplier_code'),
                'new_supplier_code' => $this->input->get_post('new_supplier_code'),
                'create_time_start' => $this->input->get_post('create_time_start'),
                'create_time_end'   => $this->input->get_post('create_time_end'),
                'audit_time_start'  => $this->input->get_post('audit_time_start'),
                'audit_time_end'    => $this->input->get_post('audit_time_end'),
            ];
        }
        $key = ['ID', '审核状态', '申请情况', '整合状态', 'SKU创建时间', '产品线', '产品图片', 'SKU',
            '品名', '原单价', '现单价', '原供应商', '现供应商', '是否拿样', '质检测试样品结果', '整合备注'];
        $field = 'a.id,a.audit_status,a.audit_user_name,a.audit_time,a.create_user_name,a.create_time,a.integrate_status,
                a.sku,a.product_name,a.old_supplier_price,a.new_supplier_price,a.old_supplier_code,a.old_supplier_name,a.new_supplier_code,a.new_supplier_name,
                a.is_sample,a.integrate_note,a.integrate_check_result,a.product_line_name,
                b.product_img_url,b.product_thumb_url,b.create_user_name as develop_user_name,b.create_time as develop_time,b.product_status';
        $result      = $this->product_mod->get_product_list($params, '', '', $field, '', true);
        $format_data = $this->product_mod->formart_product_list($result['data_list']);
        $result_tmp['key']  = $key;
        $result_tmp['value'] = $format_data;

        $this->success_json($result_tmp,$result['paging_data']);
    }


}