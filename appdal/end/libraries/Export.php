<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/5/13
 * Time: 15:58
 */
//加载公共方法
$this->load->helper(['file_remote', 'export_csv', 'export_excel']);

class Export
{
    private $tmp_path;
    private $save_path;

    function __construct()
    {
        $this->tmp_path = 'download_csv' . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m')
            . DIRECTORY_SEPARATOR . date('d') . DIRECTORY_SEPARATOR;
        $this->save_path = dirname(dirname(APPPATH)) . DIRECTORY_SEPARATOR . 'webfront' . DIRECTORY_SEPARATOR . $this->tmp_path;
    }

    /**
     * 导出csv文件
     * 导出数据小于5万条，生成单个csv文件
     * 导出数据大于5万条，生成多个文件，压缩成一个zip格式压缩包
     * @param $file_name |文件名（不包含文件后缀）
     * @param $total_count |导出数据总条数
     * @param $column_data |表头数据
     *           array(
     *                 'purchase_number'=>'采购单号'
     *           )
     * @param $params |查询条件
     * @param object $model 模型名称
     * @param string $function 方法名称
     * @return string
     */
    function ExportCsv($file_name, $total_count, $column_data, $params, $model, $function,$MysqlObject = NULL)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        //服务器保存路径
        if (!file_exists($this->save_path)) {
            @mkdir($this->save_path, 0777, true);
            @chmod($this->save_path, 0777);
        }

        $limit = 100;                           //每次查询的条数
        $total_page = ceil($total_count / $limit);//总页数
        $limit_csv = 50000;                       //csv每页的数据量
        $count_csv = 0;                           //统计数据条数

        //如果系统未开启buffer则手动开启
        if(ob_get_level() == 0) ob_start();

        //表头中文编码转换
        foreach ($column_data as &$v) {
            $v = iconv("UTF-8", "GB2312//IGNORE", $v) . "\t";
        }
        //总条数超过$limit_csv时，分多个csv导出，并压缩打包
        if ($total_count > $limit_csv) {
            $file_id = 1;//文件序号
            $file_name_arr = array();
            //一页一页取出数据处理
            for ($page = 1; $page <= $total_page; $page++) {
                $offsets = ($page - 1) * $limit;
                //获取导出数据
                $result = call_user_func_array([$model, $function], array($params, $offsets, $limit, $page, true));
                $count_csv += count($result['values']);
                $file_tmp = $this->save_path . $file_name . '_' . $file_id . '.csv';//文件全路径及文件名
                //生成临时文件，写入表头
                if (!is_file($file_tmp)) {
                    $fp = fopen($file_tmp, 'w');
                    chmod($file_tmp, 0777);
                    fputcsv($fp, $column_data);//将数据通过fputcsv写到文件句柄
                }
                //使用生成器迭代
                $content_data = yieldData($result['values']);

                //数据写入文件
                writeCsvContent($fp, array_keys($column_data), $content_data, $offsets);
                //最后一页的数据，或者当数据条数等于$limit_csv时，生成一个文件
                if (($page == $total_page) OR ($count_csv % $limit_csv == 0)) {
                    $file_name_arr[] = $file_tmp;
                    ob_flush();//刷新一下输出buffer，防止由于数据过多造成问题
                    flush();
                    fclose($fp);   //每生成一个文件关闭
                    $file_id++;    //文件序号递增
                    $count_csv = 0;//统计csv文件条数归零
                    unset($fp);
                }
            }
            //进行多个文件压缩,并删除原文件
            $file_name = CreateZipFile($file_name_arr, $this->save_path . $file_name);
        } else {
            //导出单个csv文件
            $file_tmp = $this->save_path . $file_name . '.csv';//文件全路径及文件名

            $file_name = $file_name . '.csv';

            //生成文件，写入表头
            $fp = fopen($file_tmp, 'w');
            chmod($file_tmp, 0777);
            fputcsv($fp, $column_data);//将数据通过fputcsv写到文件句柄
            //一页一页取出数据处理
            for ($page = 1; $page <= $total_page; $page++) {
                $offsets = ($page - 1) * $limit;
                //获取导出数据
                $result = call_user_func_array([$model, $function], array($params, $offsets, $limit, $page, true));
                //使用生成器迭代
                $content_data = yieldData($result['values']);
                //数据写入文件
                writeCsvContent($fp, array_keys($column_data), $content_data, $offsets);
                if(ob_get_level()>0) {
                    ob_flush();//刷新一下输出buffer，防止由于数据过多造成问题
                    flush();
                }

//                // 如果是SWOOLE 导出，回写日志表
                if( isset($params['swoole']) && NULL != $MysqlObject){

                    if( ($page * $limit ) >= $total_count ){

                        $now_number = $total_count;
                    }else{
                        $now_number = $page * $limit;
                    }
                    $MysqlObject->data_center_model->updateCenterData($params['swoole']['id'], ['progress' => $now_number]);
                }
            }
            fclose($fp);//每生成一个文件关闭
            unset($fp);
        }
        //前端下载地址

        if( isset($params['swoole']) && NULL != $MysqlObject ){
            return $this->save_path . $file_name;
        }
        return CG_SYSTEM_WEB_FRONT_IP . $this->tmp_path . $file_name;
    }

    /**
     * 导出Excel，保存服务器
     * @param $file_name |文件名（不包含文件后缀）
     * @param $total_count |导出数据总条数
     * @param $column_data
     * @param $params
     * @param object $model 模型名称
     * @param string $function 方法名称
     * @param array $field_img_key 图片字段名称
     * @return string
     */
    function ExportExcel($file_name, $total_count, $column_data, $params, $model, $function, $field_img_key = [])
    {
        //服务器保存路径
        if (!file_exists($this->save_path)) @mkdir($this->save_path, 0777, true);

        $limit = 10000;                             //每次查询的条数
        $total_page = ceil($total_count / $limit);  //总页数
        $page_limit = 30000;                        //每页的数据量
        $data_count = 0;                            //统计已查询数据条数

        if ($total_count > $page_limit) {
            $file_id = 1;//文件序号
            $file_name_arr = array();
            //一页一页取出数据处理
            for ($page = 1; $page <= $total_page; $page++) {
                $offsets = ($page - 1) * $limit;
                //获取导出数据
                $result = call_user_func_array([$model, $function], array($params, $offsets, $limit, $page, true));
                $data_count += count($result['values']);
                $file_tmp = $this->save_path . $file_name . '_' . $file_id . '.xls';//文件全路径及文件名
                //生成临时文件，写入表头
                if (!is_file($file_tmp)) {
                    //导出图片时设置行高
                    if (!empty($field_img_key)) {
                        $setRowHeight = true;
                    } else {
                        $setRowHeight = false;
                    }
                    writeExcelHead($column_data['rows'], $file_tmp, $setRowHeight);
                }
                //使用生成器迭代
                $content_data = yieldData($result['values']);
                //数据写入文件
                writeExcelContent($content_data, $column_data['keys'], $file_tmp, $field_img_key, $offsets);
                //最后一页的数据，或者当数据条数等于$limit_csv时，生成一个文件
                if (($page == $total_page) OR ($data_count % $page_limit == 0)) {
                    $file_name_arr[] = $file_tmp;
                    $file_id++;     //文件序号递增
                    $data_count = 0;//统计文件条数归零
                    //写入页脚
                    writeExcelFoot($file_tmp);
                }
            }
            //进行多个文件压缩,并删除原文件
            $file_name = CreateZipFile($file_name_arr, $this->save_path . $file_name);
        } else {
            //导出单个文件
            $file_tmp = $this->save_path . $file_name . '.xls';//文件全路径及文件名
            $file_name = $file_name . '.xls';

            //导出图片时设置行高
            if (!empty($field_img_key)) {
                $setRowHeight = true;
            } else {
                $setRowHeight = false;
            }
            //写入表头
            writeExcelHead($column_data['rows'], $file_tmp, $setRowHeight);
            //一页一页取出数据处理
            for ($page = 1; $page <= $total_page; $page++) {
                $offsets = ($page - 1) * $limit;
                //获取导出数据
                $result = call_user_func_array([$model, $function], array($params, $offsets, $limit, $page, true));
                //使用生成器迭代
                $content_data = yieldData($result['values']);
                //数据写入文件
                writeExcelContent($content_data, $column_data['keys'], $file_tmp, $field_img_key, $offsets);
            }
            //写入页脚
            writeExcelFoot($file_tmp);
        }

        //前端下载地址
        return CG_SYSTEM_WEB_FRONT_IP . $this->tmp_path . $file_name;
    }
}