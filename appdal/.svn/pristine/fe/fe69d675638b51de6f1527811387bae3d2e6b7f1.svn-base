<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Product_sale_api extends MY_API_Controller {

    private $bi_url_test = "http://47.106.223.247:8800/bi/dwh/aweek_sale_volume/"; // BI 获取SKU 近7天销售数量地址(测试地址)
    private $bi_url_product = "http://47.106.223.247:8801/bi/dwh/aweek_sale_volume/"; // BI 获取SKU 近7天销售数量地址(生产地址)
    private $one_limit = 4000; // 默认第一次获取的数据

    public function __construct() {
        parent::__construct();
    }

    // 获取BI 访问地址
    private function getSevenSaleBiUrl() {

        if( CG_ENV == "dev" ) {

            return $this->bi_url_test;
        }

        if( CG_ENV == "prod" ) {

            return $this->bi_url_product;
        }

        return NULL;
    }

    /**
     * 发送HTTP 请求
     * @param :   $url    string   URL
     * @return    array    HTTP 返回信息
     **/
    private function sendHttp( $url,$params = array() ) {

        $url = "{$url}?" . http_build_query ( $params );
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 60 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $params );
        $result = curl_exec ( $ch );
        curl_close ( $ch );

        return $result;
    }

    /**
     * 更新SKU 销量信息
     * @param :  更新数据
     * @return BOOL
     **/
    private function update_sku_sale( $data ) {

        if( !empty($data) ) {
            $new_result_array = array_chunk( $data,2000,True);
            foreach( $new_result_array as $key=>$value ) {


//                $pid = pcntl_fork(); // FORK 字进程用于记录日志信息
//                echo $pid;
//                if( $pid ==0 ) {
                    $logs = array(

                        "skus" => json_encode($value),
                        "create_time" => date("Y-m-d H:i:s")
                    );
                    $this->db->insert("product_sale_log",$logs );
                    $this->db->update_batch('product', $value, "sku");
                    // 子进程退出
//                    exit;

//                }else {


//                    pcntl_wait($status);// 等待子进程退出
//                }
            }

        }
    }

    /**
      * 获取7天销售数据
     **/
    public function getSenvenSaleData() {
        ini_set('max_execution_time','100');
        $sendUrl = $this->getSevenSaleBiUrl();
        // 第一次获取数据
        $result = $this->sendHttp($sendUrl,array('page'=>1,'count'=>$this->one_limit));
        if( !empty($result) ) {

            $result = json_decode( $result,True);
            $total_all = (isset( $result['total_count']) && !empty($result['total_count']))? $result['total_count']:0;
            if( $total_all >0 ) {
                // 插入SKU销售数据
                //$this->update_sku_sale($result['data_list']);
                $total_page = ceil($result['total_count']/$this->one_limit);
                for( $i=1;$i<$total_page;++$i) {

                   $result = $this->sendHttp($sendUrl,array('page'=>$i,'count'=>$this->one_limit));
                   if( !empty($result) ) {
                       $result = json_decode( $result,True);
                       $this->update_sku_sale($result['data_list']);
                   }
                }
            }
        }
    }



}