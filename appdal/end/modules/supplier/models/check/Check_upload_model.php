<?php

/**
 * Created by PhpStorm.
 * 供应商验厂-文件资料
 * author: Jackson
 * Date: 2019/1/23
 */
class Check_upload_model extends Purchase_model
{

    protected $table_name = 'supplier_check_upload';//供货商验厂-文件资料

    /**
     * 返回表名
     * MY_Model 中的 filterNotExistFields() 方法需要
     * @return string
     */
    public function table_nameName()
    {
        return $this->table_name;
    }

    /**
     * Supplier_model constructor.
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @desc 供应商验货验厂获取相关资料根据ID
     * @author Jackson
     * @param array $parames 参数
     * @return array
     */
    public function get_meterial(array $parames, $flag = false)
    {
        //条件
        $condition = array();
        $_field = '';
        if (!$flag) {
            //显示获取
            $condition['check_id'] = $parames['check_id'];
        } else {
            //下载获取
            $condition['where_in'] = array('id' => explode(',', $parames['id']));
            $_field = 'url';
        }
        $condition['status'] = 1;//文件状态  1.正常,2.删除

        //查询字段
        $fields = 'id,check_id,file_type,file_name,'.$_field;

        //查询数据
        $result = $this->getDataByCondition($condition, $fields);

        if ($flag) {
            //增加下载地址
            array_walk($result, function (&$v) {
                $v['url'] = "http://" . $_SERVER['HTTP_HOST'] . $v['url'];
            }
            );
        }

        return array(
            'list' => $result,
        );

    }

}