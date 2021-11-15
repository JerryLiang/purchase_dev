<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Datalogs_api extends MY_API_Controller {


    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 计划系统获取采购系统备份数据表状态
     * @param
     * @author:luxu
     * @time:2021年1月29
     **/

    public function getBackData(){

        $data_json = file_get_contents('php://input');
        $datas = json_decode(stripslashes($data_json),True);

//        $sql = "SHOW TABLES LIKE 'pur_backup_logs'";
//        $result = $this->db->query($sql)->result_array();
//        print_r($result);die();

        if(!empty($datas)){

           $resultData = [];

           foreach($datas as $key=>&$value){

               $tablesData =  $value['tables']."_".date("d",strtotime($value['date']));
               $exists =  "SHOW TABLES LIKE '{$tablesData}'";
               $tableMessage = $this->db->query($exists)->row_array();
               if( empty($tableMessage)){
                   $value['tables'] = $tablesData;
                   $value['rcount'] = 0;
                   $value['is_succes_ch'] = "备份失败";
                   $value['is_success'] = 0;
                   $resultData[] = $value;
               }else {

                   $sql = " SELECT log.table,log.date,is_succes,rcount FROM pur_backup_log as log WHERE 1=1 ";
                   if( isset($value['tables']) && !empty($value['tables'])){

                       $sql .= "  AND log.table='".$tablesData."'";
                   }

                   if( isset($value['date']) && !empty($value['date'])){

                       $sql .= " AND log.date='".$value['date']."'";
                   }

                   $result = $this->db->query($sql)->result_array();

                   if(!empty($result)){
                       foreach( $result as &$tables){

                           if( $tables['is_succes'] == 1){

                               $tables['is_succes_ch'] = "备份成功";
                           }else{
                               $tables['is_succes_ch'] = "备份失败";
                           }
                           $resultData[] = $tables;
                       }
                   }
               }

           }



            $this->success_json($resultData);
        }
    }
}