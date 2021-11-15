<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/11/6
 * Time: 14:51
 */
class Purchase_return_tracking_api extends MY_Controller
{

    public function __construct()
    {
        self::$_check_login = false;
        parent::__construct();
    }

    /**
     *
     * @return mixed
     */
    public function get_delivery_data_by_orderId(){
        $this->load->model('purchase_return/Purchase_return_tracking_model','purchase_return');
        $limit = $this->input->post_get('limit');
        if(empty($limit)){
            $limit = 10;
        }
        $return_number = $this->input->post_get('return_number');
        $result = $this->purchase_return->get_delivery_data_by_orderId($limit,$return_number);

    }
}