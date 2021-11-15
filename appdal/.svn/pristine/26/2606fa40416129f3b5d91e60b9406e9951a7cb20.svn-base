<?php
/**
 * Created by PhpStorm.
 * 产品信息不全控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Product_incomplete extends MY_Controller{

    public function __construct(){
        self::$_check_login = false;
        parent::__construct();
        $this->load->model('product_incomplete_model','incomplete');
        $this->load->model('purchase_user_model','product_user',false,'user');
        $this->load->model('product_line_model','product_line',false,'product');
    }

    /**
     * 产品信息不全列表
     * /purchase_suggest/product_incomplete/product_incomplete_list
     * @author Jaden 2019-1-8
     */

    public function product_incomplete_list(){
        $this->load->helper('status_order');
    	$params = [
    		'sku' => $this->input->get_post('sku'),
    		'buyer_id' => $this->input->get_post('buyer_id'),
    		'product_line_id' => $this->input->get_post('product_line_id'),
            'intercept_reason' => $this->input->get_post('intercept_reason'),
            'start_add_time' => $this->input->get_post('start_add_time'),
            'end_add_time' => $this->input->get_post('end_add_time'),
    	];
    	$page =  $this->input->get_post('offset');
    	$limit = $this->input->get_post('limit');
    	if(empty($page)){
    		$page=1;
    	}
        $limit = query_limit_range($limit);
    	$offset = ($page-1)*$limit;
    	$result=$this->incomplete->get_incomplete_list_all($params,$offset,$limit);
        $result['key'] = array('图片','SKU','产品线','产品名称','单价','开票点','退税率','供应商','拦截原因','创建时间','备注','操作');

        //下拉框
        $user_list = $this->product_user->get_list();
        $drop_down_box['supplier_name'] = array_column($user_list, 'name','id');
        $product_line_list = $this->product_line->get_product_line_list_first();
        $drop_down_box['product_line_list'] =array_column($product_line_list,'linelist_cn_name','product_line_id');
        $drop_down_box['intercept_reason'] = getInterceptReason();
        

        $result['drop_down_box'] = $drop_down_box;
        $result['page_data']['pages'] = ceil($result['page_data']['total']/$limit);
        $result['page_data']['offset'] = $page;
        $this->success_json($result);
    }


    //产品信息不全页面推送接口
    //purchase_suggest/product_incomplete/put_product_incomplete_list
    public function put_product_incomplete_list(){
        $ids = $this->input->get_post('ids');
        $ids_arr = explode(',', $ids);
        //$ids = array(14,15);
        if(!empty($ids)){
        	$params['id']   = $ids_arr;
        }else{
        	$params = [
            'sku'                   => $this->input->get_post('sku'),// SKU
            'buyer_id'              => $this->input->get_post('buyer_id'),// 采购员
            'is_drawback'           => $this->input->get_post('is_drawback'),// 是否退税
            'product_line'          => $this->input->get_post('product_line'),// 产品线
            'demand_type_id'        => $this->input->get_post('demand_type_id'),// 需求类型
        	];
        }
        $page = 1;
        $limit = 500;
        $offset = ($page-1)*$limit;

        $product_incomplete_list_arr = $this->incomplete->get_incomplete_list_all($params,$offset,$limit);
        $product_incomplete_list = $product_incomplete_list_arr['value'];
        $total = $product_incomplete_list_arr['page_data']['total'];//推送的总条数

        $data_list_arr = array();
        //$url = 'http://web.test.cc';
        $this->error_json('待接推送接口');
        if($total>=1){
        	for ($i=0; $i <ceil($total/$limit) ; $i++) { 
        		$page_offset = $i*$limit;
        		$product_incomplete_list_arr = $this->incomplete->get_incomplete_list_all($params,$page_offset,$limit);
        		foreach ($product_incomplete_list as $key=>$value)
				{
                    $data_list_arr[$key]['id'] = $value['id'];
					$data_list_arr[$key]['sku'] = $value['sku'];
					$data_list_arr[$key]['demand_number'] = $value['demand_number'];
					//$data_list_arr['total'] = $product_incomplete_list_arr['paging_data']['total'];
				}
				$return_msg = response_format(1,$data_list_arr);
				$post_data['data'] = json_encode($return_msg);
				$result['states'] = getCurlData($url,$post_data);
				//file_put_contents('aaa.txt', json_encode($return_data),FILE_APPEND);
        	}
        	$this->success_json($result,null,'推送成功');
        }
        
        	
    }

    //产品信息不全页面导出
    public function export_product_incomplete(){
    	$this->load->helper('status_order');
    	$ids = $this->input->get_post('ids');
    	$ids_arr = explode(',', $ids);
        if(!empty($ids)){
        	$params['id']   = $ids_arr;
        }else{
        	$params = [
                'sku' => $this->input->get_post('sku'),
                'buyer_id' => $this->input->get_post('buyer_id'),
                'product_line_id' => $this->input->get_post('product_line_id'),
                'intercept_reason' => $this->input->get_post('intercept_reason'),
                'start_add_time' => $this->input->get_post('start_add_time'),
                'end_add_time' => $this->input->get_post('end_add_time'),
            ];
        }
        $product_incomplete_list_arr = $this->incomplete->get_incomplete_list_all($params,1,1,true);
        $product_incomplete_list = $product_incomplete_list_arr['value'];

        $tax_list_tmp = [];
        if($product_incomplete_list){
            foreach($product_incomplete_list as $v_value){
                $v_value_tmp                       = [];
                $v_value_tmp['sku']                = $v_value['sku'];
                $v_value_tmp['product_img_url']  = $v_value['product_img_url'];
                $v_value_tmp['product_line_name']       = $v_value['product_line_name'];
                $v_value_tmp['product_name']       = $v_value['product_name'];
                $v_value_tmp['unit_price']       = $v_value['unit_price'];
                $v_value_tmp['point']       = $v_value['point'];
                $v_value_tmp['tax_rate']       = $v_value['tax_rate'];
                $v_value_tmp['supplier_name']       = $v_value['supplier_name'];
                $v_value_tmp['intercept_reason']      = $v_value['intercept_reason'];
                $v_value_tmp['add_time']       = $v_value['add_time'];
                $v_value_tmp['remarks']       = $v_value['remarks'];
                


                $tax_list_tmp[] = $v_value_tmp;
            }
        }
        $this->success_json($tax_list_tmp);
    }


    //添加备注
    //purchase_suggest/product_incomplete/create_remarks
    public function create_remarks(){
        $params = [
            'id' => $this->input->get_post('id'),
            'remarks' => $this->input->get_post('remarks'),
        ];
        $result = $this->incomplete->incomplete_create_remarks($params);
        if(!isset($result['code'])){
            $this->error_json($result['msg']);
        }else{
            $this->success_json('备注添加成功');  
        }


    }




}