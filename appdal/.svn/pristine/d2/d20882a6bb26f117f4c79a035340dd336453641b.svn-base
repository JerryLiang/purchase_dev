<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

class Work_desk_data_summary_api extends MY_API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('work_desk/Work_desk_model', 'Work_desk_model');
    }

    /**
     * 批量汇总工作台所有采购员数据-接口入口
     * /Work_desk_data_summary_api/all_buyer_data_summary
     */
    public function all_buyer_data_summary()
    {
        $method_name = $this->input->get_post('method');//指定手动刷新模块（单个）
        if (empty($method_name)) {
            //模块对应的方法名
            $module_arr = [
                'non_oversea_priority_processing', 'non_oversea_unfinished_follow_up',
                'non_oversea_overdue_delivery', 'non_oversea_request_payment_follow_up',
                'non_oversea_refund_follow_up', 'non_oversea_other_pending',
                'oversea_priority_processing', 'oversea_abnormal_follow_up',
                'oversea_rejected_need_deal_with', 'oversea_pay_follow_up',
                'oversea_refund_follow_up', 'oversea_check_goods_follow_up',
                'oversea_product_modification', 'oversea_gateway_statement_invoice',
                'oversea_back_goods_follow_up', 'oversea_unfinished_follow_up',
            ];
        } else {
            $module_arr = [$method_name];
        }

        //服务器保存路径
        $save_path = APPPATH . 'logs' . DIRECTORY_SEPARATOR . 'work_desk_logs' . DIRECTORY_SEPARATOR;
        if (!file_exists($save_path)) @mkdir($save_path, 0777, true);

        foreach ($module_arr as $module) {
            $log_file = $save_path . 'log-' . date('Ymd') . '.txt';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  Work_desk_data_summary_api AllUserDataSummaryRunThread ' . $module . ' >> ' . $log_file . ' 2>&1');
        }
    }

    /**
     * 批量汇总工作台所有采购员数据-实际调用(CLI方式调用)
     * /Work_desk_data_summary_api/AllUserDataSummaryRunThread
     * @param string $method_name 方法名称
     */
    public function AllUserDataSummaryRunThread($method_name)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        if (!empty($method_name)) {
            if (!method_exists($this->Work_desk_model, $method_name)) {
                exit('方法[' . $method_name . ']不存在' . PHP_EOL);
            }
            //调用汇总模块数据方法
            if (strpos($method_name, 'non_oversea') !== FALSE) {
                $category_id = 1;
                $module = str_replace('non_oversea_', '', $method_name);
            } else {
                $category_id = 2;
                $module = str_replace('oversea_', '', $method_name);
            }
            //设置不需要权限控制
            $this->Work_desk_model->set_need_permission(FALSE);
            //删除模块数据
            $result = $this->Work_desk_model->delete_data($category_id, $module);
            if (!$result['flag']) {
                operatorLogInsert(
                    array(
                        'id' => $method_name,
                        'type' => 'WORK_DESK_ALL_BUYER_DATA_SUMMARY',
                        'content' => '工作台数据汇总删除数据',
                        'detail' => $result['msg'],
                        'user' => '计划任务',
                    ),
                    'pur_work_desk_log'
                );
                exit($result['msg'] . PHP_EOL);
            }
            //调用汇总模块数据方法
            $result = call_user_func_array([$this->Work_desk_model, $method_name], []);
            $_msg_start = '数据统计-开始:' . date('Y-m-d H:i:s');
            echo $_msg_start . PHP_EOL;
            echo $result['msg'] . PHP_EOL;
            $_msg_end = '数据统计-结束:' . date('Y-m-d H:i:s');
            echo $_msg_end . PHP_EOL;
            //写入操作日志表
            operatorLogInsert(
                array(
                    'id' => $method_name,
                    'type' => 'WORK_DESK_ALL_BUYER_DATA_SUMMARY',
                    'content' => '工作台数据汇总',
                    'detail' => $_msg_start . ' --- ' . $result['msg'] . ' --- ' . $_msg_end,
                    'user' => '计划任务',
                ),
                'pur_work_desk_log'
            );
        } else {
            exit('无效参数' . PHP_EOL);
        }
    }

    /**
     * 获取智库采购员数据TOKEN
     * @author:luxu
     * @time:2021年5月13号
     **/
    public function get_digitization_jwt(){

        try{
            $jwtDatas = $this->rediss->getData('DIGITIZATION_JWT_TOKEN');
            if(empty($jwtDatas)){

                $url = "http://python2.yibainetwork.com/yibai/python/services/jwt/token?iss=technical_sh_purchase&secret=d2Dw.3Qldacnr4";
                $datas = getCurlData($url,'','GET');
               // $datas = '{"status": 200, "msg": "Success", "exp": "2021-05-14 09:41:04", "jwt":
//"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJ0ZWNobmljYWxfc2hfcHVyY2hhc2UiLCJleHAiOjE2MjA5NTY0NjR9.-KCftQM6yJrre4sKbj8uttkx-52HWZR_rY0g9QdI8wY"}';
                $datas = json_decode($datas,True);
                if(isset($datas['status']) && $datas['status'] == 200){

                    $expiretimedays = round((strtotime($datas['exp']) - strtotime(date("Y-m-d H:i:s")))/86400);
                    $expiretime = 3600*24*$expiretimedays;
                    $this->rediss->setData('DIGITIZATION_JWT_TOKEN', json_encode($datas),$expiretime);
                    return $datas['jwt'];

                }

                throw new Exception("获取TOKE失败");
            }else{
                $jwtDatas = json_decode($jwtDatas,True);
                return $jwtDatas['jwt'];
            }

        }catch ( Exception $exp ){
            throw new Exception($exp);

        }
    }

    /**
     * 拉取智库采购员数据
     * /Work_desk_data_summary_api/get_digitization_data
     */
    public function get_digitization_data()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //接口调用日志
        operatorLogInsert(
            array(
                'type' => 'WORK_DESK_DIGITIZATION_DATA_SUMMARY',
                'content' => '接口调用成功',
                'user' => '计划任务',
            ),
            'pur_work_desk_log'
        );

        try {
            $token = $this->get_digitization_jwt();
            $request_url = 'bi.yibainetwork.com:8000/bi/purchase/data1?jwt='.$token;
            $result = getCurlData($request_url, [], 'GET');
            $result = json_decode($result, TRUE);
            //接口调用日志
            operatorLogInsert(
                array(
                    'type' => 'WORK_DESK_DIGITIZATION_DATA_SUMMARY',
                    'content' => '智库接口数据接收',
                    'detail' => $result,
                    'user' => '计划任务',
                ),
                'pur_work_desk_log'
            );

            //请求结果数据处理
            if (isset($result['status']) && $result['status'] == 1) {
                //没有可处理的数据
                if (empty($result['data_list']) or !is_array($result['data_list'])) {
                    throw new Exception('接口返回列表数据为空');
                }
                $time = date('Y-m-d H:i:s');
                //处理要保存的数据
                $save_data = [];
                foreach ($result['data_list'] as $item) {
                    $save_data[] = [
                        'sales_id' => $item['sales_id'],
                        'sales_name' => $item['sales_name'],
                        'group_id' => $item['group_id'],
                        'group_name' => $item['group_name'],
                        'yesterday_sale' => $item['yesterday_sale'],
                        'achieve_7_days' => $item['achieve_7_days'],
                        'yesterday_sku_num' => $item['yesterday_sku_num'],
                        'month_sku_num' => $item['month_sku_num'],
                        'modify_time' => $item['modify_time'],
                        'create_time' => $time,
                    ];
                }
                //保存数据前删除全部数据
                $this->db->query('DELETE FROM pur_purchase_sales_performance');

                //保存数据
                if (!empty($save_data)) {
                    $insert_count = 0;
                    $insert_count += $this->db->insert_batch('pur_purchase_sales_performance', $save_data);
                    //保存日志
                    operatorLogInsert(
                        array(
                            'type' => 'WORK_DESK_DIGITIZATION_DATA_SUMMARY',
                            'content' => '拉取智库采购员数据成功',
                            'detail' => '拉取数据条数：' . $insert_count,
                            'user' => '计划任务',
                        ),
                        'pur_work_desk_log'
                    );
                }

                exit(date('Y-m-d H:i:s') . ' ' . '拉取智库采购员数据成功');
            } else {
                $msg = isset($result['message']) ? $result['message'] : '请求失败';
                $msg = date('Y-m-d H:i:s') . ' ' . $msg;
                throw new Exception($msg);
            }
        } catch (Exception $e) {
            //保存日志
            operatorLogInsert(
                array(
                    'type' => 'WORK_DESK_DIGITIZATION_DATA_SUMMARY',
                    'content' => '拉取智库采购员数据异常',
                    'detail' => $e->getMessage(),
                    'user' => '计划任务',
                ),
                'pur_work_desk_log'
            );
            exit($e->getMessage());
        }
    }

    /**
     * 根据键名删除redis缓存
     * /Work_desk_data_summary_api/del_redis_by_key
     */
    public function del_redis_by_key()
    {
        $key = $this->input->get_post('key');
        if (empty($key)) exit('请指定key');
        $is_exist = $this->rediss->existsData($key);
        if (!$is_exist) exit('指定的key不存在');
        $res = $this->rediss->deleteData($key);
        if ($res) {
            echo '删除成功'. date('Y-m-d H:i:s') . '<br>';
        } else {
            echo '删除失败'. date('Y-m-d H:i:s') . '<br>';
        }
    }

    /**
     * 更新url参数
     * /Work_desk_data_summary_api/update_url_info
     */
    public function update_url_info()
    {
        $params = $this->input->get('params');          //参数（多组参数用|P|分隔，格式：参数名1=值1&参数名2=值2|P|参数名3=值3&参数名4=值4）
        $category_id = $this->input->get('category_id');//小组类型（1-非海外仓，2-海外仓）
        $module = $this->input->get('module');          //模块
        $type = $this->input->get('type');              //类型
        $url = $this->input->get('url');                //前端接口地址（多个url用逗号分隔，格式：api/get_data_list1,api/get_data_list2）
        $method = $this->input->get('method');          //前端请求方式（get,post）

        try {
            if (empty($params) or empty($category_id) or empty($module) or empty($type)
                or empty($url) or empty($method)) exit('参数不能为空');

            //分隔‘参数’数据
            $params_arr = explode('|P|', urldecode($params));//分隔结果，例如：['参数名1=值1&参数名2=值2','参数名3=值3&参数名4=值4']
            if (empty(array_filter($params_arr))) exit('params参数格式无效');
            //分隔url数据
            $url_arr = explode(',', $url);//分隔结果，例如：['api/get_data_list1','api/get_data_list2']
            if(count($params_arr) != count($url_arr)) exit('url和params必须对应');

            //组织最终保存的参数
            $params_save = [];
            foreach ($params_arr as $_idx => $_param) {
                $_param_arr = explode('&', $_param);//分隔每对参数（分隔结果，例如：['参数名1=值1','参数名2=值2']）
                if (empty(array_filter($_param_arr))) exit('参数格式无效');

                foreach (array_filter($_param_arr) as $idx => $item) {
                    list($key, $value) = explode('=', $item);
                    if ('uid' == strtolower($key)) {
                        continue;
                    } elseif (strpos($key, '[]') !== FALSE) {//处理数组形式的参数
                        $key = str_replace('[]', '', $key);
                        $key .= $key . '[' . $idx . ']';
                        $params_save[$_idx][$key] = $value;
                    } else {
                        $params_save[$_idx][$key] = $value;
                    }
                }
            }
            if (empty($params_save)) exit('没有可修改的数据');

            //更新条件
            $where = [
                'category_id' => $category_id,
                'module' => $module,
                'type' => $type,
            ];
            //要更新的数据
            $set = [
                'method' => trim($method),
                'url' => json_encode($url_arr),
                'params' => json_encode($params_save),
            ];
            //更新
            $res = $this->db->update('pur_work_desk_url_info', $set, $where, 1);
            if ($res) {
                exit('修改成功' . date('Y-m-d H:i:s'));
            } else {
                exit('修改失败' . date('Y-m-d H:i:s'));
            }
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
}