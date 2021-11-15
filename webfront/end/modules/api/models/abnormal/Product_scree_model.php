<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/1
 * Time: 14:31
 */
class Product_scree_model extends Api_base_model
{

    //api conf  /modules/api/conf/caigou_sys_product_scree.php
    protected $_getScreeListApi = "";
    protected $_screeExportCsvApi = "";
    protected $_screeCreateApi = "";
    protected $_screeAuditApi = "";
    protected $_affirmSupplierApi = "";

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * SKU屏蔽列表-占伟龙
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     *
     */
    public function get_scree_list($get)
    {
        $url = $this->_baseUrl . $this->_getScreeListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 导出CSV
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @throws Exception
     *
     */
    public function scree_export_csv($get)
    {
        $url = $this->_baseUrl . $this->_screeExportCsvApi . "?" . http_build_query($get);
        $res = $this->_curlReadHandleApi($url, "", 'GET');
        $this->load->library("CommonHelper");

        if(isset($res['status']) and $res['status'] == 0){
            return $res;
        }else{
            CommonHelper::arrayToCsv(
                isset($res['data_list']['key']) ? $res['data_list']['key'] : '',
                isset($res['data_list']['value']) ? $res['data_list']['value'] : '',
                'SKU屏蔽申请导出-'.date('YmdH_i_s') . ".csv"
            );
        }
    }

    public function set_post_json($post,$url) {

        $send_string = json_encode($post);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$send_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($send_string))
        );

        $result = curl_exec($ch);
        return $result;


    }

    public function set_scree_create($clientData) {

        $url = $this->_baseUrl . $this->_newscreeCreateApi;
        $result = $this->_curlWriteHandleApi($url, $clientData, 'POST');
        return $result;
    }



    /**
     * 创建 SKU屏蔽 记录-
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $post
     * @throws Exception
     * @return array
     */
    public function scree_create($post)
    {
        $url = $this->_baseUrl . $this->_screeCreateApi;
        $res = $this->_curlWriteHandleApi($url, $post, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }


    public function getPrevData($post)
    {
        $url = $this->_baseUrl . $this->_getPrevData;
        $res = $this->_curlWriteHandleApi($url, $post, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 采购经理审核
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $post
     * @throws Exception
     * @return array
     */
    public function scree_audit($post)
    {
        $url = $this->_baseUrl . $this->_screeAuditApi;
        $res = $this->_curlWriteHandleApi($url, $post, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 采购确认 - 替换供应商
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $post
     * @throws Exception
     * @return array
     */
    public function affirm_supplier($post)
    {
        $url = $this->_baseUrl . $this->_affirmSupplierApi;
        $res = $this->_curlWriteHandleApi($url, $post, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    public function get_logs( $post ) {


        $url = $this->_baseUrl . $this->_get_logs."?uid=".$post['uid'];
        $param['uid'] = $post['uid'];
        if( isset($post['scree_id']))
        {
            $url.= "&scree_id=".$post['scree_id'];
            $param['scree_id'] = $post['scree_id'];
        }
        if( isset($post['sku']))
        {
            $url .="&sku=".$post['sku'];
            $param['sku'] = $post['sku'];
        }
        $result = $this->request_http($param, $url, 'GET');
        return $result;
    }

    /**
     *  sku屏蔽流程调整——开发部无需审核，采购经理审核通过后，sku审核状态变成“已结束”
     *  @author: luxu
     **/
    public function update_estimate_time( $params )
    {


        $url = $this->_baseUrl . $this->_update_estimate_time;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $res;

    }

    public function get_scree_estimatetime( $post )
    {
        $url = $this->_baseUrl . $this->_get_scree_estimatetime."?uid=".$post['uid']."&scree_id=".$post['scree_id'];
        $result = $this->request_http($post, $url, 'GET');
        return $result;
    }

    /**
     * SKU 屏蔽数据导入
     * @param $post  array  导入数据
     * @author:luxu
     *
     **/
    public function scree_import_data($post){
        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $file_path = $post['file_path'];
        $fileExp   = explode('.', $file_path);
        $fileExp   = strtolower($fileExp[count($fileExp) - 1]);//文件后缀

        include APPPATH.'third_party/PHPExcel/IOFactory.php';
        if ($fileExp == 'csv') $PHPReader = new \PHPExcel_Reader_CSV();
        if(!isset($PHPReader)){
            $return['code']    = false;
            $return['message'] = "只能导入 csv 文件 ";
            $return['data']    = '';
            return $return;
        }
        $PHPReader = PHPExcel_IOFactory::createReader('CSV')
            ->setDelimiter(',')
            ->setInputEncoding('GBK') //不设置将导致中文列内容返回boolean(false)或乱码
            ->setEnclosure('"')
            ->setSheetIndex(0);

        $PHPReader      = $PHPReader->load($file_path);

        $currentSheet   = $PHPReader->getSheet();
        $sheetData      = $currentSheet->toArray(null,true,true,true);
        $out = array ();
        $n = 0;
        foreach($sheetData as $data){

            $num = count($data);
            $i =0;
            foreach($data as $data_key=>$data_value){
                $out[$n][$i] = trim($data_value);
                ++$i;
            }
            $n++;
        }

        $header = ['Content-Type: application/json'];
        $params['import_arr'] = $out;
        $url = $this->_baseUrl. $this->_scree_import_product.'?uid='.$post['uid'];
        $result = getCurlData($url,json_encode($params,JSON_UNESCAPED_UNICODE),'post',$header,false,['time_out'=>120]);
        $result = json_decode($result,True);

        if( isset($result['status']) && $result['status'] == 0 && $result['errorMess'])
        {
            $result = array(

                'status' => 0,
                'errorMess' => isset($result['errorMess'])?$result['errorMess']['error_message']:'',
                'data_list' => isset($result['errorMess']['error_list'])?$result['errorMess']['error_list']:''
            );
        }
        return $result;
    }


}