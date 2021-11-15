<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/12/12
 * Time: 15:09
 */

class Center_Data_model extends Api_base_model {
    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 审核数据接口
     * @author:luxu
     * @time:2020/02/26
     **/

    public function downExamine($data)
    {
        $url = $this->_baseUrl . $this->_downExamine;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        return $res;
    }

    /**
     *删除数据接口
     * @author:luxu
     * @time:2020/02/26
     **/

    public function delete_center_data($data)
    {
        $url = $this->_baseUrl . $this->_delete_center_data;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        return $res;
    }


}