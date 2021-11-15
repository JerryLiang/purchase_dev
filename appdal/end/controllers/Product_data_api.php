<?php

include_once APPPATH ."core/MY_Oauth_Controller.php";

/**
 * 功能:获取SKU 数据接口，加有权限验证功能
 * author:luxu
 * @time : 2021年6月25号
 **/
class Product_data_api extends MY_Oauth_Controller{

    public  function __construct()
    {

        parent::__construct();
    }

    /**
     * 需求：37285 对接物流系统，根据ＳＫＵ获取开发成本 、 平均采购成本（不含运费） 、最新采购成本
     * @METHOD  JSON  POST
     * @AUTHOR:luxu
     * @TIME: 2021年6月25号
     **/

    public function getSkuPrice(){

        try{
            $clientDatas = json_decode($this->_requestData,True);
            if(empty($clientDatas) || count($clientDatas)>100){

                throw new Exception("传入SKU 不能为空并且查询SKU上限为100个");
            }

            $result = $this->db->from("product")->where_in("sku",$clientDatas)->select("sku,purchase_price,product_cost")
                ->get()->result_array();
            if(!empty($result)){

                $avgResults = $this->db->from("purchase_avg_fuse")->where_in("sku",$clientDatas)->select("sku,avg_purchase_price")
                    ->get()->result_array();
                $avgResults = array_column($avgResults,NULL,"sku");
                foreach($result as $key=>$value){

                    $result[$key]['avg_purchase_price'] = isset($avgResults[$value['sku']])?$avgResults[$value['sku']]['avg_purchase_price']:0;
                }

                $this->success_json($this->_OK,$result);
            }else {

                throw new Exception("查询不到SKU数据信息");
            }


        }catch ( Exception $exception){

            $this->error_json($this->_BadRequest,$exception->getMessage());
        }

    }

    /**
        37936 提供接口给ＤＳＳ系统获取ＦＢＡ备货的ＳＫＵ #2
        需求描述
        需求背景：ＤＳＳ系统需要知道ＦＢＡ备货的有哪些ＳＫＵ，然后通知品控针对这些ＳＫＵ在入库之前，安排进行全检
        需求描述：计划系统推送到采购系统的需求单明细中，fba_purchase_qty 的数量＞0的，若这个需求单下单成功，采购单状态变为等待到货的时候，
        ＤＳＳ系统安排定时获取满足以上条件的所有ＳＫＵ
     **/
    public function getWatingDelivery(){

        $clientDatas = json_decode($this->_requestData,True);
        try{
            $clientDatas = json_decode($this->_requestData,True);
            if(empty($clientDatas)){

                throw new Exception("传入参数");
            }

            $query = $this->db->from("purchase_sku_waiting_delivery");
            if(isset($clientDatas['start']) && !empty($clientDatas['start'])){

                $query->where("date(wating_arrval_time)>=",$clientDatas['start']);
            }

            if(isset($clientDatas['end']) && !empty($clientDatas['end'])){

                $query->where("date(wating_arrval_time)<=",$clientDatas['end']);
            }

            $page = (!isset($clientDatas['page']) || empty($clientDatas['page']))?0:$clientDatas['page']-1;

            $limit = (!isset($clientDatas['limit']) || empty($clientDatas['limit']))?1:$clientDatas['limit'];

            $totalQuery = clone $query;
            $result = $query->limit($limit,$page*$limit)->select("sku,fba_purchase_qty")->get()->result_array();
            $total = $totalQuery->count_all_results();

            $results = [

                'list' => $result,
                'total' => $total
            ];

            $this->success_json($this->_OK,$results);


        }catch ( Exception $exp ){

            $this->error_json($this->_BadRequest,$exp->getMessage());

        }

    }

    /**
     * 37994 装箱“导入模板”数据可修改 #
     * @author:luxu
     * @time:2021年7月19
     **/

    public function package_examine(){
        try {
            $clientDatas = json_decode($this->_requestData, True);
            if (empty($clientDatas)) {

                throw new Exception("传入参数");
            }
            $agent_id  = 193670347;
            $userNumber =  14592;
            foreach($clientDatas as $clientData_key=>$clientData_value){

                $res = $this->db->where("shipment_sn",$clientData_value['shipment_sn'])
                    ->update("shipment_container_list",['is_package_box'=>$clientData_value['is_package_box'],'message'=>$clientData_value['message']]);
                if($clientData_value['is_package_box'] == 2) {
                    $import_id = $this->db->from("shipment_container_list")->where("shipment_sn", $clientData_value['shipment_sn'])
                        ->select("create_user_id,container_sn")->get()->row_array();
                    $msg = "ID:" . $clientData_value['container_sn'] . "装箱明细导入失败，计划系统审核未通过，请重新导入。！";
                    $userNumbers = getUserNumberById($import_id['create_user_id']);
                    $url = "http://dingtalk.yibainetwork.com/personalnews/Personal_news/personalNews?agent_id=" . $agent_id . "&userNumber={$userNumbers}&msg=" . $msg;
                    getCurlData($url, '', 'GET');
                }
            }

            $this->success_json($this->_OK);

        }catch ( Exception $exp ){

            $this->error_json($this->_BadRequest,$exp->getMessage());

        }
    }



}