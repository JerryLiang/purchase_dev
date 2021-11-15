<?php
/**
 * User: Jaxton
 * Date: 2019/1/11  10:50
 */

class File_operation {

	/**
	* 上传文件
	* @author Jaxton
	* @param  $upload_file 上传的文件数组 Array
    *    (
            [name] => 2071d5d3531407ec17a99c4be6e966438bbe38fa_original.jpeg
            [type] => image/jpeg
            [tmp_name] => C:\Windows\phpF3DC.tmp
            [error] => 0
            [size] => 55488
    *    )
	* @param  $upload_path 上传文件保存路径,直接填写upload下面的文件夹名字
	* @param  $file_prefix 上传的文件前缀名
	* @return array 上传成功时‘file_info’返回的就是文件的路径和类型
	*/
	public function upload_file($upload_file,$upload_path='',$file_prefix=''){ 
		$result_arr=[
    		'errorCode'=>false,
    		'errorMess'=>'',
    		'file_info'=>''
    	];
        if (!empty ($upload_file['name'])) {
            $file_type_arr = [
                'zip', 'rar', 'pdf', 'doc', 'docx', 'doc', 'pptx', 'ppt', 'xlsx','csv' ,'xls', 'gz', 'png', 'gif', 'jpg', 'jpeg','bmp'
            ];

        
            //单个附件处理
            $tmp_file  = $upload_file ['tmp_name'];
            $file_name = $upload_file ['name'];

            $file_types = explode(".", $file_name);
            $file_type  = $file_types [count($file_types) - 1];

            if (!in_array(strtolower($file_type), $file_type_arr)) {
                $result_arr['errorMess'] = '文件格式错误,提交失败！';

                return $result_arr;
            }

            /*设置上传路径*/            
            $savePath = dirname(dirname(__FILE__))."/".$upload_path;
            if (!file_exists($savePath)) {
                mkdir($savePath, 0755, true);
            }
            /*是否上传成功*/
            if(empty($file_prefix)){
            	$file_prefix='PURCHASE';
            }
            $new_file_name='/'.$file_prefix.'_'.date('ymdHis').rand(10000,99999).'.'.strtolower($file_type);
            $file_name = $file_prefix.'_'.date('ymdHis').rand(10000,99999).'.'.strtolower($file_type);
            $upload_result=copy($tmp_file, $savePath . $new_file_name);
            if (!$upload_result) {
                $result_arr['errorMess'] = '上传失败！';
                return $result_arr;
            }else{
            	$result_arr['errorCode'] = true;
            	$result_arr['errorMess'] = '/'.$upload_path.$new_file_name;
            	$result_arr['file_info'] = [
            		'file_path' => '/'.$upload_path.$new_file_name,
            		'file_type' => strtolower($file_type),
                    'file_name' => $file_name
            	];
            	return $result_arr;
            }
            
            
        }else{
            $result_arr['errorMess'] = '未接收到上传的文件';
        	return $result_arr;
        }

	}


    /**
     *
     * 上传文件
     * @author Jolon
     * @param  $upload_file 上传的文件数组 Array
     *    (
     *      [name] => 2071d5d3531407ec17a99c4be6e966438bbe38fa_original.jpeg
     *      [type] => image/jpeg
     *       [tmp_name] => C:\Windows\phpF3DC.tmp
     *      [error] => 0
     *      [size] => 55488
     *    )
     * @param string $upload_path 上传文件保存路径,直接填写upload下面的文件夹名字
     * @param string $file_prefix 上传的文件前缀名
     * @return array 上传成功时‘file_info’返回的就是文件的路径和类型
     * @return array
     */
    public function upload_file2($upload_file, $upload_path = '', $file_prefix = ''){
        $result_arr = [
            'errorCode' => false,
            'errorMess' => '',
            'file_info' => ''
        ];
        if(!empty ($upload_file['name'])){
            $file_type_arr = [
                'zip', 'rar', 'pdf', 'doc', 'docx', 'doc', 'pptx', 'ppt', 'xlsx', 'csv', 'xls', 'gz', 'png', 'gif', 'jpg', 'jpeg'
            ];

            //单个附件处理
            $tmp_file  = $upload_file ['tmp_name'];
            $file_name = $upload_file ['name'];

            $file_types = explode(".", $file_name);
            $file_type  = $file_types [count($file_types) - 1];

            if(!in_array(strtolower($file_type), $file_type_arr)){
                $result_arr['errorMess'] = '文件格式错误,提交失败！';

                return $result_arr;
            }
            /*设置上传路径*/
            $savePath = $upload_path;
            if(!file_exists($savePath)){
                mkdir($savePath, 0755, true);
            }
            /*是否上传成功*/
            if(empty($file_prefix)){
                $file_prefix = 'PURCHASE';
            }
            $new_file_name = '/'.$file_prefix.'_'.date('ymdHis').rand(10000, 99999).'.'.strtolower($file_type);
            if($file_type == 'zip'){
                $new_file_name = '/'.$file_name;
                $upload_result = copy($tmp_file, $savePath . $new_file_name);
            }else {
                $upload_result = copy($tmp_file, $savePath . $new_file_name);
            }
            if(!$upload_result){
                $result_arr['errorMess'] = '上传失败！';

                return $result_arr;
            }else{
                $result_arr['errorCode'] = true;
                $result_arr['errorMess'] = '/'.$upload_path.$new_file_name;
                $result_arr['file_info'] = [
                    'file_path' => '/'.$upload_path.$new_file_name,
                    'file_type' => strtolower($file_type)
                ];

                if($file_type == 'zip'){
                    $result_arr['file_info']['file_path'] = '/'.$upload_path.$new_file_name;
                    chmod('/'.$upload_path.$new_file_name,0755);
                }
                return $result_arr;
            }
        }else{
            $result_arr['errorMess'] = '未接收到上传的文件';
            return $result_arr;
        }
    }

	/**
	*下载文件
	* @author Jaxton
	* @param  $file 文件地址
    * @param $type 文件类型1 其他文件2为pdf
	*/
	public function download_file($file,$type=1){

		$get_file=@file_get_contents($file);
		if($get_file){
			$file_size=strlen($get_file);
			if ($type == 1) {
                header("Content-type:application/octet-stream");

            } else {
                header('Content-type: application/pdf');

            }
            $filename = basename($file);
            header("Content-Type: application/force-download");
            header("Content-Disposition:attachment;filename = ".$filename);
			//header("Accept-ranges:bytes");
			//header("Accept-length:".$file_size);
            echo $get_file;
		    return true;

		}else{
			return false;
		}
		
	}

}