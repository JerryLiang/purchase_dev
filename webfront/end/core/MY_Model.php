<?php defined('BASEPATH') OR exit('No direct script access allowed');


/**
 *
 * MY_Model 基类
 *
 * @author:	凌云
 * @since: 2018-09-21
 *
 */


class MY_Model extends CI_Model
{
    /**
     * 初始化
     * MY_Model constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    public function __destruct() {
        foreach ($this as $index => $value) {
            $this->$index = null;
        }
    }
}
/* End of file MY_Model.php */
/* Location: ./app/core/MY_Model.php */
