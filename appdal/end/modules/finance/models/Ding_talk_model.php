<?php  include_once APPPATH."third_party".DIRECTORY_SEPARATOR.'CurlRequest.php';
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/8/20
 * Time: 15:00
 */

class Ding_talk_model extends Purchase_model{

    protected $table_name = 'ding_talk';// 数据表名称


    public function __construct(){
        parent::__construct();
    }

    public function getDingTalkInfo($role_number){
        $user_number = $this->purchase_db
            ->select('GROUP_CONCAT(user_number) AS user_number')
            ->where('role_number',$role_number)
            ->get($this->table_name)
            ->row_array();
        return $user_number;
    }

    /**
     * @param array $ding_params
     * @return bool|string
     */
    public function curlGet($ding_params = array()){
        $curlRequest = CurlRequest::getInstance();
        $curlRequest->setServer(DD_HOST);
        return $curlRequest->cloud_get('personalnews/Personal_news/personalNewsBatch',$ding_params);
    }

    /**
     * @param array $param
     * @return bool|string
     */
    public function pushDingTalkInfo($param = array()){
        if (empty($param) or !isset($param['role_number']) or !isset($param['msg'])) return false;
        $result = $this->getDingTalkInfo($param['role_number']);
        if (!empty($result)) {
            $ding_params = array(
                'agent_id' => AGENT_ID,
                'userNumber' => $result['user_number'],
                'msg' => $param['msg'],
            );
            return $this->curlGet($ding_params);
        }
        return false;
    }
}