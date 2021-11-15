<?php

/**
 * Class Upload_image
 * 上传图片工具类文件
 * @author Jolon
 */
class Upload_image {

    /**
     * 上传 图片到 fast dfs 服务器，返回文件访问路径
     * @author Jolon
     * @param string $url 文件路径（http 远程连接）
     * @param null $dir  缓存文件的路径
     * @return array
     */
    public function push_image_to_fastdfs($url, $dir = null){
        $return = ['code' => '200', 'data' => '', 'message' => ''];
        if(empty($dir)) $dir = END_UPLOAD.'imgs';
        if(!is_dir($dir)) mkdir($dir, 0755, true);

        $array = get_headers($url, 1);
        if(preg_match('/200/', $array[0])){

            $res  = $this->getImage($url, $dir);
            $path = $res['save_path'];

            $result = $this->doUploadFastDfs('image', $path);
            if($result['code'] == 200){
                $return['data'] = $result['data'];
            }else{
                $return['code']    = $result['code'];
                $return['message'] = $result['message'];
            }

            return $return;
        }else{
            $return['code']    = 404;
            $return['message'] = "上传失败-文件抓取失败";

            return $return;
        }
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

        $access_token = getOASystemAccessToken();

        // 文件拓展名称
        $arr = pathinfo($path);
        $ext = strtolower($arr['extension']);

        // 接口路径 与 参数
        if($api_image === 'image' and in_array($ext, ['gif', 'jpg', 'png', 'jpeg'])){// 上传图片格式文件
            $curlPost = ['system' => 'PURCHASE', 'path' => 'IMAGES'];
            $url_path = PRODUCT_SYSTEM_IP."file/file/upload/image?access_token=".$access_token;
        }else{
            $curlPost = [];
            $url_path = PRODUCT_SYSTEM_IP."file/file/upload/batch?access_token=".$access_token;// 该接口支持批量上传，但是这个只实现单个上传
        }
        $results = $this->curldata($url_path, $path, $curlPost);

        $result  = json_decode($results, true);
        // 获取结果
        if($result && isset($result['code']) && $result['code'] == 1000){
            if(isset($result['data']['fullUrl'])){
                $image_url      = $result['data']['fullUrl'];
                $return['data'] = $image_url;
            }elseif(isset($result['data'][0]['fullUrl'])){
                $image_url      = $result['data'][0]['fullUrl'];
                $return['data'] = $image_url;
            }else{
                $return['code']    = 500;
                $return['message'] = isset($result['msg']) ? $result['msg'] : "上传失败";
            }
        }else{
            $return['code']    = 500;
            $return['message'] = isset($result['msg']) ? $result['msg'] : "上传失败";
        }
        if($return['code'] == 500){
            apiRequestLogInsert(
                ['record_number'    => 'push_image_to_fastdfs',
                 'record_type'      => 'push_image_to_fastdfs',
                 'api_url'          => $url_path,
                 'response_content' => $result,
                 'status'           => 0,
                ]);

            return $return;
        }else{
            if($to_del) unlink($path);// 自动删除缓存文件

            return $return;
        }

    }


    /**
     * 功能：php完美实现下载远程图片保存到本地
     * 参数：文件url,保存文件目录,保存文件名称，使用的下载方式
     * 当保存文件名称为空时则使用远程文件原来的名称
     */
    public function getImage($url, $save_dir = '', $filename = '', $type = 0){
        if(trim($url) == ''){
            return array('file_name' => '', 'save_path' => '', 'error' => 1);
        }
        if(trim($save_dir) == ''){
            $save_dir = './';
        }
        if(trim($filename) == ''){//保存文件名
            $ext = strrchr($url, '.');
//            if($ext != '.gif' && $ext != '.jpg' && $ext != '.png'){
//                return array('file_name' => '', 'save_path' => '', 'error' => 3);
//            }
            $filename = time().$ext;
        }
        if(0 !== strrpos($save_dir, '/')){
            $save_dir .= '/';
        }
        //创建保存目录
        if(!file_exists($save_dir) && !mkdir($save_dir, 0777, true)){
            return array('file_name' => '', 'save_path' => '', 'error' => 5);
        }
        //获取远程文件所采用的方法
        if($type){
            $ch      = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $img = curl_exec($ch);
            curl_close($ch);
        }else{
            ob_start();
            readfile($url);
            $img = ob_get_contents();
            ob_end_clean();
        }
        //$size=strlen($img);
        //文件大小
        $fp2 = @fopen($save_dir.$filename, 'a');
        fwrite($fp2, $img);
        fclose($fp2);
        unset($img, $url);

        return array('file_name' => $filename, 'save_path' => $save_dir.$filename, 'error' => 0);
    }

    public function upload_picture($path,$fileName=''){
        $access_token = getOASystemAccessToken();
        $url= $url_path = PRODUCT_SYSTEM_IP."file/file/upload/image?access_token=".$access_token;
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
        print_r($result);die();
        return $result;
    }

    public function curldata($url, $path, $curlPost){
       // echo $path;die();
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
