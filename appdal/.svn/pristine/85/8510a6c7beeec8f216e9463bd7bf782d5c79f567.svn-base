<?php

/**
 * Created by PhpStorm.
 * 供应商 联系方式
 * User: Jackson
 * Date: 2019/01/09 0029 11:50
 */
class Supplier_contact_model extends Purchase_model
{
    protected $table_name = 'supplier_contact';// 供应商联系方式表
    protected $table_supplier_name = 'pur_supplier';// 供应商表


    /**
     * 返回表名
     * MY_Model 中的 filterNotExistFields() 方法需要
     * @return string
     */
    public function table_nameName()
    {
        return $this->table_name;
    }

    /**
     * 获取 供应商 一个联系人
     * @author Jolon
     * @param string $supplier_code 供应商编码
     * @return array|bool
     */
    public function get_contact_one($supplier_code)
    {
        if (empty($supplier_code)) return false;

        $where = ['supplier_code' => $supplier_code];

        $row = $this->purchase_db->where($where)
            ->get($this->table_name)
            ->row_array();

        return $row;
    }


    /**
     * 获取 供应商所有联系人
     * @author Jolon
     * @param string $supplier_code 供应商编码
     * @return array|bool
     */
    public function get_contact_list($supplier_code)
    {
        if (empty($supplier_code)) return false;

        $list = $this->purchase_db->where('supplier_code', $supplier_code)
            ->get($this->table_name)
            ->result_array();

        return $list;
    }
    /**
     * 更新供应商映射关系
     * @author harvin 2019-5-21
     * @param type $productid
     * @param type $procurementid
     * @return boolean
     */
    public function update_contact_id($productid, $procurementid) {
        $resulet = $this->purchase_db
                ->where('id', $procurementid)
                ->update($this->table_name, ['contact_id' => $productid]);
        if (empty($resulet)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @desc 获取供应商-联系方式
     * @author Jackson
     * @Date 2019-01-21 17:01:00
     * @return array()
     **/
    public function get_supplier_contact(array $params)
    {

        $this->table_name = $this->table_supplier_name;
        //搜索条件
        $condition = [];
        //查询字段
        $fields = 'id,supplier_code,contact_person,corporate,contact_number,mobile,qq,micro_letter,want_want,email,cn_address,en_address';

        //排序
        $orderBy = '';
        if (isset($params['supplier_code'])) {
            $condition[$this->table_supplier_name.'.supplier_code'] = $params['supplier_code'];
            //查询字段
            $fields = 'b.id,'.$this->table_supplier_name.'.supplier_code,contact_person,contact_number,mobile,
            contact_fax,cn_address,en_address,contact_zip,qq,micro_letter,want_want,skype,sex,email,'
                .$this->table_supplier_name.'.business_scope,'
                .$this->table_supplier_name.'.first_cooperation_time,'
                .$this->table_supplier_name.'.cooperation_price,'
                .$this->table_supplier_name.'.reconciliation_agent,'
                .$this->table_supplier_name.'.agent_mobile,'
                .$this->table_supplier_name.'.purchase_time'           ;

            //联表查询
            $joinCondition = array(
                array(
                    "pur_supplier_contact b", "{$this->table_name}.supplier_code=b.supplier_code", 'INNER'
                )
            );
            $result = $this->getDataListByJoin($condition, $fields, $orderBy,0,1,'',$joinCondition);

        } else {
            $result = $this->getDataByCondition($condition, $fields, $orderBy);
        }

        return array(
            'list' => $result,
        );
    }

    /**
     * @desc 更新数据
     * @author Jackson
     * @parame array $parames  参数
     * @parame string $supplier_code  供应商代码
     * @Date 2019-01-22 17:01:00
     * @return array()
     **/
    public function update_supplier_contact(array $parames,$supplier_code='')
    {
        //更新条件
        $condition = [];
        if (!empty($parames)) {

            //更新数据
            $data = $parames;
            $condition['supplier_code'] = $data['supplier_code'];
            if(isset($data['contact_id'])){
                $condition['contact_id'] = $data['contact_id'];
            }elseif(isset($data['id'])){
                $condition['id'] = $data['id'];
            }
 

            //查检数据是否存在，存在更新，反之插入
            $checkData = $this->checkDataExsit($condition);
            unset($data['id']);



            $data['modify_user_name'] = '';//用户名
            analyzeUserInfo($data);
            $data['modify_time'] = date("Y-m-d H:i:s");

            //过虑不存在字段
            $data = $this->filterNotExistFields($data);
          
            if (empty($checkData)) {
                $result = $this->insert($data);

                //记录日志
                if ($result) {
                    // 插入表改变日志信息
                    tableChangeLogInsert(
                        ['record_number' => $this->getLastInsertID(),
                            'table_name' => 'pur_' . $this->table_name,
                            'change_type' => 1,
                            'content' => $data
                        ]);
                }

                return $result ? array(true, "插入成功") : array(false, "操作失败:" . $this->getWriteDBError());
            } else {  
                //获取更新前的数据
                $updateBefore = $this->getDataByCondition($condition);

                $result = $this->update($data, $condition);
                //记录日志
                if ($result) {
                    $ids = array_column($updateBefore, 'id');
                    if (is_array($ids)) {

                        //删除不必要字段
                        $delFilter = array('create_time', 'modify_time', 'modify_user_name');
                        foreach ($parames as $key => $val) {
                            if (in_array($key, $delFilter)) {
                                unset($parames[$key]);
                            }
                        }
      
                        //解析更新前后数据
                        $changDatas = $this->checkChangData($updateBefore, $parames, $delFilter);
                      
                        if (!empty($changDatas)) {
                            foreach ($ids as $key => $_id) {

                                operatorLogInsert(
                                    [
                                        'type' => 'pur_' . $this->table_name,
                                        'content' => '供应商联系人信息更新',
                                        'detail' => $changDatas[$_id],
                                        'ext' => $supplier_code,
                                    ]);

                                tableChangeLogInsert(
                                    ['record_number' => $_id,
                                        'table_name' => 'pur_' . $this->table_name,
                                        'change_type' => 2,
                                        'change_content' => $changDatas[$_id],
                                    ]);
                            }
                        }
                    }

                }
              return array(true, "更新成功");
                //return $this->getAffectedRows() > 0 ? array(true, "更新成功") : array(false, "操作失败:" . $this->getWriteDBError());
            }
        }
        return false;
    }

    /**
     * 根据供应商名称查询 联系信息
     * @author Manson
     * @param $supplier_code
     * @return array
     */
    public function get_supplier_contact_by_supplier($supplier_code)
    {
        $supplier_contact = [];
        $result = $this->purchase_db->select('id, name')
            ->from('pur_province')
            ->get()->result_array();
        $province_map = empty($result)?[]:array_column($result,'name','id');

        $result = $this->purchase_db->select('a.ship_address,a.ship_area,a.ship_province,a.ship_address complete_address,a.ship_province province,a.ship_city,b.contact_person, b.contact_number, b.mobile')
            ->from('supplier a')
            ->join($this->table_name.' b','a.supplier_code = b.supplier_code','left')
            ->where('a.supplier_code',$supplier_code)
            ->order_by('b.id desc')
            ->get()->row_array();

        if ($result){
            $supplier_contact = [
                'province' => $result['province'],
                'province_text' => $province_map[$result['province']]??'',
                'complete_address' => $result['complete_address'],
                'contact_person' => $result['contact_person'],
                'contact_number' => $result['contact_number'],
                'city' => $result['ship_city'],
                'area' => $result['ship_area'],
                'mobile' => $result['mobile']
            ];
        }
        return $supplier_contact;
    }

    /**
     * 获取相近的供应商信息
     * @params $searchWhere   array   获取条件
     * @author:luxu
     * @return array
     **/
    public function getCloseSupplierData($searchWhere,$supplierCode)
    {
        $status_list = getCooperationStatus();


        if( empty($searchWhere) ){

            return NULL;
        }

        $query = $this->purchase_db->from($this->table_name." AS const")->join("supplier","supplier.supplier_code=const.supplier_code")->where("const.supplier_code!=",$supplierCode);
        $searchKeys = array_keys($searchWhere);
        $where = NULL;
        foreach($searchWhere as $key=>$value){

            $value = array_map( function($data){

                return sprintf("'%s'",$data);
            },$value);
            if( $searchKeys[0] == $key){
                $where .= " (".$key." in (". implode(",",$value).")";
            }else{
                $where .= " OR ".$key ." IN (".implode(",",$value).")";
            }
        }
        $where .= ")";
        $result = $query->where($where)->select("supplier.status,supplier.supplier_code,supplier.supplier_name,const.micro_letter,const.qq,const.want_want,const.email,const.mobile,const.contact_number")->get()->result_array();



        if( !empty($result)) {




            foreach( $result as $key=>$value){
                $result[$key]['status'] = $status_list[$value['status']]??'';


                foreach ($searchWhere as $search_key => $search_value) {
                    if ($search_key == 'micro_letter') {
                        if (in_array(trim($value['micro_letter']),$search_value)){
                            $result[$key]['micro_letter_like'] = 1;

                        } else {
                            $result[$key]['micro_letter_like'] = 0;


                        }

                    }

                    if ($search_key == 'qq') {
                        if (in_array(trim($value['qq']),$search_value)){
                            $result[$key]['qq_like'] = 1;

                        } else {
                            $result[$key]['qq_like'] = 0;


                        }

                    }
                    if ($search_key == 'want_want') {
                        if (in_array(trim($value['want_want']),$search_value)){
                            $result[$key]['want_want_like'] = 1;

                        } else {
                            $result[$key]['want_want_like'] = 0;

                        }

                    }

                    if ($search_key == 'email') {
                        if (in_array(trim($value['email']),$search_value)){
                            $result[$key]['email_like'] = 1;

                        } else {
                            $result[$key]['email_like'] = 0;


                        }

                    }

                    if ($search_key == 'mobile') {
                        if (in_array(trim($value['mobile']),$search_value)){
                            $result[$key]['mobile_like'] = 1;

                        } else {
                            $result[$key]['mobile_like'] = 0;


                        }

                    }

                    if ($search_key == 'contact_number') {
                        if (in_array(trim($value['contact_number']),$search_value)){
                            $result[$key]['contact_number_like'] = 1;

                        } else {
                            $result[$key]['contact_number_like'] = 0;


                        }

                    }



                    //$result[$key]['status']


                }


                if (!isset($searchWhere['micro_letter'])) {
                    $result[$key]['micro_letter_like'] = 0;

                }
                if (!isset($searchWhere['qq'])) {
                    $result[$key]['qq_like'] = 0;

                }
                if (!isset($searchWhere['email'])) {
                    $result[$key]['email_like'] = 0;

                }
                if (!isset($searchWhere['want_want'])) {
                    $result[$key]['want_want_like'] = 0;

                }
                if (!isset($searchWhere['mobile'])) {
                    $result[$key]['mobile_like'] = 0;

                }

                if (!isset($searchWhere['contact_number'])) {
                    $result[$key]['contact_number_like'] = 0;

                }



/*
                $supplierMess =  array(
                    'supplier_code' => $value['supplier_code'],
                    'status' => (in_array($value['status'],[2]))?2:1
                );
                $returnData['micro_letter'][$value['micro_letter']][] = $supplierMess;
                $returnData['qq'][$value['qq']][] = $supplierMess;
                $returnData['email'][$value['email']][] =  $supplierMess;
                $returnData['want_want'][$value['want_want']][] =  $supplierMess;
                $returnData['mobile'][$value['mobile']][] =  $supplierMess;*/

            }
            return $result;
        }

        //还需要校验

        return NULL;
    }



    public function getClosePaymentData($searchWhere,$supplierCode)
    {
        $status_list = getCooperationStatus();


        if( empty($searchWhere) ){

            return NULL;
        }

        $query = $this->purchase_db->from('supplier_payment_info'." AS const")->join("supplier","supplier.supplier_code=const.supplier_code")->where("const.supplier_code!=",$supplierCode)->where("const.is_tax",0)->where("const.is_del",0)->where_in("const.purchase_type_id",[1,2]);
        $searchKeys = array_keys($searchWhere);
        $where = NULL;
        foreach($searchWhere as $key=>$value){

            $value = array_map( function($data){

                return sprintf("'%s'",$data);
            },$value);
            if( $searchKeys[0] == $key){
                $where .= " (".$key." in (". implode(",",$value).")";
            }else{
                $where .= " OR ".$key ." IN (".implode(",",$value).")";
            }
        }
        $where .= ")";
        $result = $query->where($where)->select("supplier.status,supplier.supplier_code,supplier.supplier_name,const.phone_number,const.id_number,const.account,const.phone_number,const.purchase_type_id")->get()->result_array();


        $payment_info_list = [];

        $return_result = [];








        if( !empty($result)) {


            foreach( $result as $key=>$value){
                $payment_info_list[$value['supplier_code']][$value['purchase_type_id']] = $value;

            }
            foreach ($searchWhere as $search_key => $search_value) {
                foreach ($payment_info_list as $supplier_code =>$payment_data) {
                    foreach ($payment_data as $purchase_type_id=>$data) {

                        if ($search_key == 'phone_number') {
                            if (in_array($data['phone_number'], $search_value)) {
                                $payment_info_list[$supplier_code][$purchase_type_id]['phone_number_like'] = 1;

                            } else {
                                $payment_info_list[$supplier_code][$purchase_type_id]['phone_number_like'] = 0;


                            }
                        }

                        if ($search_key == 'id_number') {
                            if (in_array($data['id_number'], $search_value)) {
                                $payment_info_list[$supplier_code][$purchase_type_id]['id_number_like'] = 1;

                            } else {
                                $payment_info_list[$supplier_code][$purchase_type_id]['id_number_like'] = 0;


                            }
                        }

                        if ($search_key == 'account') {
                            if (in_array($data['account'], $search_value)) {
                                $payment_info_list[$supplier_code][$purchase_type_id]['account_like'] = 1;

                            } else {
                                $payment_info_list[$supplier_code][$purchase_type_id]['account_like'] = 0;


                            }
                        }
                    }





                }


            }

            //开始将数组组装
            $return_supplier_code_arr = array_keys($payment_info_list);






            foreach ($return_supplier_code_arr as $return_supplier_code) {



                $status_value=!empty($payment_info_list[$return_supplier_code][1]['status'])?$payment_info_list[$return_supplier_code][1]['status']:(!empty($payment_info_list[$return_supplier_code][2]['status'])?$payment_info_list[$return_supplier_code][2]['status']:'');

                $return_result[] = [

                    'supplier_name'=>!empty($payment_info_list[$return_supplier_code][1]['supplier_name'])?$payment_info_list[$return_supplier_code][1]['supplier_name']:(!empty($payment_info_list[$return_supplier_code][2]['supplier_name'])?$payment_info_list[$return_supplier_code][2]['supplier_name']:''),
                    'supplier_code'=>!empty($payment_info_list[$return_supplier_code][1]['supplier_code'])?$payment_info_list[$return_supplier_code][1]['supplier_code']:(!empty($payment_info_list[$return_supplier_code][2]['supplier_code'])?$payment_info_list[$return_supplier_code][2]['supplier_code']:''),
                    'status'=>$status_list[$status_value]??'',
                    'domestic_mobile'=>!empty($payment_info_list[$return_supplier_code][1]['phone_number'])?$payment_info_list[$return_supplier_code][1]['phone_number']:'',
                    'domestic_mobile_like'=>!empty($payment_info_list[$return_supplier_code][1]['phone_number_like'])?$payment_info_list[$return_supplier_code][1]['phone_number']:0,
                    'domestic_card'=>!empty($payment_info_list[$return_supplier_code][1]['id_number'])?$payment_info_list[$return_supplier_code][1]['id_number']:'',
                    'domestic_card_like'=>!empty($payment_info_list[$return_supplier_code][1]['id_number_like'])?$payment_info_list[$return_supplier_code][1]['id_number_like']:0,
                    'domestic_account'=>!empty($payment_info_list[$return_supplier_code][1]['account'])?$payment_info_list[$return_supplier_code][1]['account']:'',
                    'domestic_account_like'=>!empty($payment_info_list[$return_supplier_code][1]['account_like'])?$payment_info_list[$return_supplier_code][1]['account_like']:0,


                    'oversea_mobile'=>!empty($payment_info_list[$return_supplier_code][2]['phone_number'])?$payment_info_list[$return_supplier_code][2]['phone_number']:'',
                    'oversea_mobile_like'=>!empty($payment_info_list[$return_supplier_code][2]['phone_number_like'])?$payment_info_list[$return_supplier_code][2]['phone_number']:0,
                    'oversea_card'=>!empty($payment_info_list[$return_supplier_code][2]['id_number'])?$payment_info_list[$return_supplier_code][2]['id_number']:'',
                    'oversea_card_like'=>!empty($payment_info_list[$return_supplier_code][2]['id_number_like'])?$payment_info_list[$return_supplier_code][2]['id_number_like']:0,
                    'oversea_account'=>!empty($payment_info_list[$return_supplier_code][2]['account'])?$payment_info_list[$return_supplier_code][2]['account']:'',
                    'oversea_account_like'=>!empty($payment_info_list[$return_supplier_code][2]['account_like'])?$payment_info_list[$return_supplier_code][2]['account_like']:0,





                ];



            }




        }
        return $return_result;


    }


}