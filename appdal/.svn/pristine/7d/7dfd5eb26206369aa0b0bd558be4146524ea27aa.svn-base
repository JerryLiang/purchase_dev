<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020/11/3
 * Time: 14:42
 */

class Message_model extends Purchase_model
{

    /**
     * 采购系统消息基类模型，主要处理消息的中转
     * author:luxu
     * time:2020/11/3
     **/
    private $subjectData = [
        'purchase' => ['headkeyword' => 'PO','message'=>'采购单审核'],
        'product'  => ['headkeyword' => 'product','message'=>'商品管理模块审核'],
        'money'    => ['headkeyword' => 'money','message'=>'请款单模块审核'],
    ];

    protected $table_name = 'purchase_invoice_list';// 数据表名称
    protected $declare_customs_table = 'declare_customs';
    protected $table_invoice_detail = 'purchase_items_invoice_info';
    protected $table_invoice_item = 'purchase_invoice_item';
    protected $table_purchase_order = 'purchase_order';
    protected $table_purchase_order_items = 'purchase_order_items';
    protected $table_purchase_order_reportloss = 'purchase_order_reportloss';
    protected $table_product = 'product';


    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/purchase_user_model');
        $this->load->model('supplier/supplier_model');

    }

    /**
     * 查找信息接收人
     * @number  string 数据单号
     * @subject string 消息主体
     **/
    public function searchPushUserId($number,$subject){

        try{

            if( $subject == 'purchase'){

                // 如果消息是从采购单模块发出，通过PO 单号查询 采购单采购员
                $buyerData = $this->purchase_db->from("purchase_order");
                if(is_array($number)) {
                    $buyerData->where_in("purchase_number", $number);
                }else{
                    $buyerData->where("purchase_number",$number);
                }
                $result = $buyerData->select("buyer_id,buyer_name,purchase_number")->get()->result_array();
                return $result;
            }

            if($subject == 'product'){

                $productLogData = $this->purchase_db->from('product_update_log');

                if( is_array($number)){

                    $productLogData->where_in("id",$number);
                }else{
                    $productLogData->where("id",$number);
                }

                $result = $productLogData->select("create_user_name,sku")->get()->result_array();
                return $result;
            }

            /**
              * 请款单模块
             **/
            if($subject == 'money'){

                $productLogData = $this->purchase_db->from('purchase_order_pay');

                if( is_array($number)){

                    $productLogData->where_in("id",$number);
                }else{
                    $productLogData->where("id",$number);
                }

                $result = $productLogData->select("applicant,requisition_number")->get()->result_array();
                return $result;
            }

            /**
              * 取消未到货
             **/

            if($subject == 'determine'){

                $productLogData = $this->purchase_db->from('purchase_order_cancel');

                if( is_array($number)){

                    $productLogData->where_in("id",$number);
                }else{
                    $productLogData->where("id",$number);
                }

                $result = $productLogData->select("cancel_number,create_user_name,create_user_id")->get()->result_array();
                return $result;
            }

            if($subject == 'report'){

                $productLogData = $this->purchase_db->from('purchase_order_reportloss');

                if( is_array($number)){

                    $productLogData->where_in("id",$number);
                }else{
                    $productLogData->where("id",$number);
                }

                $result = $productLogData->select("bs_number,apply_person")->get()->result_array();
                return $result;
            }

            // SKU 屏蔽模块
            if( $subject == 'scree'){

                $productLogData = $this->purchase_db->from('product_scree');

                if( is_array($number)){

                    $productLogData->where_in("id",$number);
                }else{
                    $productLogData->where("id",$number);
                }

                $result = $productLogData->select("sku,apply_user_id,apply_user,apply_user_id AS apply_id")->get()->result_array();
                return $result;
            }

            // 发票清单号 pur_purchase_invoice_list
            if($subject == 'declare'){

                $productLogData =  $this->purchase_db->from($this->table_invoice_detail. ' b')
                    ->join($this->table_invoice_item.' a','a.invoice_number=b.invoice_number AND a.purchase_number = b.purchase_number AND a.sku = b.sku','left')
                    ->join($this->table_name. ' c', 'b.invoice_number=c.invoice_number', 'left');

                if( is_array($number)){

                    $productLogData->where_in("b.id",$number);
                }else{
                    $productLogData->where("b.id",$number);
                }

                $result = $productLogData->select("c.submit_user,c.invoice_number")->get()->result_array();
                return $result;
            }

            if($subject == 'childreninvoice'){

                $productLogData = $this->purchase_db->from('purchase_items_invoice_info');

                if( is_array($number)){

                    $productLogData->where_in("children_invoice_number",$number);
                }else{
                    $productLogData->where("children_invoice_number",$number);
                }

                $result = $productLogData->select("invoice_number,children_invoice_number,tovoid_user")->get()->result_array();
                return $result;
            }

            if( $subject == 'abnormal'){
                $productLogData = $this->purchase_db->from('purchase_order_charge_against_records');

                if( is_array($number)){

                    $productLogData->where_in("id",$number);
                }else{
                    $productLogData->where("id",$number);
                }

                $result = $productLogData->select("charge_against_number,create_user_name,create_user_id")->get()->result_array();
                return $result;
            }

            if($subject == 'supplier'){

                $productLogData = $this->purchase_db->from('pur_supplier_update_log');

                if( is_array($number)){

                    $productLogData->where_in("id",$number);
                }else{
                    $productLogData->where("id",$number);
                }

                $result = $productLogData->select("apply_no,create_user_id,create_user_name")->get()->result_array();
                return $result;
            }
            if($subject == 'alternative'){
                $alternativeLogs = $this->purchase_db->from("alternative_supplier_log")->where_in("id",$number)
                    ->get()->result_array();
                return $alternativeLogs;
            }
            //拜访供应商上传
            if($subject == 'visit_report'){
                $visit_info = [];
                $visit_report_info = $this->supplier_model->get_audit_visit_info($number[0],false);
                if (!empty($visit_report_info)) {
                    if ($visit_report_info['audit_id_director']) {
                        $visit_info[] =
                            [
                                'supplier_name'=>$visit_report_info['supplier_name'],
                                'apply_no'     =>$visit_report_info['apply_no'],
                                'apply_person' =>  $visit_report_info['audit_user_director'],
                                'apply_id' =>  $visit_report_info['audit_id_director']

                            ];
                    }
                    if ($visit_report_info['audit_id_manage']) {
                        $visit_info[] =
                            [
                                'supplier_name'=>$visit_report_info['supplier_name'],
                                'apply_no'     =>$visit_report_info['apply_no'],
                                'apply_person' =>  $visit_report_info['audit_user_manage'],
                                'apply_id' =>  $visit_report_info['audit_id_manage']


                            ];


                    }


                }


                return $visit_info;
            }

            //拜访供应商上传
            if($subject == 'visit_audit'){
                $visit_info = [];
                $visit_report_info = $this->supplier_model->get_audit_visit_info($number[0],false);
                if (!empty($visit_report_info)) {
                    if ($visit_report_info['audit_id_director']) {
                        $visit_info[] =
                            [
                                'supplier_name'=>$visit_report_info['supplier_name'],
                                'apply_no'     =>$visit_report_info['apply_no'],
                                'apply_person' =>  $visit_report_info['audit_user_director'],
                                'apply_id' =>  $visit_report_info['audit_id_director']

                            ];
                    }
                    if ($visit_report_info['audit_id_manage']) {
                        $visit_info[] =
                            [
                                'supplier_name'=>$visit_report_info['supplier_name'],
                                'apply_no'     =>$visit_report_info['apply_no'],
                                'apply_person' =>  $visit_report_info['audit_user_manage'],
                                'apply_id' =>  $visit_report_info['audit_id_manage']


                            ];


                    }


                }


                return $visit_info;
            }



        }catch ( Exception $exp ){

            echo $exp->getMessage();
        }
    }

    /**
     * 采购单模块组装消息
     * @param  $userData array  消息目标用户
     *         $messageSubject array 消息主体
     **/
    protected  function purchaseassembleMessage($userData,$messageSubject){

        if(!empty($userData)){

            foreach($userData as $key=>&$value){
                //提示消息拼接
                $value['pushMessage'] = "PO:".$value['purchase_number']."被驳回,驳回人:".$messageSubject['user'].",原因:".$messageSubject['message'];
                //操作类型
                $value['type'] = $messageSubject['type'];
                $value['module'] = 'purchase';
                $value['create_time'] = date("Y-m-d H:i:s",time());
                $value['param'] = $value['purchase_number'];
                $value['recv_name'] = $value['buyer_name'];
            }
            $this->pushSwooleData($userData);

        }
    }

    /**
     * 采购单模块组装消息
     * @param  $ata arruserDay  消息目标用户
     *         $messageSubject array 消息主体
     **/
    protected  function AliOrderMessage($userData,$messageSubject){
        if(!empty($userData)){
            foreach($userData as $key=>&$value){
                //提示消息拼接
                $value['pushMessage'] = $messageSubject['message'];
                //操作类型
                $value['type'] = $messageSubject['type'];
                $value['module'] = 'purchase';
                $value['create_time'] = date("Y-m-d H:i:s",time());
                $value['param'] = $value['purchase_number'];
                $value['recv_name'] = $value['buyer_name'];
            }
            $this->pushSwooleData($userData);

        }
    }

    /**
     * 采购单模块组装消息
     * @param  $userData array  消息目标用户
     *         $messageSubject array 消息主体
     **/
    protected  function CheckProductMessage($userData,$messageSubject){
        if(!empty($messageSubject)){
            $messageSubject['pushMessage'] = $messageSubject['message'];
            $messageSubject['module'] = 'inspection_is_not_up_to_standard';
            $messageSubject['create_time'] = date("Y-m-d H:i:s",time());
            $messageSubject['param'] = $messageSubject['purchase_number'];
            $messageSubject['url_info'] = [
                "method" => "get",
                "params" => [[
                    "tab_tips_count"=>1,
                    "limit" => 20,
                    "status" => 0,
                    "check_code" => $messageSubject['purchase_number'],
                ]],
                "url" => [
                    "/api/supplier_check/check_product/get_data_list"
                ],
            ];
            unset($messageSubject['message']);
            $this->pushSwooleData([$messageSubject]);
        }
    }

    /**
     * 备选供应商审核驳回通知消息
     * @param  $ata arruserDay  消息目标用户
     *         $messageSubject array 消息主体

     **/
    protected  function alternativeMessage($userData,$messageSubject){

        if(!empty($userData)){
            $userName = get_buyer_name();

            foreach($userData as $key=>&$value){

                $value['pushMessage'] = "sku:".$value['sku'].",备选供应商修改,被驳回，驳回人：".$messageSubject['user'].",原因:".$messageSubject['message'];
                $value['type'] = $messageSubject['type'];
                $value['module'] = 'request_funds';
                $value['create_time'] = date("Y-m-d H:i:s",time());
                $value['param'] = $value['requisition_number'];
                $value['recv_name'] = isset($userName[$value['applicant']])?$userName[$value['applicant']]:'';
                $value['apply_id'] = $value['applicant'];
            }
            $this->pushSwooleData($userData);

        }
    }

    /**
     * 请款单模块消息组装
     * @param  $userData array  消息目标用户
     *         $messageSubject array 消息主体
     **/
    protected  function moneyMessage($userData,$messageSubject){

         $userName = get_buyer_name();
        if(!empty($userData)){

            foreach($userData as $key=>&$value){
                //提示消息拼接
                $value['pushMessage'] = "请款单号:".$value['requisition_number']."被驳回,驳回人:".$messageSubject['user'].",原因:".$messageSubject['message'];
                //操作类型
                $value['type'] = $messageSubject['type'];
                $value['module'] = 'request_funds';
                $value['create_time'] = date("Y-m-d H:i:s",time());
                $value['param'] = $value['requisition_number'];
                $value['recv_name'] = isset($userName[$value['applicant']])?$userName[$value['applicant']]:'';
                $value['apply_id'] = $value['applicant'];

            }
            $this->pushSwooleData($userData);
        }
    }

    protected  function reportMessage($userData,$messageSubject){

        foreach($userData as $key=>&$value){
            //提示消息拼接
            $value['pushMessage'] = "报损单号:".$value['bs_number']."被驳回,驳回人:".$messageSubject['user'].",原因:".$messageSubject['message'];
            //操作类型
            $value['type'] = $messageSubject['type'];
            $value['module'] = 'report';
            $value['create_time'] = date("Y-m-d H:i:s",time());
            $value['param'] = $value['bs_number'];
            $value['recv_name'] = $value['apply_person'];
            $value['apply_id'] = '';

        }
        $this->pushSwooleData($userData);
    }

    /**
     * 取消未到货模块消息组装
     * @param  $userData array  消息目标用户
     *         $messageSubject array 消息主体
     **/
    protected  function determineMessage($userData,$messageSubject){

        foreach($userData as $key=>&$value){
            //提示消息拼接
            $value['pushMessage'] = "取消未到货编码:".$value['cancel_number']."被驳回,驳回人:".$messageSubject['user'].",原因:".$messageSubject['message'];
            //操作类型
            $value['type'] = $messageSubject['type'];
            $value['module'] = 'cancel';
            $value['create_time'] = date("Y-m-d H:i:s",time());
            $value['param'] = $value['cancel_number'];
            $value['recv_name'] = $value['create_user_name'];
            $value['apply_id'] = $value['create_user_id'];

        }
        $this->pushSwooleData($userData);
    }

    protected  function screeMessage($userData,$messageSubject){

        foreach($userData as $key=>&$value){
            //提示消息拼接
            $value['pushMessage'] = "sku:".$value['sku']."被驳回,驳回人:".$messageSubject['user'].",原因:".$messageSubject['message'];
            //操作类型
            $value['type'] = $messageSubject['type'];
            $value['module'] = 'scree';
            $value['create_time'] = date("Y-m-d H:i:s",time());
            $value['param'] = $value['sku'];
            $value['recv_name'] = $value['apply_user'];
            $value['apply_id'] = $value['apply_id'];

        }
        $this->pushSwooleData($userData);
    }


    /**
      * 产品管理模块消息组装
     **/

    protected function productMessage($userData,$messageSubject){

        $userName = $this->purchase_user_model->get_user_all_list();
        $data = [];
        if(!empty($userName)){

            $data = array_column($userName,NULL,'name');
        }

        if(!empty($userData)){

            foreach($userData as $key=>&$value){
                //提示消息拼接
                $value['pushMessage'] = "sku:".$value['sku']."被驳回,驳回人:".$messageSubject['user'].",原因:".$messageSubject['message'];
                //操作类型
                $value['type'] = $messageSubject['type'];
                $value['module'] = 'product';
                $value['create_time'] = date("Y-m-d H:i:s",time());
                $value['param'] = $value['sku'];
                $value['recv_name'] = $value['create_user_name'];
                $value['apply_id'] = isset($data[$value['create_user_name']])?$data[$value['create_user_name']]['id']:'';

            }
            $this->pushSwooleData($userData);

        }
    }

    protected  function abnormalMessage($userData,$messageSubject){

        foreach($userData as $key=>&$value){
            //提示消息拼接 charge_against_number,create_user_name,create_user_id
            $value['pushMessage'] = "申请编号:".$value['charge_against_number']."被驳回,驳回人:".$messageSubject['user'].",原因:".$messageSubject['message'];
            //操作类型
            $value['type'] = $messageSubject['type'];
            $value['module'] = 'abnormal';
            $value['create_time'] = date("Y-m-d H:i:s",time());
            $value['param'] = $value['charge_against_number'];
            $value['recv_name'] = $value['create_user_name'];
            $value['apply_id'] = $value['create_user_id'];
        }
        $this->pushSwooleData($userData);
    }

    public function childreninvoiceMessage($userData,$messageSubject){
        foreach($userData as $key=>&$value){
            //提示消息拼接
            $value['pushMessage'] = "子发票清单号:".$value['children_invoice_number']."被驳回,驳回人:".$messageSubject['user'].",原因:".$messageSubject['message'];
            //操作类型
            $value['type'] = $messageSubject['type'];
            $value['module'] = 'declare';
            $value['create_time'] = date("Y-m-d H:i:s",time());
            $value['param'] = $value['children_invoice_number'];
            $value['recv_name'] = $value['tovoid_user'];
            $value['apply_id'] = '';

        }
        $this->pushSwooleData($userData);

    }

    protected  function invoiceMessage($userData,$messageSubject){

        foreach($userData as $key=>&$value){
            //提示消息拼接 c.submit_user,c.invoice_number
            $value['pushMessage'] = "发票清单号:".$value['invoice_number']."被驳回,驳回人:".$messageSubject['user'].",原因:".$messageSubject['message'];
            //操作类型
            $value['type'] = $messageSubject['type'];
            $value['module'] = 'declare';
            $value['create_time'] = date("Y-m-d H:i:s",time());
            $value['param'] = $value['invoice_number'];
            $value['recv_name'] = $value['submit_user'];
            $value['apply_id'] = '';

        }
        $this->pushSwooleData($userData);
    }


    protected  function supplierMessage($userData,$messageSubject){

        foreach($userData as $key=>&$value){
            //提示消息拼接
            $value['pushMessage'] = "供应商修改信息,申请编码:".$value['apply_no']."被驳回,驳回人:".$messageSubject['user'].",原因:".$messageSubject['message'];
            //操作类型
            $value['type'] = $messageSubject['type'];
            $value['module'] = 'supplier';
            $value['create_time'] = date("Y-m-d H:i:s",time());
            $value['param'] = $value['apply_no'];
            $value['recv_name'] = $value['create_user_name'];
            $value['apply_id'] = $value['create_user_id'];

        }
        $this->pushSwooleData($userData);
    }

      //上传拜访报告通知消息
    protected  function visitReportMessage($userData,$messageSubject){

        foreach($userData as $key=>&$value){
            //提示消息拼接
            $value['pushMessage'] = "供应商:".$value['supplier_name']."的外出拜访报告已生成,可点击查看";
            //操作类型
            $value['type'] = $messageSubject['type'];
            $value['module'] = 'visit_report';
            $value['create_time'] = date("Y-m-d H:i:s",time());
            $value['param'] = $value['apply_no'];
            $value['recv_name'] = $value['apply_person'];
            $value['apply_id'] = $value['apply_id'];

        }
        $this->pushSwooleData($userData);
    }


    //拜访报告审核通过消息通知
    protected  function visitAuditMessage($userData,$messageSubject){

        foreach($userData as $key=>&$value){
            //提示消息拼接
            $value['pushMessage'] = $messageSubject['message'];
            //操作类型
            $value['type'] = $messageSubject['type'];
            $value['module'] = 'visit_audit';
            $value['create_time'] = date("Y-m-d H:i:s",time());
            $value['param'] = $value['apply_no'];
            $value['recv_name'] = $value['apply_person'];
            $value['apply_id'] = $value['apply_id'];

        }
        $this->pushSwooleData($userData);
    }

    protected  function pushSwooleData($userData){

        // 消息拼接完毕，PUSH 到SWOOLE 服务器

        try{
            if (!empty($userData)) {
                $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
                if ($client->connect(SWOOLE_SERVER, 2026, 0.5)) {
                    $client->send(json_encode($userData));
                    $client->recv();
                    $client->close();
                }
            } else {
                return False;
            }
        }catch ( Exception $exp ){

            echo $exp->getMessage();
        }
    }

    /**
     * 接受消息
     * @param $subject  主体，采购系统那个模块消息

     *        $messageSubject array  消息主体  包含 $messageSubject['data']: 单号
     *                                             $messageSubject['message']: 消息提示
     **/

    public function AcceptMessage($subject,$messageSubject = array()){
       if( is_string($subject) ){

           // 验货相关
           if( $subject == 'check_product'){
               // 如果是采购单模块消息
               $this->CheckProductMessage($messageSubject,$messageSubject);
               return false;
           }

           // 查找消息接收人信息
           $userData = $this->searchPushUserId($messageSubject['data'],$subject);
           if(!empty($userData) || $userData == true){

               // 组装相关消息格式
               if( $subject == 'purchase'){

                   // 如果是采购单模块消息
                   $this->purchaseassembleMessage($userData,$messageSubject);
               }

               // 组装相关消息格式
               if( $subject == 'ali_order'){
                   $this->AliOrderMessage($userData,$messageSubject);
               }

               if( $subject == 'product'){

                   // 如果是商品管理模块消息
                   $this->productMessage($userData,$messageSubject);
               }

               if( $subject == 'money'){
                   // 请款单模块消息出来
                   $this->moneyMessage($userData,$messageSubject);
               }

               if( $subject == 'determine'){
                   // 请款单模块消息出来
                   $this->determineMessage($userData,$messageSubject);
               }

               if( $subject == 'report'){
                   // 请款单模块消息出来
                   $this->reportMessage($userData,$messageSubject);
               }

               if( $subject == 'scree'){

                   // SKU 屏蔽模块消息
                   $this->screeMessage($userData,$messageSubject);
               }

               if( $subject == 'declare'){

                   // SKU 屏蔽模块消息
                   $this->invoiceMessage($userData,$messageSubject);
               }

               if( $subject == 'childreninvoice'){

                   $this->childreninvoiceMessage($userData,$messageSubject);
               }

               if( $subject == 'supplier'){

                   $this->supplierMessage($userData,$messageSubject);
               }
	       if( $subject == 'visit_report'){
                   $this->visitReportMessage($userData,$messageSubject);
               }
               if( $subject == 'visit_audit'){

                   $this->visitAuditMessage($userData,$messageSubject);
               }
           }
       }
    }

    /**
     * 消息推送到SWOOLE 服务,PULL SWOOLE 的过程中如果出现BUG或者异常，并不会抛出
     * @param  $pushUserId  int  目标用户ID
     *         $message     string 消息
     *         $subject     string  主体
     * @author:luxu
     * @time:2020/11/3
     **/

    public function pushMessageSwoole($pushUserId,$message,$subject){

       try{


       }catch ( Exception $exp ){


       }
    }
}