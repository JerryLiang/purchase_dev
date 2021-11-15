<?php
/**
 * 发票清单模型类
 * User: Jaxton
 * Date: 2019/01/10 18:06
 */

class Invoice_list_model extends Purchase_model {
	protected $table_name = 'purchase_invoice_list';//发票清单表
	protected $declare_customs_table = 'declare_customs';//报关详情

    public function __construct(){
        parent::__construct();
        $this->load->model('product/product_model');
        $this->load->model('purchase_order_tax_model');
        $this->load->helper('user');
    }

    /**
    * 获取单条发票清单信息
    * @param $invoice_number
    * @return array   
    * @author Jaxton 2019/01/12
    */
    public function get_one_invoice($invoice_number){
    	$row=$this->purchase_db->select('*')
    	->from($this->table_name)
    	->where('invoice_number',$invoice_number)
    	->get()
    	->row_array();
    	if(!empty($row)){
    		return $row;
    	}else{
    		return false;
    	}
    }
    /**
    *获取多条清单详情
    * @param $invoice_number_arr
    * @return array   
    * @author Jaxton 2019/01/12
    */
    public function get_many_invoice_detail($invoice_number_arr,$limit=null,$offset=null){
    	$this->purchase_db->select('*')
    	->from($this->table_name.' a')
    	->join($this->declare_customs_table.' b','a.invoice_number=b.invoice_number')
    	->where_in('a.invoice_number',$invoice_number_arr);
        if($limit && $offset){
            $this->purchase_db->limit($limit,$offset);
        }
        //$clone_db = clone($this->purchase_db);
        //$total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        //$this->purchase_db=$clone_db;
    	$list=$this->purchase_db->get()
    	->result_array();
    	return [
            'list' =>$list,
            // 'total' => $total
        ];
    }

    /**
    * 获取发票清单sku
    * @param $params
    * @return array   
    * @author Jaxton 2019/01/11
    */
    public function get_invoice_detail_list($invoice_number,$limit,$offset){
    	$this->load->model('purchase_order_model');
        $this->load->model('purchase_order_items_model');
        $error_msg='';
    	$success=false;
    	$invoice_data=[];
    	if(!empty($invoice_number)){
    		$invoice_number_arr=explode(',', $invoice_number);
    		$list=$this->get_many_invoice_detail($invoice_number_arr,$limit,$offset);
	    	//print_r($list);die;
	    	if(!empty($list['list'])){
                $skus = array_unique(array_column(isset($list['list'])?$list['list']:[], 'sku'));
                $product_field = 'sku,customs_code,declare_cname,declare_unit,export_cname';
                $sku_arr = $this->product_model->get_list_by_sku($skus,$product_field);
	    		foreach($list['list'] as $k => $v){
                                $taxes=$invoice_amount=$uumber_invoices=0;
	    			$order_items_info = $this->purchase_order_items_model->get_item($v['purchase_number'],$v['sku'],true);
                                $uumber_invoices=format_price($v['customs_quantity']*$order_items_info['purchase_unit_price']);  //已开票金额
                                $invoice_amount=format_price($uumber_invoices/1.13);//发票金额
                                $taxes=format_price($uumber_invoices-$invoice_amount);//税金
                                //开票单位中含有2个或者以上单位时，生成的开票合同中的开票单位需要删除千克
                                $declare_unit = isset($v['customs_unit']) ? $v['customs_unit'] : '';
                                $new_declare_unit = $this->purchase_order_tax_model->get_new_declare_unit($declare_unit);
                                
                    if(isset($sku_arr[$v['sku']]) && !empty($v['purchase_number']))
                    {
                        $query = $this->purchase_db->from("purchase_order_items")->where("purchase_number",$v['purchase_number'])->where("sku",$v['sku']);
                        $vats = $query->select("coupon_rate")->get()->row_array();
                        $vat_tax_price = $vats['coupon_rate'];
                    }else{
                        $vat_tax_price = NULL;
                    }
	    			$invoice_data[$v['invoice_number']][]=[
	    				'invoice_number'=>$v['invoice_number'],
	    				'sku'=>$v['sku'],
	    				'customs_name'=>$v['customs_name'],
	    				'product_name'=>$order_items_info['product_name'],
	    				'purchase_number'=>$v['purchase_number'],
	    				'supplier_name'=>$v['supplier_name'],
	    				'customs_number'=>$v['customs_number'],
                                        'buyer_name'=>$v['purchase_user_name'],
                                        'order_time'=>$order_items_info['create_time'],
	    				'customs_quantity'=>$v['customs_quantity'],
	    				'unit_price'=>$order_items_info['purchase_unit_price'],
                        'uumber_invoices'=> empty($v['uumber_invoices'])?$v['customs_quantity']:$v['uumber_invoices'],//已开票数量
                        'vat_tax_rate'   =>$vat_tax_price,//增值税税率
                        'invoiced_amount'=> empty($v['invoiced_amount'])?$uumber_invoices:$v['invoiced_amount'],//已开票金额
                        'invoice_amount' => empty($v['invoice_amount'])?$invoice_amount:$v['invoice_amount'],//发票金额
                        'taxes'          => empty($v['taxes'])?$taxes:$v['taxes'],  //税金
	    				'total_price'=>round($v['customs_quantity']*$order_items_info['purchase_unit_price'],3),
	    				'customs_type'=>$v['customs_type'],
	    				'customs_unit'=>$v['customs_unit'],
                                        'demand_number'=>$v['demand_number'],
                                        'export_cname'=>isset($sku_arr[$v['sku']])?$sku_arr[$v['sku']]['export_cname']:'',
                                        'customs_code'=>isset($sku_arr[$v['sku']])?$sku_arr[$v['sku']]['customs_code']:'',
                                        'customs_unit'=>$new_declare_unit,
	    				'invoice_code_left'=>$v['invoice_code_left'],
	    				'invoice_code_right'=>$v['invoice_code_right']
	    			];
	    		}
                $success=true;
	    	}else{
                $error_msg.='所选发票清单未获取到sku数据';
            }
	    	
    	}else{
    		$error_msg.='请选择发票清单';
    	}
        $return_data=[
            'success'=>$success,
            'error_msg'=>$error_msg,
            'data'=>[]
        ];
        if($success){
            $return_data['data']=[
                'value'=>array_merge($invoice_data),
                'key'=>[
                    'SKU','产品名称','开票品名','采购单号','供应商名称','报关单号','出口海关编码','报关品名','报关数量','含税单价','开票数量','票面税率','已开票金额','发票金额','税金','总金额','报关型号','报关单位','发票代码(左)','发票号码(右)'
                ]
            ];
            $return_data['page_data'] = [
                'page_data' => [
                    'total'     => count($invoice_data),
                    'offset'    => $offset,
                    'limit'     => $limit,
           
                ]
            ];
        }
        return $return_data;
    	
    }

    
    /**
    * 批量开票提交
    * @param $params
    * @return array   
    * @author Jaxton 2019/01/11
    */
    public function btach_invoice_submit($invoice_code_data){
    	$success=true;
    	$error_msg='';
    	if(!empty($invoice_code_data)){
            $purchase_code_data = json_decode( $invoice_code_data,True);
    		$invoice_code_data=json_decode($invoice_code_data);
//    		pr($invoice_code_data);die;

    		$this->purchase_db->trans_begin();
            try{
                foreach($invoice_code_data as $key => $val){
                    $flag=0;
                    foreach($val as $k => $v){
                        if($v->invoice_code_left=='' || $v->invoice_code_right==''){
                            $error_msg.='发票清单:'.$key.'-'.$k.'发票代码未填写完全';
                            $success=false;
                            $flag++;
                            continue;
                        }
                        if ($v->vat_tax_rate>1 || $v->vat_tax_rate<0){
                            $error_msg.='发票清单:'.$key.'-'.$k.'填写有误,增值税率可修改范围: 0≤增值税税率≤1';
                            $success=false;
                            $flag++;
                            continue;
                        }
                    }
                    //单条清单完全填写完整才继续操作
                    if($flag==0){
                        foreach($val as $k => $v){
                            $edit_data=[
                                'invoice_code_left'=>$v->invoice_code_left,
                                'invoice_code_right'=>$v->invoice_code_right,
                                'uumber_invoices'   =>$v->uumber_invoices,
                                'invoiced_amount'   =>$v->invoiced_amount,
                                'invoice_amount'    =>$v->invoice_amount,
                                'taxes'             =>$v->taxes,
                                'vat_tax_rate'      =>$v->vat_tax_rate, //增值税税率
                            ];

                            $update_data = array(
                                'coupon_rate' => $v->vat_tax_rate,
                            );
                            $this->purchase_db->update('purchase_order_items',$update_data,['sku'=>$k,'purchase_number'=>$v->purchase_number]);
                            $edit_result=$this->purchase_db->where(['invoice_number'=>$key,'sku'=>$k])->update($this->declare_customs_table,$edit_data);
                            if($edit_result){
                                //存记录

                                $this->load->model('reject_note_model');
                                $log_data=[
                                    'record_number'=>$key,
                                    'record_type'=>'批量开票',
                                    'content'=>'批量开票',
                                    'content_detail'=>$v->invoice_code_left.'-'.$v->invoice_code_right
                                ];
                                $this->reject_note_model->get_insert_log($log_data);
                            }
                            
                        }
                        //改状态
                        $this->purchase_db->where('invoice_number',$key)->update($this->table_name,['states'=>3]);

                        $this->purchase_db->trans_commit();

                        
                    }
                }
            }catch(Exception $e){
                $this->purchase_db->trans_rollback();
                //$error_msg.='发票清单:'.$key.'-开票失败';
                $error_msg.=$e->getMessage();
                $success=false;
            }
    		
    		return [
    			'success'=>$success,
    			'error_msg'=>$error_msg
    		];
    	}else{
    		return [
    			'success'=>false,
    			'error_msg'=>'缺少必填数据'
    		];
    		
    	}
    }
    /**
    * 财务审核
    * @param $invoice_number
    * @param $remark
    * @param $review_result
    * @return array   
    * @author Jaxton 2019/01/12
    */
    public function invoice_finance_review($invoice_number,$remark,$review_result){
        $this->load->model('declare_customs_model');
    	$success=true;
    	$error_msg='';
    	if(!empty($invoice_number) && !empty($review_result)){
    		if(in_array($review_result, [1,2])){
    			$edit_data=[
	    			'auditor_remark'=>$remark
	    		];
	    		if($review_result==1){//通过
    				$edit_data['states']=4;
                    $edit_data['auditor_user'] = getActiveUserName();
                    $edit_data['auditor_time'] = date('Y-m-d H:i:s');
    				$review_str='通过';
    			}else{//驳回
    				$edit_data['states']=5;
                    $edit_data['auditor_user'] = getActiveUserName();
                    $edit_data['auditor_time'] = date('Y-m-d H:i:s');
    				$review_str='驳回';
    			}
    			$is_invoice_number=$this->get_one_invoice($invoice_number);
    			if(!$is_invoice_number){
    				$error_msg.='此发票清单号不存在';
    				$success=false;
    			}else{
    				if($is_invoice_number['states']==3){
    					$this->purchase_db->trans_begin();
                        try{
                            $edit_result=$this->purchase_db->where('invoice_number',$invoice_number)->update($this->table_name,$edit_data);
                            //审核通过，判断该发票清单下的采购单号数据是否已经完结
                            if($edit_result && $review_result==1){
                                $this->declare_customs_model->check_purchase_order_is_end(array($invoice_number));
                            }
                            //存记录
                            $this->load->model('reject_note_model');
                            $log_data=[
                                'record_number'=>$invoice_number,
                                'record_type'=>'审核发票清单',
                                'content'=>'审核'.$review_str,
                                'content_detail'=>isset($remark)?$remark:'',
                            ];
                            $this->reject_note_model->get_insert_log($log_data);
                            $this->purchase_db->trans_commit();
                        }catch(Exception $e){

                            $this->purchase_db->trans_rollback();

                            $error_msg.='发票清单:'.$invoice_number.'-审核失败';
                            $success=false;
                        }
		    							       
    				}else{
    					$error_msg.='此发票清单不是待审核状态';
    					$success=false;
    				}
    				
    			}
    			
    		}else{
    			$error_msg.='审核类型错误，请联系开发人员';
    			$success=false;
    		}
    		
    		
    	}else{
    		$success=false;
    	}
    	return [
    		'success'=>$success,
    		'error_msg'=>$error_msg
    	];
    }

    /**
    * 获取下载发票明细数据
    * @param $invoice_number    
    * @return array   
    * @author Jaxton 2019/01/12
    */
    public function get_download_invoice_detail($invoice_number){
    	$this->load->model('purchase_order_items_model');
    	$error_msg='';
    	$success=false;
    	$invoice_number_arr=explode(',', $invoice_number);
    	$invoice_detail=[];
    	if(!empty($invoice_number_arr)){
    		$all_list=$this->get_many_invoice_detail($invoice_number_arr)['list'];
    		//return $all_list;
    		
    		if(!empty($all_list)){
    			foreach($all_list as $key => $v){
    				$order_items_info = $this->purchase_order_items_model->get_item($v['purchase_number'],$v['sku'],true);
    				$invoice_detail[$key]=[
    					'invoice_number'=>$v['invoice_number'],
	    				'sku'=>$v['sku'],
	    				'customs_name'=>$v['customs_name'],
	    				'product_name'=>$order_items_info['product_name'],
	    				'purchase_number'=>$v['purchase_number'],
	    				'supplier_name'=>$v['supplier_name'],
	    				'customs_number'=>$v['customs_number'],
	    				'customs_quantity'=>$v['customs_quantity'],
	    				'unit_price'=>$v['unit_price'],
	    				'total_price'=>$v['customs_quantity']*$v['unit_price'],
	    				'customs_type'=>$v['customs_type'],
	    				'customs_unit'=>$v['customs_unit'],
	    				'invoice_code_left'=>$v['invoice_code_left'],
	    				'invoice_code_right'=>$v['invoice_code_right']
    				];
    			}
    		}
    		$success=true;
    	}else{
    		$error_msg.='请选择发票清单';
    	}
    	return [
    		'success'=>$success,
    		'error_msg'=>$error_msg,
    		'data'=>$invoice_detail
    	];
    }
    
}