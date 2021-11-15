<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * Class Login
 * @desc 用户登录、修改密码、注销
 * @author liht
 * @since 20180710
 *
 *
 */
class Login extends MX_Controller {

    protected $data;

    public function __construct()
    {
        parent::__construct();
        $this->data['status'] = 0;
        $this->load->model('Login_model','login');
        $this->load->library(array('asset','cookie','rediss'));
    }

    public  function test()
    {
//        $str = 'Kyr2ZcfCfOYgNj7qP16OETHsNjM7bjRwM3x5pXf0Tv6+0yVgycdlP8sKqc/75efkyLxKD0EzkoBsPzbuEybMfSgFXqFhzhCQlv84xhtzUSP9VDtHhapF18omiTusTjDnJ9LEvBico2VGcqdQo+yRPnTPTlZKIj+NYGm0bS9/gEU=';
//        $str =  $this->rsa->privateDecrypt($str);
//
//        pr($str);
        $data = '{
  "PUR_PURCHASE_IS_CLUDE_TAX":  "{\"0\":\"否\",\"1\":\"是\"}",
  
    "PUR_PURCHASE_IS_DRAWBACK":  "{\"0\":\"否\",\"1\":\"是\"}",
  
    "PUR_PURCHASE_TYPE":  "{\"1\":\"国内\",\"2\":\"海外\",\"3\":\"FBA\"}",
  
    "PUR_PURCHASE_PRODUCT_IS_NEW":  "{\"0\":\"否\",\"1\":\"是\"}",
  
    " PUR_PURCHASE_SUGGEST_STATUS":  "{\"1\":\"待处理\",\"2\":\"驳回待审核\",\"3\":\"驳回\",\"4\":\"同意\",\"5\":\"待采购驳回\",\"6\":\"作废\"}",
  
    "PUR_PURCHASE_IS_PLAN_ORDER":  "{\"0\":\"否\",\"1\":\"是\"}",
  
    "PUR_PURCHASE_PUR_ORDER_STATUS":  "{\"0\":\"未生产\",\"1\":\"已生成\"}",
  
    "PUR_PURCHASE_PUR_ORDER_STATUS_ALL":  "{\"0\":\"未生成\",\"1\":\"已生成\",\"2\":\"待审核\",\"3\":\"审核驳回\"}",
  
    "PUR_PURCHASE_PUR_ORDER_STATUS_FOR_WAITREJECT":  "{\"2\":\"待审核\",\"3\":\"审核驳回\",\"1\":\"已生成\"}",
  
    "PUR_PURCHASE_PAY_TYPE":  "{\"1\":\"支付宝\",\"2\":\"对公支付\",\"3\":\"对私支付\"}",
  
    "PUR_PURCHASE_REQUISITION_METHOD":  "{\"1\":\"比例请款\",\"2\":\"入库数量\"}",
  
    "PUR_PURCHASE_FREIGHT_FORMULA_MODE":  "{\"volume\":\"体积\",\"weight\":\"重量\"}",
  
    "PUR_PURCHASE_FREIGHT_PAYMENT":  "{\"1\":\"甲方支付\",\"2\":\"乙方支付\"}",
  
    "PUR_PURCHASE_SETTLEMENT_RATIO":  "{\"5%\":\"5%\",\"10%\":\"10%\",\"20%\":\"20%\",\"30%\":\"30%\",\"40%\":\"40%\",\"50%\":\"50%\",\"60%\":\"60%\",\"70%\":\"70%\",\"80%\":\"80%\",\"90%\":\"90%\",\"100%\":\"100%\"}",
  
    "PUR_PURCHASE_INTERCEPT_REASON":  "{\"供应商缺失\":\"供应商缺失\",\"链接缺失\":\"链接缺失\",\"税点缺失\":\"税点缺失\",\"开票信息缺失\":\"开票信息缺失\",\"退税率缺失\":\"退税率缺失\"}",
  
    "PUR_PURCHASE_ORDER_REJECT_REASON":  "{\"供应商停产\":\"供应商停产\",\"数量调整\":\"数量调整\"}",
  
    "PUR_PURCHASE_IS_EXPEDITED":  "{\"1\":\"否\",\"2\":\"是\"}",
  
    "PUR_PURCHASE_ORDER_STATUS":  "{\"1\":\"等待采购询价\",\"2\":\"信息变更等待审核\",\"3\":\"待采购审核\",\"5\":\"待销售审核\",\"6\":\"等待生成进货单\",\"7\":\"等待到货\",\"8\":\"已到货待检测\",\"9\":\"全部到货\",\"10\":\"部分到货等待剩余到货\",\"11\":\"部分到货不等待剩余到货\",\"12\":\"作废订单待审核\",\"13\":\"作废订单待退款\",\"14\":\"已作废订单\"}",
  
    "PUR_PURCHASE_REQUEST_PAYOUT_TYPE":  "{\"0\":\"全额付款\",\"1\":\"按（入库数量）请款\",\"2\":\"按（订单数量-已请款数量-已取消数量-未到货数量）请款\",\"3\":\"按（订单数量-已请款数量-已取消数量）请款\",\"4\":\"按（订单数量-已取消数量）手动请款\",\"5\":\"比例支付\"}",
  
    "PUR_PURCHASE_PAY_STATUS":  "{\"10\":\"未申请付款\",\"20\":\"待经理审核\",\"21\":\"经理驳回\",\"30\":\"待财务审核\",\"31\":\"财务驳回\",\"40\":\"待财务付款\",\"50\":\"已部分付款\",\"51\":\"已付款\",\"52\":\"出纳驳回\",\"90\":\"已取消\"}",
  
    "PUR_PURCHASE_SOURCE":  "{\"1\":\"合同\",\"2\":\"网采\"}",
  
    "PUR_PURCHASE_DEMAND_TYPE":  "{\"\":\"全部\",\"1\":\"计划单\",\"2\":\"预测单\"}",
  
    "PUR_PURCHASE_PRODUCT_STATUS":  "{\"all\":\"全部\",\"4\":\"在售中\",\"0\":\"审核不通过\",\"1\":\"刚开发\",\"2\":\"编辑中\",\"3\":\"预上线\",\"5\":\"已滞销\",\"6\":\"待清仓\",\"7\":\"已停售\",\"8\":\"刚买样\",\"9\":\"待品检\",\"10\":\"拍摄中\",\"11\":\"产品信息确认\",\"12\":\"修图中\",\"14\":\"设计审核中\",\"15\":\"文案审核中\",\"16\":\"文案主管终审中\",\"17\":\"试卖编辑中\",\"18\":\"试卖在售中\",\"19\":\"试卖文案终审中\",\"20\":\"预上线拍摄中\",\"21\":\"物流审核中\",\"22\":\"缺货中\",\"27\":\"作图审核中\",\"29\":\"开发检查中\",\"30\":\"拍摄中、编辑中\",\"100\":\"未知\"}",
  
    "PUR_PURCHASE_PRODUCT_SUPPLYSTATUS":  "{\"1\":\"正常\",\"2\":\"停产\",\"3\":\"断货\",\"4\":\"停货\"}",
  
    "PUR_PURCHASE_PRODUCT_AUDITSTATUS":  "{\"1\":\"待采购审核\",\"2\":\"待品控审核\",\"3\":\"审核通过\",\"4\":\"驳回\"}",
  
    "PUR_PURCHASE_PRODUCT_SCREESTATUS":  "{\"10\":\"待采购经理审核\",\"11\":\"采购经理驳回\",\"20\":\"待开发确认\",\"30\":\"待采购确认\",\"40\":\"已变更\",\"50\":\"已结束\"}",
  
    "PUR_PURCHASE_PRODUCT_SCREE_APPLY_REASON":  "{\"2\":\"停产\",\"3\":\"断货\",\"4\":\"停货\",\"99\":\"需要起订量\",\"100\":\"其他\"}",
  
    "PUR_PURCHASE_PRODUCT_WAREHOUSE_TYPE":  "{\"1\":\"国内仓\",\"2\":\"海外仓\",\"3\":\"第三方仓\"}",
  
    "PUR_PURCHASE_PRODUCT_MOD_STATUS":  "{\"\":\"请选择\",\"1\":\"待采购审核\",\"2\":\"待品控审核\",\"3\":\"审核通过\",\"4\":\"审核驳回\"}",
  
    "PUR_PURCHASE_PRODUCT_IS_SAMPLE_DOWN_BOX":  "{\"\":\"请选择\",\"2\":\"不拿样\",\"3\":\"拿样\"}",
  
    "PUR_PURCHASE_PRODUCT_SAMPLE_CAHECK_RESULT":  "{\"\":\"请选择\",\"1\":\"待确认\",\"2\":\"合格\",\"3\":\"不合格\"}",
  
    "PUR_PURCHASE_SUUPLIER_CROSSB_ORDER":  "{\"0\":\"否\",\"1\":\"是\"}",
  
    "PUR_PURCHASE_SUUPLIER_SETTLEMENT_METHOD":  "{\"0\":\"周结\",\"1\":\"月结\"}",
  
    "PUR_PURCHASE_SHIPPING_METHOD":  "{\"1\":\"自提\",\"2\":\"快递\",\"3\":\"物流\",\"4\":\"送货\",\"5\":\"直发整柜\",\"6\":\"直发散货\"}",
  
    "PUR_PURCHASE_ORDER_DESTROY":  "{\"0\":\"否\",\"1\":\"是\"}",
  
    "PUR_PURCHASE_REQUEST_PAYOUT_STATUS":  "{\"10\":\"待处理\",\"20\":\"已审批\",\"30\":\"已作废\"}",
  
    "PUR_PURCHASE_IRANSPORT_STYLE":  "{\"10\":\"海运整柜（不包税）\",\"11\":\"海运整柜（包税）\",\"12\":\"海运散货（不包税）\",\"13\":\"海运散货（包税）\",\"20\":\"空运散货（不包税）\",\"21\":\"空运散货（包税）\",\"22\":\"空运整柜（不包税）\",\"23\":\"空运整柜（包税）\",\"30\":\"铁路散货（不包税）\",\"31\":\"铁路散货（包税）\",\"32\":\"铁路整柜（不包税）\",\"33\":\"铁路整柜（包税）\",\"40\":\"快递（不包税）\",\"41\":\"快递（包税）\",\"42\":\"陆运（不包税）\",\"43\":\"陆运（包税)\"}",
  
    "PUR_PURCHASE_PAYMENT_STATUS":  "{\"30\":\"待财务审核\",\"31\":\"财务驳回\",\"40\":\"待财务付款\",\"51\":\"已付款\"}",
  
    "PUR_PURCHASE_PRODUCT_IS_SAMPLE_DOWN_BOXS":  "{\"1\":\"\",\"2\":\"不拿样\",\"3\":\"拿样\"}"
}';
        $data = json_decode($data,true);

        $this->rediss->setData('STATUS',$data);

        $result = $this->rediss->getData('STATUS');
        pr($result);die;
    }

    /**
     * 获取用户权限
     */
    public function getUserInfo(){
        $action_token = $this->input->post_get('access_token');

        $userData = $this->cookie->getData('userdata');

        if(empty($action_token) && isset($userData['uid']) && !empty($userData['uid'])) {
            $authData = $this->rediss->getData($userData['uid']);
            if(isset($authData['session_id']) && !empty($authData['session_id'])) {
                if($authData['session_id'] != $userData['session_id']) {
                    $this->cookie->deleteData('userdata');
                    http_response(array(
                        'status'=>0,
                        'errorCode'=>3047,
                        'path' => SECURITY_PATH,
                        'http_status_code'=>402,
                    ),402);
                }

                $this->data['status'] = 1;
                $this->data['data'] = $authData;
                http_response($this->data);
            }
        }

        if (empty($action_token)){
            http_response(array(
                'status'=>0,
                'errorCode'=>3047,
                'path' => SECURITY_PATH,
                'http_status_code'=>401,
            ),401);
        }

        $client_ip = get_client_ip();

        $authData = $this->rediss->getData($action_token);
		$authData = json_decode($authData,true)
        if (IP_VALIDATE && $authData['client_ip'] != $client_ip){
            http_response(array(
                'status'=>0,
                'errorCode'=>3047,
                'path' => SECURITY_PATH,
                'http_status_code'=>402,
            ),402);
        }

        if (empty($authData)){
            http_response(array(
                'status'=>0,
                'errorCode'=>3047,
                'path' => SECURITY_PATH,
                'http_status_code'=>401,
            ),401);
        }

        $user_data = array(
            'uid' => $authData['uid'],
            'user_name' => $authData['user_name'],
            'session_id' => $authData['session_id'],
            'user_code' => $authData['staff_code'],
        );
		
		$authData['path'] = SECURITY_PATH;
        $authData['pur_big_file_path'] = SECURITY_PUR_BIG_FILE_PATH;

		$this->data['status'] = 1;
        $this->data['data'] = $authData;
		
		unset($authData['permissions']);
        $this->cookie->setData('userdata',$user_data);
        $this->rediss->setData($authData['uid'],$authData);
        $this->rediss->delete($action_token);

        http_response($this->data);
    }

    
    /**
     * 验证第三方登录
     */
    public function apiLogin(){
        $access_token = $this->input->post_get('access_token');
        $client_ip = $this->input->post_get('client_ip');

        require_once APPPATH . "third_party/CurlRequest.php";
        if (empty($access_token) || empty($client_ip)){
            $this->data['errorCode'] = 3001;
            http_response($this->data);
        }

        $curlRequest = CurlRequest::getInstance();
        try{
            $params = array(
                'session_id' => $access_token,
                'client_ip' => $client_ip,
            );
            
            //请求权限中心数据
            $curlRequest->setSessionId($access_token);
            $curlRequest->setServer(SECURITY_API_HOST,SECURITY_API_SECRET,SECURITY_APP_ID);
            $result = $curlRequest->cloud_post('login/login/getUserLoginInfo', $params);
            
            if (isset($result['status']) && $result['status'] && isset($result['data']) && $result['data']){
                $result['data']['client_ip'] = $client_ip;
                $this->rediss->setData($access_token,json_encode($result['data']));
                $this->data['status'] = 1;

                //登录成功，记录登录日志
                $this->load->model("api/system/Admin_log_model");
                $username = isset($result['data']['user_name']) ? $result['data']['user_name'] : "";
                $uid= isset($result['data']['uid'])? $result['data']['uid'] : "";
                $staff_code = isset($result['data']['staff_code']) ? $result['data']['staff_code'] : "";
		$user_role = isset($result['data']['role_data']) ? implode(',',array_column($result['data']['role_data'],'name')) : '';
                $this->Admin_log_model->write_log([
                    "route" => "/" . trim($this->uri->uri_string, "/"),
                    "user_name" => $username,//操作者用户名
                    "user_role" => $user_role,//操作者角色
                    "ip" => $client_ip,
                    "description" => "用户{$username}[工号：{$staff_code}]登录",
                     'uid'=>$uid       
                ]);
                //设置系统采购单所有状态
                $this->load->model("api/system/Status_set_model");
                $this->Status_set_model->get_set(['uid'=>$uid]);
                
            }
        }catch (Exception $e){
            $this->data['errorCode'] =1002;
        }

        http_response($this->data);
    }

}
/* End of file api.php */
/* Location: ./application/modules/demo/controllers/api.php */