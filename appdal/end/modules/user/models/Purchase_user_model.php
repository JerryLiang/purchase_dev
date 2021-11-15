<?php
/**
 * Created by PhpStorm.
 * 采购相关用户模型
 * User: Jolon
 * Date: 2019/01/07 0028 9:58
 */
class Purchase_user_model extends Purchase_model {

    protected $table_name   = 'purchase_user_info';// 数据表名称

    public $ip;
    public $getUserListByUserIdAndUserName;// 获取指定用户信息
    public $getOaUserVo;// 通过部门名称获取人员列表
    public $selectAllUserByDeptId;// 根据部门id获取部门下所有人员(包括所有子级部门)
    public $getProcurementUserList;// 采购系统用户列表
    public $getUserListByUserNo;// 根据用户编号查询用户信息
    public $getUserListByUserId;// 根据用户id查询用户信息
    public $getUserListByUserName;// 根据用户名称查询用户信息
    public $getUserByJobName;// 通过岗位名称获取用户信息
    public $getDirectlyDept;//

    public function __construct(){
        parent::__construct();
       // 加载 URL 配置项
        $this->load->config('api_config', FALSE, TRUE);
       $access_taken = getOASystemAccessToken();
        if (!empty($this->config->item('oa_system'))) {
            $oa_system = $this->config->item('oa_system');
            foreach($oa_system as $key => $value){
                $this->$key = $value."?access_token=".$access_taken;
            }
        }
    }

    /**
     * 获取采购员列表
     * @author Jolon
     * @return array
     */
    public function get_list(){
        if(CG_ENV == 'prod'){
            return $this->get_all_user_by_dept_id('1079904','CGY_ALL');// 72331746  采购部（包含下级部门）下采购员列表  写死
        }elseif(CG_ENV == 'test'){
            return $this->get_all_user_by_dept_id('1079904','CGY_ALL');// 54518313  采购部（包含下级部门）下采购员列表  写死
        }

        $user_list = $this->rediss->getData('CGY_ALL');
        if(empty($user_list)){
            $header = array('Content-Type: application/json');

            $param['deptName'] = '采购部';
            $result = getCurlData($this->getOaUserVo,json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);
            $result = json_decode($result,true);

            if(isset($result['code']) and $result['code'] == 200 and !empty($result['data'])){// 200成功，500失败
                $data = $result['data'];
                $user_list = [];
                foreach($data as $key => $user){
                    $user_list[$key]['id']         = $user['userId'];
                    $user_list[$key]['staff_code'] = isset($user['userNumber'])?$user['userNumber']:'';
                    $user_list[$key]['name']       = $user['userName'].$user_list[$key]['staff_code'];
                }
                $this->rediss->setData('CGY_ALL',$user_list);
            }else{
                $user_list = [];
            }
        }

        if(is_string($user_list) and is_json($user_list)){
            $user_list = json_decode($user_list,true);
        }

        return $user_list;
    }
       /**
     * 获取财务人员列表
     * @author Jolon
     * @return array
     */
    public function get_finance_list(){
//        if(CG_ENV == 'prod'){
//            return $this->get_all_user_by_dept_id('72331746','CGY_ALL');// 72331746  采购部（包含下级部门）下采购员列表  写死
//        }elseif(CG_ENV == 'test'){
//            return $this->get_all_user_by_dept_id('54518313','CGY_ALL');// 54518313  采购部（包含下级部门）下采购员列表  写死
//        }

        $user_list = $this->rediss->getData('CGY_ALL_FIN');
        if(empty($user_list)){
            $header = array('Content-Type: application/json');
            $param['jobName'] = '出纳';
            $result = getCurlData($this->getUserByJobName,json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);
            $result = json_decode($result,true);     
            if(isset($result['code']) and $result['code'] == 200 and !empty($result['data'])){// 200成功，500失败
                $data = $result['data'];
                $user_list = [];
                foreach($data as $key => $user){
                    $user_list[$key]['id']         = $user['id'];
                    $user_list[$key]['staff_code'] = isset($user['userNumber'])?$user['userNumber']:'';
                    $user_list[$key]['name']       = $user['userName'].$user_list[$key]['staff_code'];
                }
                $this->rediss->setData('CGY_ALL_FIN',$user_list);
            }else{
                $user_list = [];
            }
        }
        if(is_string($user_list) and is_json($user_list)){
            $user_list = json_decode($user_list,true);
        }

        return $user_list;
    }

    /**
     * 指定部门下用户列表
     * @author Jolon
     * @param string $dept_id 部门ID
     * @param string $list_key 设定缓存键名
     * @return array
     */
    public function get_all_user_by_dept_id($dept_id,$list_key){
        $user_list = $this->rediss->getData($list_key);
        if(empty($user_list)){
            $header = array('Content-Type: application/json');

            $param['id'] = $dept_id;
            $param['isAll'] =1;
            $result = getCurlData($this->selectAllUserByDeptId,json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);
            $result = json_decode($result,true);

            if(isset($result['code']) and $result['code'] == 200 and !empty($result['data'])){// 200成功，500失败
                $data = $result['data'];
                $user_list = [];
                foreach($data as $key => $user){
                    $user_list[$key]['id']         = $user['id'];
                    $user_list[$key]['staff_code'] = isset($user['userNumber'])?$user['userNumber']:'';
                    $user_list[$key]['name']       = $user['userName'].$user_list[$key]['staff_code'];
                }
                $this->rediss->setData($list_key,$user_list);
            }else{
                $user_list = [];
            }
        }
        if(is_string($user_list) and is_json($user_list)){
            $user_list = json_decode($user_list,true);
        }

        return $user_list;
    }

    /**
     * 获取开发部下所有员工-下拉信息
     * @author Manson
     * @return array
     */
    public function get_user_developer_list(){
        $user_list = $this->rediss->getData('USER_DEVELOPER');
        if(empty($user_list)){
            $dept_id = '1079231';//开发部id
            $user_list = $this->get_all_user_by_dept_id($dept_id,'USER_DEVELOPER');
        }
        if(is_string($user_list) and is_json($user_list)){
            $user_list = json_decode($user_list,true);
        }
        return $user_list;
    }

    /**
     * 获取指定的用户信息（所有用户皆可获取）
     * @author Jolon
     * @param int    $user_id       用户ID
     * @param string $user_name     采购员名称
     * @param string $field         返回字段（逗号分隔）
     * @return null|string|array
     */
    public function get_user_info($user_id = null,$user_name = null,$field = '*'){
        if($user_id){
            $user_info = $this->get_user_info_by_id($user_id);
        }elseif($user_name){
            $user_info = $this->get_user_info_by_user_name($user_name);
        }else{
            return false;
        }

        if($field == '*'){
            $user_info_tmp = $user_info;
        }else{
            if(strpos($field,',') === false){// 指定的一个字段
                $user_info_tmp = isset($user_info[$field])?$user_info[$field]:null;
            }else{
                $user_info_tmp = [];
                $fields = explode(',',$field);
                foreach($fields as $key){// 指定的多个字段
                    $user_info_tmp[$key] = isset($user_info[$key])?$user_info[$key]:null;
                }
            }
        }

        return $user_info_tmp;
    }

    /**
     * 根据用户编号查询用户信息
     * @param $staff_code
     * @return bool
     */
    public function get_user_info_by_staff_code($staff_code){
        if(empty($staff_code)) return false;

        $key    = 'USER_CODE_'.$staff_code;
        $param  = [$staff_code];
        $user_info = $this->rediss->getData($key);
        if(empty($user_info)){
            $header = array('Content-Type:application/json');
            $result = getCurlData($this->getUserListByUserNo,json_encode($param),'post',$header);
            $result = json_decode($result,true);
            if(!empty($result) and isset($result[0])){
                $data                    = $result[0];
                $user_info['user_id']    = $data['id'];// 用户ID
                $user_info['staff_code'] = isset($data['userNumber']) ? $data['userNumber'] : '';// 员工编号
                $user_info['user_name']  = $data['userName'].$user_info['staff_code'];// 用户姓名
                $user_info['orgId']      = isset($data['orgId']) ? $data['orgId'] : '';// 组织id
                $user_info['jobId']      = isset($data['jobId']) ? $data['jobId'] : '';// 组织id
                $user_info['posId']      = isset($data['posId']) ? $data['posId'] : '';// 组织id
                $user_info['phone']      = isset($data['phone']) ? $data['phone'] : '';// 联系电话
                $user_info['mobile']     = isset($data['mobile']) ? $data['mobile'] : '';// 联系电话
                $user_info['staff_name'] = $data['userName'];//员工名称
                $this->rediss->setData($key,$user_info);
            }else{
                return false;
            }
        }

        return $user_info;
    }

    /**
     * 根据用户id查询用户信息
     * @param $user_id
     * @return bool
     */
    public function get_user_info_by_id($user_id){
        $key    = 'USER_ID_'.$user_id;
        $param  = [$user_id];
        $user_info = $this->rediss->getData($key);
        if(empty($user_info)){
            $header = array('Content-Type:application/json');
            $result = getCurlData($this->getUserListByUserId,json_encode($param),'post',$header);
            $result = json_decode($result,true);
            if(!empty($result) and isset($result[0])){
                $data                    = $result[0];
                $user_info['user_id']    = $data['id'];// 用户ID
                $user_info['staff_code'] = isset($data['userNumber']) ? $data['userNumber'] : '';// 员工编号
                $user_info['user_name']  = $data['userName'].$user_info['staff_code'];// 用户姓名
                $user_info['orgId']      = isset($data['orgId']) ? $data['orgId'] : '';// 组织id
                $user_info['jobId']      = isset($data['jobId']) ? $data['jobId'] : '';// 组织id
                $user_info['posId']      = isset($data['posId']) ? $data['posId'] : '';// 组织id
                $user_info['phone']      = isset($data['phone']) ? $data['phone'] : '';// 联系电话
                $user_info['mobile']     = isset($data['mobile']) ? $data['mobile'] : '';// 联系电话
                $user_info['staff_name'] = $data['userName'];//员工名称
                $this->rediss->setData($key,$user_info);
            }else{
                return false;
            }
        }
        return $user_info;
    }

    /**
     * 根据用户id查询用户信息
     * @param $user_id
     * @return bool
     */
    public function get_user_info_by_user_id($user_id){
        if(empty($user_id)){
            return [];
        }
        $where = ['user_id' => $user_id];
        $user_info = $this->purchase_db->where($where)->get($this->table_name)->row_array();

        return $user_info;

        
    }




    /**
     * 根据用户名称查询用户信息
     * @param $user_name
     * @return bool
     */
    public function get_user_info_by_user_name($user_name){
        $key    = 'USER_NAME_'.md5($user_name);
        $param  = ['userName' => $user_name];

        $user_info = $this->rediss->getData($key);
        if(empty($user_info)){
            $header = array('Content-Type:application/json');
            $result = getCurlData($this->getUserListByUserIdAndUserName,json_encode($param),'post',$header);
            $result = json_decode($result,true);
            if(isset($result['code']) and $result['code'] == 200 and !empty($result['data']) and !empty($result['data'][0])){
                $data                   = $result['data'][0];
                $user_info['user_id']   = $data['userId'];// 用户ID
                $user_info['staff_code'] = isset($data['userNumber']) ? $data['userNumber'] : '';// 员工编号
                $user_info['user_name'] = $data['userName'].$user_info['staff_code'];// 用户姓名
                $user_info['phone']     = isset($data['phone']) ? $data['phone'] : '';// 联系电话
                $user_info['mobile']    = isset($data['mobile']) ? $data['mobile'] : '';// 联系电话
                $user_info['email']     = isset($data['email']) ? $data['email'] : '';// 邮箱
                $user_info['posId']     = isset($data['posId'])?$data['posId']:'';// 组织id
                $user_info['posName']   = isset($data['posName'])?$data['posName']:'';// 组织名称
                $user_info['orgId']     = isset($data['orgId'])?$data['orgId']:'';// 组织id
                $user_info['orgName']   = isset($data['orgName'])?$data['orgName']:'';// 组织名字
                $this->rediss->setData($key,$user_info);
            }else{
                return false;
            }
        }

        return $user_info;
    }


    /**
     * 采购系统所有用户 列表（采购系统内部用户）
     * @author Jolon
     * @param null $user_id
     * @param null $user_name
     * @return array
     */
    public function get_user_all_list($user_id = null,$user_name = null){
        $user_list  = $this->rediss->getData('USER_ALL');
        if(empty($user_list)){
            $result = getCurlData($this->getProcurementUserList.'&isAll=1','','get');
            $result = json_decode($result,true);
            if(isset($result['code']) and $result['code'] == 200 and !empty($result['data'])){// 200成功，500失败
                $data = $result['data'];
                $user_list = [];
                foreach($data as $key => $user){
                    $user_list[$key]['id']         = isset($user['id'])?$user['id']:0;
                    $user_list[$key]['user_id']    = isset($user['id'])?$user['id']:0;// 用户ID
                    $user_list[$key]['staff_code'] = isset($user['userNumber'])?$user['userNumber']:'';// 员工编号
                    $user_list[$key]['name']       = isset($user['userName'])?$user['userName'].$user_list[$key]['staff_code']:'';
                    $user_list[$key]['orgId']      = isset($user['orgId']) ? $user['orgId'] : 0;
                    $user_list[$key]['jobId']      = isset($user['jobId']) ? $user['jobId'] : 0;
                    $user_list[$key]['posId']      = isset($user['posId']) ? $user['posId'] : 0;
                    $user_list[$key]['mobile']     = isset($user['mobile']) ? $user['mobile'] : '';
                }
                $this->rediss->setData('USER_ALL',$user_list);
            }else{
                $user_list = [];
            }
        }

        if($user_list){// 查找指定用户
            if($user_id){
                $user_list_tmp = arrayKeyToColumn($user_list,'id');
                return isset($user_list_tmp[$user_id])?$user_list_tmp[$user_id]:[];
            }elseif($user_name){
                $user_list_tmp = arrayKeyToColumn($user_list,'name');
                return isset($user_list_tmp[$user_name])?$user_list_tmp[$user_name]:[];
            }
        }

        return $user_list;
    }

    /**
     * 财务部所有人
     */
    public function get_finance_all_list(){
        $user_list = $this->rediss->getData('FIN_ALL');
        if(empty($user_list)){
            $dept_name = '财务部';
            $user_list = $this->get_all_user_by_dept_id($this->getDirectlyDept($dept_name),'FIN_ALL');
        }
        if(is_string($user_list) and is_json($user_list)){
            $user_list = json_decode($user_list,true);
        }
        return $user_list;
    }
    /**
     * 财务主管列表
     * @return array|mixed
     */
    public function get_finance_Supervisor_list(){
        $user_list = $this->rediss->getData('CGY_FIN_SUPERVISOR');
        if(empty($user_list)){
            $header = array('Content-Type: application/json');
            $param['jobName'] = '财务主管';
            $result = getCurlData($this->getUserByJobName,json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);
            $result = json_decode($result,true);
            if(isset($result['code']) and $result['code'] == 200 and !empty($result['data'])){// 200成功，500失败
                $data = $result['data'];
                $user_list = [];
                foreach($data as $key => $user){
                    $user_list[$key]['id']         = $user['id'];
                    $user_list[$key]['staff_code'] = isset($user['userNumber'])?$user['userNumber']:'';
                    $user_list[$key]['name']       = $user['userName'].$user['userNumber'];
                }
                $this->rediss->setData('CGY_FIN_SUPERVISOR',$user_list);
            }else{
                $user_list = [];
            }
        }
        if(is_string($user_list) and is_json($user_list)){
            $user_list = json_decode($user_list,true);
        }

        return $user_list;
    }
    /**
     * 财务经理
     * @return array|mixed
     */
    public function get_finance_Manager_list(){
        $user_list = $this->rediss->getData('CGY_FIN_MANAGER');
        if(empty($user_list)){
            $header = array('Content-Type: application/json');
            $param['jobName'] = '财务经理';
            $result = getCurlData($this->getUserByJobName,json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);
            $result = json_decode($result,true);
            if(isset($result['code']) and $result['code'] == 200 and !empty($result['data'])){// 200成功，500失败
                $data = $result['data'];
                $user_list = [];
                foreach($data as $key => $user){
                    $user_list[$key]['id']         = $user['id'];
                    $user_list[$key]['staff_code'] = isset($user['userNumber'])?$user['userNumber']:'';
                    $user_list[$key]['name']       = $user['userName'].$user['userNumber'];
                }
                $this->rediss->setData('CGY_FIN_MANAGER',$user_list);
            }else{
                $user_list = [];
            }
        }
        if(is_string($user_list) and is_json($user_list)){
            $user_list = json_decode($user_list,true);
        }

        return $user_list;
    }
    /**
     * 财务总监
     * @return array|mixed
     */
    public function get_finance_Officer_list(){
        $user_list = $this->rediss->getData('CGY_FIN_OFFICER');
        if(empty($user_list)){
            $header = array('Content-Type: application/json');
            $param['jobName'] = '财务总监';
            $result = getCurlData($this->getUserByJobName,json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);
            $result = json_decode($result,true);
            if(isset($result['code']) and $result['code'] == 200 and !empty($result['data'])){// 200成功，500失败
                $data = $result['data'];
                $user_list = [];
                foreach($data as $key => $user){
                    $user_list[$key]['id']         = $user['id'];
                    $user_list[$key]['staff_code'] = isset($user['userNumber'])?$user['userNumber']:'';
                    $user_list[$key]['name']       = $user['userName'].$user['userNumber'];
                }
                $this->rediss->setData('CGY_FIN_OFFICER',$user_list);
            }else{
                $user_list = [];
            }
        }
        if(is_string($user_list) and is_json($user_list)){
            $user_list = json_decode($user_list,true);
        }

        return $user_list;
    }
    /**
     * 总经办审核
     * @return array|mixed
     */
    public function get_finance_General_list(){
        $user_list = $this->rediss->getData('GENERAL_ALL');
        if(empty($user_list)){
            $dept_name = '总经办';
            $user_list = $this->get_all_user_by_dept_id($this->getDirectlyDept($dept_name),'GENERAL_ALL');
        }
        if(is_string($user_list) and is_json($user_list)){
            $user_list = json_decode($user_list,true);
        }
        return $user_list;
    }
    /**
     * 根据部门名称获取部门ID
     * @param $dept_name
     * @return array
     */
    public function getDirectlyDept($dept_name){
        $header = array('Content-Type: application/json');
        $result = getCurlData($this->getDirectlyDept,null,'post',$header);
        $result = json_decode($result,true);
        $dept_id = '';
        if(isset($result['code']) and $result['code'] == 200 and !empty($result['data'])){// 200成功，500失败
            $data = $result['data'];
            foreach($data as $key => $dept){
                if($dept_name==$dept['name']){
                    $dept_id  = $dept['id'];
                }
            }
        }
        return $dept_id;
    }

    /**
      * function:获取公司所有的员工信息
     **/
    public function getCompanyAllPerson()
    {
        $user_list = $this->rediss->getData('COMPANY_ALL_PERSON');
        if( empty($user_list) ) {

            $header = array('Content-Type: application/json');
            $this->load->config('api_config', FALSE, TRUE);
            if( !empty($this->config->item('java_system_oa')) ) {

                $sytem_items = $this->config->item('java_system_oa');
                $get_oa_url = isset( $sytem_items['get_company_all'] )? $sytem_items['get_company_all']."?access_token=". getOASystemAccessToken():'';
                $result = getCurlData($get_oa_url,json_encode(["pageNumber"=>1,"pageSize"=>20000,"isDel"=>0]),'post',$header);
                $result = json_decode( $result,True);
                if( !empty($result) && $result['code']==200 && $result['data']['records']) {

                     $user_list = [];
                     foreach(  $result['data']['records'] as $key=>$value) {
                         if( $value['isDel'] == 0) {
                             $user_list[$key]['id'] = $value['id'];
                             $user_list[$key]['staff_code'] = isset($value['userNumber']) ? $value['userNumber'] : '';
                             $user_list[$key]['name'] = $value['userName'] . $value['userNumber'];
                         }
                     }

                     $this->rediss->setData('COMPANY_ALL_PERSON',json_encode($user_list) );

                     return $user_list;

                }else{

                    return [];
                }

            }
        }else{

            return json_decode( $user_list,True);
        }
    }

    public function getCompanyDep()
    {
        $user_list = $this->rediss->getData('COMPANY_ALL_DEPTM');
        if( empty($user_list) ) {

            $header = array('Content-Type: application/json');
            $this->load->config('api_config', FALSE, TRUE);
            if( !empty($this->config->item('java_system_oa')) ) {

                $sytem_items = $this->config->item('java_system_oa');
                $get_oa_url = isset( $sytem_items['get_company_all_dep'] )? $sytem_items['get_company_all_dep']."?access_token=". getOASystemAccessToken():'';
                $result = getCurlData($get_oa_url,json_encode(["id"=>1085984]),'post',$header);
                $result = json_decode( $result,True);
                if( !empty($result) && $result['code']==200 && $result['data']) {

                    $dep_list = [];
                    foreach(  $result['data'] as $key=>$value) {

                        if( isset($value['children']) && !empty($value['children'])) {

                            foreach( $value['children'] as $children_key=>$children_value ) {

                                if( $children_value['isDel'] ==0 ) {

                                    $dep_list[] = array(

                                        'dep_id' => $children_value['id'],
                                        'name'   => $children_value['name'],
                                        'prev_dep_name' => $value['name'],
                                    );
                                }
                            }

                        }
                    }
                    $this->rediss->setData('COMPANY_ALL_DEPTM',json_encode($dep_list) );
                    return $dep_list;

                }else{

                    return [];
                }
            }

        }else{

             return json_decode( $user_list,True);
        }
    }

    /**
     * 获取用户的职位信息
     * @param :   array $user_caff   用户的工号
     * @return array
     **/
    public function get_user_job($user_caff)
    {
        $user_caff =(array_unique( $user_caff));
        asort($user_caff);
        $redis_key = gzcompress(implode(",",$user_caff));
        $user_list = $this->rediss->getData('USER_JOB_'.$redis_key);
        if( empty($user_list) ) {

            $header = array('Content-Type: application/json');
            $this->load->config('api_config', FALSE, TRUE);
            if( !empty($this->config->item('java_system_oa')) ) {
                $sytem_items = $this->config->item('java_system_oa');
                $get_oa_url = isset( $sytem_items['get_user_job'] )? $sytem_items['get_user_job']."?access_token=". getOASystemAccessToken():'';
                if( is_array($user_caff))
                {
                    $user_caff = implode(",",$user_caff);
                }
                $result = getCurlData($get_oa_url,json_encode(["userNumber"=>$user_caff]),'post',$header);
                $result = json_decode( $result,True);
                if( !empty($result) && $result['code']==200 && $result['data']) {

                    $this->rediss->setData('USER_JOB_'.$redis_key,json_encode($result['data']) );
                    return $result['data'];
                }else{
                    return [];
                }
            }
        }else{
            return json_decode( $user_list,True);
        }
    }
}