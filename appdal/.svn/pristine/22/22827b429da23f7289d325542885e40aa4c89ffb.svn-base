<?php
/**
 * 银行卡管理
 * User: Jolon
 * Date: 2019/01/17 15:00
 */

class Bank_card extends MY_Controller{

	public function __construct(){
        parent::__construct();

        $this->load->helper('status_finance');
        $this->load->model('bank_card_model');
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
            $status_list = ['account_sign','payment_types','status'];
        }else{
            $status_list = [$status_type];
        }

        $data_list_all = [];
        foreach($status_list as $status_type){
            switch(strtolower($status_type)){
                case 'account_sign':
                    $data_list        = bankCardAccountSign();
                    break;

                case 'payment_types':
                    $data_list        = bankCardPaymentTypes();
                    break;

                case 'status':
                    $data_list        = bankCardStatus();
                    break;

                default :
                    $data_list        = null;
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
     * 相关状态 下拉列表
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
     * 获取 银行卡账号简称列表
     * @author Jolon
     */
    public function get_account_short_list(){
        $account_short_list = $this->bank_card_model->get_account_short_list();
        $this->success_json($account_short_list);
    }

    /**
     * 获取 指定的一个 银行卡信息
     * @author Jolon
     */
    public function get_card_one(){
        $card_id = $this->input->get_post('id');

        // 参数错误
        if(empty($card_id)) $this->error_json('参数【id】缺失');

        $card_info = $this->bank_card_model->get_card($card_id);

        if(!empty($card_info)){
            $this->success_json(['value' => $card_info]);
        }else{
            $this->error_json('未找到对应的银行卡');
        }
    }

    /**
     * 查询 银行卡列表
     * @author Jolon
     */
    public function get_card_list(){
        $params = [
            'account_number' => $this->input->get_post('account_number'),// 账号
            'branch'         => $this->input->get_post('branch'),// 支行
            'account_holder' => $this->input->get_post('account_holder'),// 开户人
            'account_short'  => $this->input->get_post('account_short'),// 账号简称
            'payment_types'  => $this->input->get_post('payment_types'),// 支付类型(1.银行卡,2.支付宝)
            'status'         => $this->input->get_post('status'),// 审核状态
            'account_sign'   => $this->input->get_post('account_sign'),// 账号标志(1.对公帐号,2.对私帐号)
        ];

        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page <= 0 ) $page  = 1;
        $limit         = query_limit_range($limit);
        $offset        = ($page - 1) * $limit;

        $drop_down_box  = $this->status_list(null,true);
        $bank_card_info = $this->bank_card_model->get_card_list($params,$offset,$limit,$page);
        $key_arr        = ['ID','开户银行','开户账号信息','账号标志','账号简称','支付类型','K3账号','状态','时间','备注','动作'];

        $data_list = $bank_card_info['data_list'];

        if($data_list){
            $data_list_tmp = [];
            foreach($data_list as $list){
                $list_tmp                    = [];
                $list_tmp['id']              = $list['id'];
                $list_tmp['head_office']     = $list['head_office'];
                $list_tmp['branch']          = $list['branch'];
                $list_tmp['account_holder']  = $list['account_holder'];
                $list_tmp['account_number']  = $list['account_number'];
                $list_tmp['account_sign']    = bankCardAccountSign($list['account_sign']);
                $list_tmp['account_short']   = $list['account_short'];
                $list_tmp['payment_types']   = bankCardPaymentTypes($list['payment_types']);
                $list_tmp['k3_bank_account'] = $list['k3_bank_account'];
                $list_tmp['status']          = bankCardStatus($list['status']);
                $list_tmp['create_time']     = $list['create_time'];
                $list_tmp['update_time']     = $list['update_time'];
                $list_tmp['remarks']         = $list['remarks'];

                $data_list_tmp[]             = $list_tmp;
            }
            $data_list = $data_list_tmp;
        }

        $this->success_json(['key' => $key_arr,'value' => $data_list,'drop_down_box' => $drop_down_box],$bank_card_info['paging_data']);
    }

    /**
     * 创建 SKU屏蔽 记录
     * @author Jolon
     */
    public function bank_card_create(){
        $post_data       = $this->input->post();

        $id = isset($post_data['id'])?$post_data['id']:0;
        $bank_card = [
            'id'               => $id,
            'head_office'      => isset($post_data['head_office'])?$post_data['head_office']:'',
            'branch'           => isset($post_data['branch'])?$post_data['branch']:'',
            'account_holder'   => isset($post_data['account_holder'])?$post_data['account_holder']:'',
            'account_number'   => isset($post_data['account_number'])?$post_data['account_number']:'',
            'account_short'    => isset($post_data['account_short'])?$post_data['account_short']:'',
            'payment_types'    => isset($post_data['payment_types'])?$post_data['payment_types']:0,
            'account_sign'     => isset($post_data['account_sign'])?$post_data['account_sign']:0,
            'k3_bank_account'  => isset($post_data['k3_bank_account'])?$post_data['k3_bank_account']:'',
            'remarks'          => isset($post_data['remarks'])?$post_data['remarks']:'',
            'status'           => empty($post_data['status'])?1:$post_data['status'],// 默认 1.可用
            'apply_business'   => 1,
        ];

        $error_msg = '';
        if(empty($bank_card['payment_types'])
            or empty($bank_card['branch'])
            or empty($bank_card['account_number'])
            or empty($bank_card['account_sign'])
            or empty($bank_card['account_short'])) $error_msg = '请填写必填信息';

        if($error_msg){
            $this->error_json($error_msg);
        }

        $result = $this->bank_card_model->card_create($bank_card);
        if($result['code']){
            $this->success_json($result['data_list'],null,$id?'更新成功':'创建成功');
        }else{
            $this->error_json($result['msg']);
        }
    }

    /**
     * 禁用银行卡
     * @author Jolon
     */
    public function bank_card_disabled(){
        $id = $this->input->post('id');

        $result = $this->bank_card_model->change_status($id,2);

        if($result['code']){
            $this->success_json([],null,'禁用：操作成功');
        }else{
            $this->error_json('禁用：'.$result['msg']);
        }
    }

    /**
     * 启用银行卡
     * @author Jolon
     */
    public function bank_card_activate(){
        $id = $this->input->post('id');

        $result = $this->bank_card_model->change_status($id,1);
        if($result['code']){
            $this->success_json([],null,'启用：操作成功');
        }else{
            $this->error_json('启用：'.$result['msg']);
        }

    }


}