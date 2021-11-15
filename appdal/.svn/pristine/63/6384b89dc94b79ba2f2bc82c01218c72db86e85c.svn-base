<?php
/**
 * Created by PhpStorm.
 * 删除相关表日志记录操作
 * User: Jolon
 * Date: 2020/03/06 11:17
 */

class Remove_logs_model extends Purchase_model{

    public function __construct(){
        parent::__construct();
    }

    /**
     * 删除 阿里消息记录日志表的记录 api_request_ali_log
     * @author Jolon
     * @param int $limit
     * @param string $type
     * @return array
     */
    public function delete_ali_request_invalid_log($limit = 100,$type = 'graceful'){
        $times = 10;

        if($type != 'graceful'){
            $count_1 = $this->purchase_db->select("count(1) as count")
                ->get('api_request_ali_log')
                ->row_array();
            $count = $count_1['count'];
            $times_1 = 0;

            if($count > 500000){
                $out_of_range = $count - 500000;
                $remainder    = $out_of_range % $limit;
                $times_1      = ceil($out_of_range / $limit);
                if($remainder > 0) $times_1--;
                $times_1 = ($times_1 > $times) ? $times : $times_1;

                for($i = 0;$i < $times_1 ;$i ++){
                    $this->purchase_db->delete('api_request_ali_log','1=1',$limit);
                }
            }

            $data = [
                'count_1'     => $count_1['count'],
                'del_count_1' => $limit * $times_1,
            ];
            return $data;

        }else{
            // 删除请求记录，只保留一天
            $last_date = date('Y-m-d H:i:s',strtotime(' -3 days'));

            $count_1 = $this->purchase_db->select("count(1) as count")
                ->where("record_number='AliBaseApi' AND create_time<'$last_date'")
                ->get('api_request_ali_log')
                ->row_array();
            $times_1 = ceil($count_1['count'] / $limit);
            $times_1 = ($times_1 > $times)? $times:$times_1;

            for($i = 0;$i < $times_1 ;$i ++){
                $this->purchase_db->delete('api_request_ali_log',"record_number='AliBaseApi' AND create_time<'$last_date'",$limit);
            }


            // 删除错误日志记录，只保留7天
            $last_date = date('Y-m-d H:i:s',strtotime(' -7 days'));

            $count_2 = $this->purchase_db->select("count(1) as count")
                ->where("record_number NOT IN('AliBaseApi','JAVA_MSG_FROM_ALI') AND create_time<'$last_date'")
                ->get('api_request_ali_log')
                ->row_array();
            $times_2 = ceil($count_2['count'] / $limit);
            $times_2 = ($times_2 > $times)? $times:$times_2;

            for($i = 0;$i < $times_2 ;$i ++){
                $this->purchase_db->delete('api_request_ali_log',"record_number NOT IN('AliBaseApi','JAVA_MSG_FROM_ALI') AND create_time<'$last_date'",$limit);
            }


            // 删除执行成的 1688推送的消息，只保留 3天
            $last_date = date('Y-m-d H:i:s',strtotime(' -3 days'));

            $count_3 = $this->purchase_db->select("count(1) as count")
                ->where("record_number='JAVA_MSG_FROM_ALI' AND status=3 AND create_time<'$last_date'")
                ->get('api_request_ali_log')
                ->row_array();
            $times_3 = ceil($count_3['count'] / $limit);
            $times_3 = ($times_3 > $times)? $times:$times_3;

            for($i = 0;$i < $times_3 ;$i ++){
                $this->purchase_db->delete('api_request_ali_log',"record_number='JAVA_MSG_FROM_ALI' AND status=3 AND create_time<'$last_date'",$limit);
            }

            $data = [
                'count_1'     => $count_1['count'],
                'count_2'     => $count_2['count'],
                'count_3'     => $count_3['count'],
                'del_count_1' => $limit * $times_1,
                'del_count_2' => $limit * $times_2,
                'del_count_3' => $limit * $times_3,
            ];

            return $data;
        }
    }




}