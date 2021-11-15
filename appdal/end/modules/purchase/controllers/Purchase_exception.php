<?php
/**
 * 接收仓库异常数据
 * User: Jaden
 * Date: 2019/2/12 0023
 * Time: 16:20
 */

class Purchase_exception extends MY_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('purchase_abnomal_model','abnomal');
        $this->load->model('purchase_abnormals_model','abnormals');
    }

    /**
     * 接收到货异常入库
     * /purchase/purchase_exception/createabnomal
     * @author Jaden 2019-1-17
    */

    public function createabnomal(){
        $datas = $this->input->post('deliveryAbnormal'); 
        if(isset($datas) && !empty($datas))
        {
            $datas = json_decode($datas,true);
            foreach ($datas as $k => $v) {
                $update_datas = array();
                $update_datas['express_no'] = $v['express_no'];
                $update_datas['package_qty'] = $v['package_qty'];
                $update_datas['send_addr'] = $v['send_addr'];
                $update_datas['send_name'] = $v['send_name'];
                $update_datas['status'] = $v['status']==4?$v['status']:3;
                $update_datas['is_del'] = $v['is_del'];
                $update_datas['wms_id'] = $v['wms_id'];
                $update_datas['img'] = $v['img'];
                $update_datas['note'] = $v['note'];
                $update_datas['buyer'] = isset($v['buyer'])?$v['buyer']:'';
                $update_datas['create_user'] = $v['create_user'];
                $update_datas['create_time'] = $v['create_time'];
                //$update_datas['is_push'] = $v['is_push']==1?$v['is_push']:0;
                $update_datas['update_time'] = !empty($v['update_time'])?$v['update_time']:date('Y-m-d H:i:s');
                //如果仓库ID存在就更新
                $wms_id_one = $this->abnomal->getBywhere('wms_id="'.$v['wms_id'].'"');
                if(!empty($wms_id_one)){
                    $wms_id_where = 'wms_id="'.$v['wms_id'].'"';
                    $this->abnomal->updateBywhere($wms_id_where,$update_datas);
                    $data['success_list'][$k]['wms_id']                 = $wms_id_one['wms_id'];
                    $data['failure_list'][]                             = '';
                }else{
                    //如果快递单号存在就更新
                    $express_no_one = $this->abnomal->getBywhere('express_no="'.$v['express_no'].'"');
                    if(!empty($express_no_one))
                    {
                        $express_no_where = 'express_no="'.$v['express_no'].'"';
                        $this->abnomal->updateBywhere($express_no_where,$update_datas);
                        $data['success_list'][$k]['express_no']                 = $express_no_one['express_no'];
                        $data['failure_list'][]                             ='';
                    } else{
                        $this->abnomal->insert_data($update_datas);
                        $data['success_list'][$k]['wms_id']                 = $v['wms_id'];
                        $data['failure_list'][]                             = '';
                        $datas = [
                            'title'=>'快递单'.$v['express_no'].'出现了PO异常了,请及时处理',
                            'content'=>'快递单'.$v['express_no'].'出现了PO异常了,请及时处理',
                            'pur_number'=>$v['express_no'],
                            'type'=>'3',
                        ];
                        $this->abnormals->insert_data($datas);
                    }

                }
            }

            echo json_encode($data);
        } else {
            return '没有任何的数据过来！';
        }
    }

}