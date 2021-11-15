<?php

/**
 * Created by PhpStorm.
 * 账务管理 - 1688账号管理表
 * User: Jackson
 * Date: 2019/02/12
 */
class Alibaba_account_model extends Purchase_model
{
    protected $table_name = 'alibaba_account';// 数据表名称

    // 阿里巴巴开放平台api列表
    public $api_list = [];

    // 阿里巴巴开放平台网关
    public $ali_gateway = '';
    public function __construct()
    {
        parent::__construct();
        $this->load->config("alibaba_config");
        if (!empty($this->config->item('ali'))) {
            //获取配置信息
            $alibabaConfig = (object)$this->config->item('ali');
            $this->api_list['buyerView'] = $alibabaConfig->buyerView;
            $this->api_list['urlGet'] = $alibabaConfig->urlGet;
            $this->ali_gateway = $alibabaConfig->gateway;
            $this->refreshTokenUrl = $alibabaConfig->refreshToken;
        } else {
            throw new Exception("未配置1688支付信息");
        }
    }


    /**
     * 获取采购员采购账号（配置的 1688 子账号 ）
     * @author Jolon
     * @param int $user_id 用户ID
     * @param string $account 账号
     * @return array
     */
    public function get_purchase_account_list($user_id = null,$account = null)
    {
        $condition = [];
        if(!is_null($user_id)){
            $condition['user_id'] = $user_id;
        }
        if(!is_null($account)){
            $condition['account_number'] = $account;
        }
        $purchase_account_list = $this->purchase_db
            ->select('account_number')
            ->where($condition)
            ->get('purchase_user_info')
            ->result_array();
        if($purchase_account_list){
            foreach($purchase_account_list as $key => $value){
                $purchase_account_list[$key]['account'] = $value['account_number'];
            }
        }
        return $purchase_account_list?$purchase_account_list:[];
    }

    /**
     * 获取采购员对应的账号（采购员所有可用的账号）
     * @author harvin jolon 2019-2-15
     * @param $id
     * @return array
     */
    public function get_purchase_acccount($id = null){
        //当前用户登录id
        if(is_null($id)){
            $id   = getActiveUserId();
        }

        $this->load->library('alibaba/AliAccount');
        $data  = $this->get_purchase_account_list($id);// 数据权限配置的账号
        $data2 = $this->aliaccount->getSubAccountListByUserId($id);// 采购员的 1688 子账号
        // 公共账号
        $data3 = $this->purchase_db
            ->select('account')
            ->where('bind_account',$id)
            ->or_where_in('account',['琦LL113','琦LL114','琦LL115','琦LL217','琦LL213','琦LL214','琦LL217'])
            ->get('alibaba_account')
            ->result_array();


        $data     = array_column($data, 'account','account');
        $data2    = array_column($data2,'account','account');
        $data3    = array_column($data3,'account','account');

        // 设置默认值，防止报错
        if(!is_array($data) or empty($data))   $data = [];
        if(!is_array($data2) or empty($data2)) $data2 = [];
        if(!is_array($data3) or empty($data3)) $data3 = [];

        $account  = array_merge($data,$data2,$data3);
        return $account;
    }

    /**
     * 根据账号获取基本信息
     * @author Jolon
     * @param string $account 
     * @return array|bool
     */
    public function get_alibaba_account_by_account($account){
        if(empty($account)) return [];

        $where = ['account' => $account];
        $account_info = $this->purchase_db->where($where)->get($this->table_name)->row_array();

        return $account_info;
    }


    /**
     * @desc 请求参数组装(1688)
     * @author Jackson
     * @parames array $param 请求参数组合
     * @parames string $orders 订单号
     * @parames object $accountData 接口请求参数
     * @parames boolean $flag 为true为post提交参数组合，false则批量提交
     * @Date 2019-02-14 18:01:00
     * @return array()
     **/
    public function assemblyParameter(&$param = array(), $orders = '', $accountData = '', $flag = false)
    {
        $_url = '';//调用地址
        if ($flag) {

            $param = [
                'orderIdList' => "[" . $orders . "]",
                'access_token' => trim($accountData->access_token),
            ];
            $_url = 'urlGet';

        } else {

            $param = [
                'webSite' => '1688',
                'orderId' => $orders,
                'access_token' => trim($accountData->access_token),
                'includeFields' => 'OrderInvoice' // 只获取订单的发票信息
            ];
            $_url = 'buyerView';

        }
        $apiInfo = $this->api_list[$_url] . trim($accountData->app_key);

        $args = [
            'param' => $param,
            'apiInfo' => $apiInfo,
            'appSecret' => trim($accountData->secret_key)
        ];
        $param['_aop_signature'] = $this->makeSignature($args);
        $param['_apiInfo'] = $apiInfo;

    }

    // 计算api签名
    public function makeSignature($args)
    {
        $aliParams = array();
        foreach ($args['param'] as $key => $val) {
            $aliParams[] = $key . $val;
        }
        sort($aliParams);
        $sign_str = join('', $aliParams);
        $sign_str = $args['apiInfo'] . $sign_str;
        $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $args['appSecret'], true)));
        return $code_sign;
    }

    // 执行api访问
    public function executeApi($apiInfo, $param)
    {
        $query = http_build_query($param);
        $url = $this->ali_gateway . $apiInfo . '?' . $query;
        $s = getCurlData($url,'','GET');
        $response = json_decode($s, 1);
        return $response;
    }

    /**
     * @desc 获取1688账号信息(根据绑定账户)
     * @author Jackson
     * @param  array $params 查询值
     * @Date 2019-02-12 18:01:00
     * @return mixed
     **/
    public function findOnes($params = array())
    {
        $user_id = isset($params['user_id'])?$params['user_id']:null;
        $account = isset($params['account'])?$params['account']:null;
        if(empty($user_id) and empty($account)) return [];
        $this->load->library('alibaba/AliAccount');
        if($user_id){// 获取当前用户 的一个账号
            if($account != 'yibaisuperbuyers' and stripos($account,'yibaisuperbuyers') !== false ){
                $account_parent = $this->aliaccount->getSubAccountOneByUserId($user_id,$account);// 根据子账号 获取主账号 @user Jolon 2019/05/17

                if(empty($account_parent)){
                    $account_parent =$this->aliaccount->getSubAccountOneByUserId(null,$account);
                }

                if(!empty($account_parent)){
                    $account = isset($account_parent['p_account'])?$account_parent['p_account']:'yibaisuperbuyers';// 找到主账号
                    //判断子账号是否判断付款人
//                    if(empty($account_parent['pay_user_id'])){
//                        throw new Exception('该子账号'.$account_parent['account'].'未绑定付款人,请前往1688子账号页面进行绑定');
//                    }
//                    if($account_parent['pay_user_id']!= getActiveUserId()){
//                        throw new Exception('该子账号'.$account_parent['account'].'未绑定当前付款人,请前往1688子账号页面进行修改');
//                    }
                    if($account_parent['level']!=0){
                        throw new Exception('该子账号'.$account_parent['account'].'未绑定当前付款人非出纳,请前往1688子账号页面进行修改');
                    }
                }else{
                     throw new Exception('该子账号'.$account.'未绑定主账号');
                }
            }

//            $account_list = $this->get_purchase_account_list($user_id,$account);
//            if(empty($account_list) or !isset($account_list[0]) or empty($account_list[0]['account']))
//                return [];
//            $account = $account_list[0]['account'];
            $account_data = $this->findOne(['account' => $account]);
        }else{// 获取 指定的账号
            $account_data = $this->findOne(['account' => $account]);
        }

        return $account_data?$account_data:[];
    }

    public function getAccountList(){
        return $this->purchase_db->select('*')->from($this->table_name)->group_by('app_key')
            ->get()->result_array();
    }

    /**
     * @desc 刷新token
     * @author zouhua
     * @param  string  $refreshToken
     * @param  string  $clientId
     * @param  string  $clientSecret
     * @return mixed
     **/
    public function getRefreshToken($refreshToken, $clientId, $clientSecret) {
        $this->ali_gateway = str_replace('http://', 'https://', $this->ali_gateway);
        $url = "{$this->ali_gateway}{$this->refreshTokenUrl}/{$clientId}";
        $data = ['grant_type' => 'refresh_token', 'client_id' => $clientId, 'client_secret' => $clientSecret, 'refresh_token' => $refreshToken];
        return getCurlData($url, $data);
    }

    public function updateAccessToken($app_key, $data) {
        return $this->purchase_db->where('app_key', $app_key)
            ->update($this->table_name, $data);
    }
}