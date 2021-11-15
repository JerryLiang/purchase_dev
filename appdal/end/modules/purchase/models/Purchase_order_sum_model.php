<?php

/**
 * 列表页面统计.
 * User: luxu
 * Date: 2019/7/30
 */
class Purchase_order_sum_model extends Purchase_model
{

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 生成缓存KEY 值(私有)
     * @param   $params     array   审查缓存KEY
     **/
    private function get_sum_key( $params,$resultKey = "total",$flag  ) {

        if( empty($params) ) {

            return $resultKey;
        }

        $Keys = array();
        foreach( $params as $key=>$value ) {

            if( !empty($value) || !is_null($value) ) {
                $Keys[$key] = $value;
            }
        }
        if( !empty($Keys) ) {
            return gzdeflate( $flag."-".json_encode($Keys));
        }

        return $resultKey;

    }


    /**
     * 生成缓存KEY 值(公开)
     * @param   $params     array   审查缓存KEY
     **/

    public function get_key( $params,$flag ) {

        if( isset($params['new']) ) {
            unset($params['new']);
        }
        $keys = $this->get_sum_key($params,"total",$flag);
        return $keys;
    }

    /**
     * 统计数据存入缓存
     * @param :  $keys    string     缓存KEY
     *           $data    array      缓存数据
     *           $timeout string     缓存时间
     *@return  BOOL  缓存成功返回TRUE，失败返回FALSE
     **/

    public function set_sum_cache( $keys, $data,$timeout =500 ) {

        if( empty($keys) && empty($data) ) {

            return False;
        }

        $result = $this->rediss->setData($keys, $data, $timeout);
        return $result;
    }

    /**
     * 获取数据缓存
     * @param :  $keys    string     缓存KEY
     *
     *@return  array  缓存成功返回缓存信息，失败返回NULL
     **/

    public function get_sum_cache( $keys) {

        return $this->rediss->getData($keys);
    }
}