<?php

 class TransReqDataBF0040002{
		 
		 public $values = array('trans_batchid' => '宝付批次号', 
		 'trans_no' => '商户订单号');
		 
		 
		 
		// __get()方法用来获取私有属性	
		public function _get($property_name)
		{
			
			if (isset($this->values[$property_name])) {  //判断一下
				return $this->values[$property_name];
			} else {
				//echo '没有此属性！['.$property_name.']<br>';
			} 
				
		}
		// __set()方法用来设置私有属性
		public function _set($property_name, $value)
		{
			
			if (isset($this->values[$property_name])) {  //这里也判断一下
				$this->values[$property_name] = $this->validate($value);
			} else {
				//echo '没有此属性！['.$property_name.']<br>';
			} 
		}
		
		public function _getValues(){
			//var_dump($this->values);
			return $this->values;
		}
		
		private function validate($value){
			return htmlspecialchars(addslashes($value));
        //等等
		} 
	}


 ?>