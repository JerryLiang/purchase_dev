<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Upload_receipt_model extends Api_base_model{
    
    public $image_url; //图片接口地址
    public $file_url;
    public function __construct() { 
        parent::__construct();
        $this->config->load('url_img', FALSE, TRUE);
        $redis=new Rediss();
       //调用OA需求获取access_taoken
       $access_taken =$redis->getData('ACCESS_TOKEN');
       if(empty($access_taken)) {
            $url=$this->config->item('access_token');
            $result= getCurlData($url, '','post','',TRUE);
            $result = json_decode($result,true);
            $this->rediss->setData('ACCESS_TOKEN',$result['access_token'],$result['expires_in']); //10分钟有效期
            $access_taken=$result['access_token'];
       }
        $this->image_url=$this->config->item('url_img')."?access_token=".$access_taken;
        $this->file_url = $this->config->item('file_img')."?access_token=".$access_taken;
    }
    
    
    /**
     * 图片上传接口
     * @param  $path 图片本地路径
     * @author harvin 2019-4-2
     */
    public function upload_picture($path,$fileName=''){
        $url=$this->image_url;
        $curlPost = [
                'system' => 'PURCHASE',
                'path' => 'IMAGES',
            ];
         $curl = curl_init();
        if (class_exists('\CURLFile')) {
            curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
            $data = array('file' => new \CURLFile(realpath($path),$mime='',$fileName)); //>=5.5
        } else {
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
            }
            $data = array('file' => '@' . realpath($path)); //<=5.5
        }
        $data_list= array_merge($data,$curlPost);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_list);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, "TEST");
        $result = curl_exec($curl);
        $error = curl_error($curl);
        return $result;
    }



    /**
     * 上传图片|文件到 Fast DFS 服务器
     * @param string $api_image 接口类型（image 或 file）
     * @param string $path      本地文件路径
     * @param bool   $to_del    是否上传成功后自动删除文件
     * @return array
     */
    public function doUploadFastDfs($api_image = 'image', $path, $to_del = true){
        $return = ['code' => 200, 'data' => '', 'message' => ''];

        // 文件拓展名称
        $arr = pathinfo($path);
        $ext = strtolower($arr['extension']);

        // 接口路径 与 参数
        if($api_image === 'image' and in_array($ext, ['gif', 'jpg', 'png', 'jpeg'])){// 上传图片格式文件
            $curlPost = ['system' => 'PURCHASE', 'path' => 'IMAGES'];
            $url_path = $this->image_url;
        }else{
            $curlPost = [];
            $url_path = $this->file_url;// 该接口支持批量上传，但是这个只实现单个上传
        }
        $results = $this->curldata($url_path, $path, $curlPost);
        return $results;
    }

    public function curldata($url, $path, $curlPost){
        $curl = curl_init();
        if(class_exists('\CURLFile')){
            curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
            $data = array('file' => new \CURLFile(realpath($path))); //>=5.5
        }else{
            if(defined('CURLOPT_SAFE_UPLOAD')){
                curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
            }
            $data = array('file' => '@'.realpath($path)); //<=5.5
        }
        $data_list = array_merge($data, $curlPost);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_list);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, "TEST");
        $result = curl_exec($curl);
        $error  = curl_error($curl);
        //file_put_contents("data.txt", $error);

        return $result;
    }
    
    
    
    
    
    
}
