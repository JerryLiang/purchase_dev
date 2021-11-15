<?php
/**
 * 审核金额配置模型类
 * User: Jaxton
 * Date: 2019/03/13 16:23
 */

class Audit_amount_model extends Purchase_model {
	private $success=FALSE;
	private $error_msg='';

	protected $table_name = 'audit_amount';//合同表

    public function __construct(){
        parent::__construct();

        $this->load->helper('user');
        $this->config->load('key_name', FALSE, TRUE);
    }

    /**
    * 需查询显示的字段
    */
    private $select_field='id,auth_name,headman_start,headman_end,director_start,director_end,
    						manager_start,manager_end,majordomo,
    						update_time,deputy_manager_start,deputy_manager_end,line';

    /**
    * 获取列表数据
    * @param $offset,$limit,$page
    * @return array   
    * @author Jaxton 2019/03/13
    */
    public function get_list($offset,$limit,$page=1){
    	$this->purchase_db->select($this->select_field)->from($this->table_name);
    	$this->purchase_db->where_in('id',[2,5]);
    	$clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        $this->purchase_db=$clone_db;
        $result=$this->purchase_db->order_by('id')->limit($limit,$offset)->get()->result_array();
        $return_data = [
            'data_list'   => [
                'value' => $result,
                'key' => ['权限','业务线','组长审核金额区间','主管审核金额区间','副经理审核金额区间','经理审核金额区间','总监审核金额区间','修改时间'
                        ],
                
            ],
            'page_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit,
                'pages'     => ceil($total/$limit)
            ]
        ];
        return $return_data;
    }

    /**
    * 需验证是否有修改的字段
    */
    private $validate_field=[
    	'headman_start',
    	'headman_end',
    	'director_start',
    	'director_end',
    	'manager_start',
    	'manager_end',
    	'majordomo',
        'deputy_manager_start',
        'deputy_manager_end',
    ];

    /**
    * 修改金额
    * @param $data
    * @return array   
    * @author Jaxton 2019/03/13
    */
    public function update_amount($data){
    	$this->purchase_db->trans_begin();
    	try{
    		if(!empty($data)){
	    		//$data=json_decode($data,TRUE);
	    		foreach($data as $key => $val){
	    			//右边值需大于左边值 
	    			if($val['headman_start']>=$val['headman_end']){
	    				throw new Exception($val['auth_name'].'-组长审核金额区间填写有误');
	    			}
	    			if($val['director_start']>=$val['director_end']){
	    				throw new Exception($val['auth_name'].'-主管审核金额区间填写有误');
	    			}
	    			if($val['manager_start']>=$val['manager_end']){
	    				throw new Exception($val['auth_name'].'-经理审核金额区间填写有误');
	    			}

                    if($val['deputy_manager_start']>=$val['deputy_manager_end']){
                        throw new Exception($val['auth_name'].'-副经理审核金额区间填写有误');
                    }


                    //组长审核金额区间＜主管审核金额区间＜经理审核金额区间＜总监金额
	    			//主管左边区间数值要等于组长右边区间数值，经理左边区间数值等于主管右边区间数值
	    			if($val['director_start']!=$val['headman_end']){
	    				throw new Exception($val['auth_name'].'-主管左边区间数值要等于组长右边区间数值');
	    			}
                    if($val['deputy_manager_start']!=$val['director_end']){
                        throw new Exception($val['auth_name'].'-副经理左边区间数值要等于主管右边区间数值');
                    }
	    			if($val['manager_start']!=$val['deputy_manager_end']){
	    				throw new Exception($val['auth_name'].'-经理左边区间数值要等于副经理右边区间数值');
	    			}
	    			if($val['majordomo']!=$val['manager_end']){
	    				throw new Exception($val['auth_name'].'-总监区间数值要等于经理右边区间数值');
	    			}
	    			$old_info=$this->get_old_info_by_id($val['id']);
	    			if($old_info){
	    				//判断是否有编辑
	    				$flag=0;
                        $mod_data=[];
	    				foreach($this->validate_field as $field){
	    					if($old_info[$field]!=$val[$field]){
	    						$mod_data[$field]=$val[$field];
	    						$mod_data['old_'.$field]=$old_info[$field];

	    						$flag++;
	    					}
	    				}
	    				if($flag){//有变动
	    					$mod_data['update_time']=date('Y-m-d H:i:s');
    						$mod_data['update_user_id']=getActiveUserId();
    						$mod_data['update_user_name']=getActiveUserName();
	    				}
	    				if(!empty($mod_data)){
			    			$this->purchase_db->where(['id'=>$val['id']])->update($this->table_name,$mod_data);
			    			$log_data=[
                                'id'=>$val['id'],
                                'type'=>$this->table_name,
                                'content'=>'修改金额',
                                'detail'=>json_encode($mod_data)
                            ];
			    			operatorLogInsert($log_data);
			    		}
	    			}
	    		}
	    		$this->purchase_db->trans_commit();
	    		//print_r($mod_data);die;
	    		$this->success=TRUE;
	    	}else{
	    		throw new Exception('参数[data]为空');
	    	}
    	}catch(Exception $e){
    		$this->purchase_db->trans_rollback();
    		$this->error_msg.=$e->getMessage();
    	}
    	return [
    		'success'=>$this->success,
    		'error_msg'=>$this->error_msg
    	];
    }

    /**
    * 用ID获取一条记录
    * @param $id
    * @return array   
    * @author Jaxton 2019/03/13
    */
    public function get_old_info_by_id($id){
    	$row=$this->purchase_db->select('*')->from($this->table_name)
    	->where('id',$id)
    	->get()->row_array();
    	if($row){
    		return $row;
    	}else{
    		return FALSE;
    	}
    }

    /**
    * 获取金额审核权限
    */
    public function get_amount_auth(){
    	
    }
}