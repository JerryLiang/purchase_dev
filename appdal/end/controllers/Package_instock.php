<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
include_once APPPATH . "core/MY_API_Controller.php";

/**
 * 包材入库
 * Class Package_instock
 * @author yefanli
 */
class Package_instock extends MY_API_Controller
{
    /**
     * Package_instock constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Package_instock_model');
    }

    public function sendData()
    {
        $data = [
            [
                "purchase_number" =>"PO10000274",
                "sku"=>"1011200429411",
                "express_no"=>"",
                "instock_batch"=>"pk15121554",
                "arrival_date"=>"2021-04-19 00:00:00",
                "delivery_user"=>"张三",
                "instock_qty"=>20,
                "instock_type"=>0,"arrival_qty"=>20,"instock_date"=>"2021-04-19 00:00:00","qurchase_num"=>0,
                "quality_level_num"=>0,"receipt_number"=>0,"instock_user_name"=>"张三","defective_num"=>0]];
        $post_data = [
            "data" => json_encode($data)
        ];
        $post_data = '{"data":[{"instock_date":1619429502657,"arrival_qty":200,"purchase_number":"PO10000469","sku":"QT00499-13","instock_user_name":"陈佼","instock_qty":200}]}';
        $post_data = json_decode($post_data);
        $header = [];
        $url = "otest01.com/Package_instock/package_instock";
        $result = getCurlData($url,json_encode($post_data),'post',$header);
        echo $result;
    }

    /**
     * 包材信息入库
     */
    public function package_instock()
    {
        $res = [
            "status" => 0,
            "data_list" => [
                "success" => [],
                "error" => [],
            ],
            "errorMess" => "默认推送失败"
        ];

        $params = $this->get_instock_params();
        if(is_string($params)){
            $res['errorMess'] = $params;
            exit(json_encode($res));
        };

        $result = $this->Package_instock_model->package_instock($params);

        if(isset($result['code']) && $result['code'] == 1){
            $res['status'] = 1;
            $res['errorMess'] = '推送成功';
        }else if(isset($result['msg'])){
            $res['errorMess'] = $result['msg'];
        }else{
            $res['errorMess'] = '处理失败！';
        }
        $res = json_encode($res);
        exit($res);
    }


    /**
     * 获取传入的包材数据
     * se/e 不为空，n数字，s已设置，!s未设置，nn不为null
     */
    private function get_instock_params()
    {
        $filed_must = [
            "purchase_number", "sku", "arrival_qty", "instock_date", "instock_qty"
        ];
        $this_time = ["instock_date", "upper_end_time"];
        $filed = [
            "purchase_number" => "varchar",  //  varchar     采购单号
            "sku" => "varchar",   //  varchar     产品sku
            "express_no" => "varchar",   //  varchar     快递单号
            "deliery_batch" => "varchar",   //  varchar     发货批次号
            "instock_batch" => "varchar",   //  varchar     入库批次
            "purchase_qty" => "int",   //  int 采购数量
            "arrival_qty" => "int",   //  int 到货数量
            "qurchase_num" => "int",   //  int 应到数量
            "quality_level_num" => "int",   //  int 抽检数量
            "quality_level" => "varchar",   //  varchar     IQC质检级别
            "defective_num" => "int",   //  int 次品数量
            "defective_type" => "varchar",   //  varchar     次品类型
            "storage_position" => "varchar",   //  varchar     入库货位
            "defective_position" => "varchar",   //  varchar     次品货位
            "quality_username" => "varchar",   //  varchar     质检人
            "quality_all" => "int",   //  tinyint 是否全检，1：否，2：是
            "quality_time" => "datetime",   //  datetime     质检时间
            "quality_result" => "int",   //  tinyint 质检结果 1合格，2不合格
            "quality_all_time" => "datetime",   //  datetime     全检时间
            "upper_end_time" => "datetime",   //  datetime     上架完成时间
            "count_time" => "datetime",   //  datetime     点数时间
            "delevery_time_long" => "int",   //  int 时效，时效开始时间到上架完成时间的时间间隔，单位h
            "arrival_date" => "datetime",   //  datetime     到货日期
            "delivery_user" => "varchar",   //  varchar     收货人
            "bad_qty" => "int",   //  int 不良品数量
            "breakage_qty" => "int",   //  int 报损数量
            "instock_qty" => "int",   //  int 入库数量
            "receipt_number" => "varchar",   //  varchar     仓库入库单号
            "instock_user_name" => "varchar",   //  varchar     入库人
            "instock_type" => "int",   //  int 入库类型
            "instock_date" => "datetime",   //  datetime     入库时间
            "check_qty" => "int",   //  int 品检数量
            "check_type" => "varchar",   //  varchar     品检类型
            "receipt_id" => "varchar",   //  varchar     仓库返回的唯一串
            "instock_platform" => "int",   //  int 对应需求数
            "instock_total" => "varchar",   //  varchar     总入库平台项目
            "qc_id" => "varchar",   //  varchar     仓库传过来的唯一标识（数据唯一标识）
            "instock_qty_more" => "int",   //  int  多货数量
            "paste_labeled" => "int",   //  tinyint 是否贴码（1是 2否）
            "transfer_numbers" => "int",   //  int  中转仓数量
            "traight_numbers" => "int",   //  int   直发数
            "old_instock_batch" => "varchar",   //  varchar  原入库批次号
            "abnormal_order" => "varchar",   //  varchar  关联异常单号
            "paste_code_time" => "datetime",   //  datetime     贴码时间
            "iqc_quality_testing" => "int",   //  int     是否IQC质检：0否，1是
            "count_number" => "int",   //  int   点数数量
            "count_user" => "varchar",   //  varchar  点数操作员
            "count_job_number" => "varchar",   //  varchar     点数操作工位
            "arrival_job_user" => "varchar",   //  varchar    到货操作员
            "paste_code_user" => "varchar",   //  varchar  贴码人
            "paste_code_qty" => "int",   //  int 贴码数量
            "instock_system" => "varchar",   //  varchar  入库系统
            "is_accumulation" => "int",   //  int 是否累加数量：0否，1是
            "good_qty" => "int",   //  int 优品数量
            "upper_num" => "int",   //  int   上架数量
            "delivery_note" => "int",   //  tinyint  送货单枚举 1=与送货单相符;2=与送货单不符;3=无送货单
        ];

        $params = [];
        $param_tmp = json_decode(file_get_contents('php://input'), true);
        $param = [];
        try{
            if(isset($param_tmp['data']) && !is_array($param_tmp['data'])){
                $param = json_decode($param_tmp['data'], true);
            }if(is_array($param_tmp['data'])){
                $param = $param_tmp['data'];
            }

            if(!is_array($param) || count($param) == 0) return "提交数据不能为空!";
            if(count($param) > 100)return "每次最多推送100条!";
        }catch (Exception $e){}

        foreach ($param as $val){
            $row = [];
            foreach ($filed as $k=>$v){
                $type = [
                    "int"   => 0,
                    "varchar" => "",
                    "datetime"=> "0000-00-00 00:00:00"
                ];
                if(SetAndNotEmpty($val, $k))$row[$k] = $val[$k];
                if(in_array($k, $this_time))$row[$k] = date("Y-m-d H:i:s");
                if(in_array($k, $filed_must) && !SetAndNotEmpty($val, $k))return "必要数据：".implode(",", $filed_must)."，不能为空！";
            }
            if(empty($row['instock_batch']))$row['instock_batch'] = 'BC'.date('mdH').rand(1000, 9999);
            $params[] = $row;
        }
        return $params;
    }

    /**
     * 删除特定时间之前的日志文件 只留 7 日内的api请求日志
     */
    public function delete_log_by_date()
    {
        $file = [
            "api_request_log_",
            "batch_audit_order_save_",
        ];
        $start = 5; // 保留天数
        $max_data = $start + 30; // 删除天数
        foreach ($file as $val){
            for($i=$start;$i< $max_data; $i++){
                $da = "-{$i} days";
                $tem = APPPATH . 'logs/'.$val. date('Ymd',strtotime($da));
                $val_txt =  $tem.'.txt';
                if(file_exists($val_txt)){
                    unlink($val_txt);
                }
                $val_log =  $tem.'.log';
                if(file_exists($val_log)){
                    unlink($val_log);
                }
                $tem_h = APPPATH . 'logs/'.$val. date('YmdH',strtotime($da));
                $val_txt_h =  $tem_h.'.txt';
                if(file_exists($val_txt_h)){
                    unlink($val_txt_h);
                }
                $val_log_h =  $tem_h.'.log';
                if(file_exists($val_log_h)){
                    unlink($val_log_h);
                }
            }
        }
    }
}