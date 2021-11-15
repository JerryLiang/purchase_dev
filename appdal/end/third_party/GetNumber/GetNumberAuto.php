<?php
/**
 * @author Jacky
 * @desc 自动取号类
 * @since 2018/09/23
 */

class GetNumberAuto{
	private static $_db = null;
	
	const INCREATE_TYPE_DEF = 0;
	
	const INCREATE_TYPE_MON = 1;
	
	const INCREATE_TYPE_DAY = 2;
	
	const INCREATE_TYPE_HOUR = 3;
	
	public function __construct(){
		$ci = &get_instance();
		self::$_db = $ci->db;
	}
	/**
	 * @desc 自动取号
	 * @param String $type
	 * @return String $formate
	 */
	public static function getCode( $codeType='',$appType='' ) {
		$info = self::getByCodeType($codeType,$appType);
		$formate = $info['code_format'];
		$datetime = date('Y-m-d H:i:s');
		$datetime = str_replace(array(' ', ':'), array('-','-'), $datetime);
		$timeArr = explode("-", $datetime);
		$timeArr[0] = substr($timeArr[0], 2, 2);
		$search = array('{Y}','{M}','{D}','{H}','{prefix}', '{suffix}');
		$replace = array($timeArr[0], $timeArr[1], $timeArr[2], $timeArr[3],$info['code_prefix'], $info['code_suffix']);
		$formate = str_replace($search, $replace, $formate);
		$reset = self::reset($timeArr, $info['code_increate_type'], $info['code_increate_tag']);
		
		if (! empty($info['code_fix_length']) ) { //指定编码长度
			$numLen = $info['code_fix_length'] + 5 - strlen($formate);
			if ( $numLen < 1 ) {
				throw new Error('Fixed length setting is not enough.');
			}
			if ( empty($info['code_increase_num']) || $reset ) { //归为最小值
				$num = $info['code_min_num'];
			} else { //递增
				$num = $info['code_increase_num'] + 1;
				if ( strlen($num) > $numLen ) {
					throw new Error('Value is beyond the fixed length.');
				}
			}
			if (! empty( $info['code_max_num']) && $num > $info['code_max_num']) {
				throw new Error('Error in getting number.');
			}
			$codeNum = str_pad($num, $numLen, '0', STR_PAD_LEFT);
		} else { //未指定编码长度
			if ( empty($info['code_increase_num']) || $reset) { //归为最小值
				$num = $info['code_min_num'];
			} else { //递增
				$num = $info['code_increase_num'] + 1;
			}
			if ( ! empty( $info['code_max_num']) &&$num > $info['code_max_num']) {
				throw new Error('Error in getting number.');
			}
			$codeNum = $num;
		}
		$formate = str_replace('{num}', $codeNum,  $formate);
		
		//更新数据库
		$flag = self::setIncreateTagNum($info['id'],$num,self::getIncreateTag($timeArr,  $info['code_increate_type']));
		
		if (! $flag ) {
			return self::getCode($codeType,$appType);
		}
	
		return $formate;
	}
	
	/**
	 * @desc 根据取号类型获取取号规则
	 * @return Array $info
	 */
	public static function getByCodeType($codeType='',$appType='') {
		if( !$codeType || !$appType ) return null;
		self::$_db->select('*');
    	self::$_db->from('yibai_appdal.supr_auto_code');
    	self::$_db->where('code_type',$codeType);
    	self::$_db->where('app_type',$appType);
    	$data = self::$_db->get()->row_array();
    	/* echo $this->db->last_query();
    	 exit; */
		if ( empty($data) ) {
			throw new Error('No configuration code type information.');
		}
		return $data;
	}
	
	/**
	 * @desc 更新编号增长类型
	 */
	public static function setIncreateTagNum( $id='',$codeIincreaseInum='',$codeIncreateTag='' ){
		if(!$id) return false;
    	$data = array(
    			'code_increase_num'  => $codeIincreaseInum,
				'code_increate_tag'  => $codeIncreateTag,
    	);
    	self::$_db->where('id', $id);
    
    	return self::$_db->update('yibai_appdal.supr_auto_code', $data);
	}
	
	/**
	 * @desc 检查是否重置递增
	 */
	public static function reset($timeArr, $increateType, $increateTag) {
		switch ($increateType) {
			case self::INCREATE_TYPE_DEF;
			return false;
			break;
			case self::INCREATE_TYPE_MON:
				if ( $timeArr[1] == $increateTag ) {
					return false;
				}
				break;
			case self::INCREATE_TYPE_DAY :
				if ( $timeArr[2] == $increateTag ) {
					return false;
				}
				break;
			case self::INCREATE_TYPE_HOUR:
				if ( $timeArr[3] == $increateTag ) {
					return false;
				}
				break;
			default:
				throw new Error('Increate type error.');
		}
	
		return true;
	}
	
	/**
	 * @desc 获取自增标记
	 */
	public static function getIncreateTag($timeArr, $increateType) {
		switch ($increateType) {
			case self::INCREATE_TYPE_DEF;
			return null;
			break;
			case self::INCREATE_TYPE_MON:
				return $timeArr[1];
				break;
			case self::INCREATE_TYPE_DAY :
				return $timeArr[2];
				break;
			case self::INCREATE_TYPE_HOUR:
				return $timeArr[3];
				break;
			default:
				throw new Error('Increate type error.');
	
		}
	}
	
}

?>