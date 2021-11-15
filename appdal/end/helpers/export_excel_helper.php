<?php
/**
 * @desc excel导出
 * @param array $heads 表头
 * @param array $datalist 数据
 * @param string $filename 文件名称
 * @param array $field_img_name 需要转换成图片的表头名称
 * @param array $field_img_key 需要转换成图片的字段键名
 * @author sinder 2019-05-24
 */
function export_excel($heads, $datalist, $filename, $field_img_name = array('图片'), $field_img_key = array()){
    set_time_limit(0);
    ini_set('memory_limit', '500M');
    ini_set('post_max_size', '500M');
    ini_set('upload_max_filesize', '1000M');
    header( "Content-Type: application/vnd.ms-excel; name='excel'" );
    header( "Content-type: application/octet-stream" );
    header( "Content-Disposition: attachment; filename=".$filename );
    header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
    header( "Pragma: no-cache" );
    header( "Expires: 0" );

    $str = "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\"\r\nxmlns:x=\"urn:schemas-microsoft-com:office:excel\"
        \r\nxmlns=\"http://www.w3.org/TR/REC-html40\">\r\n<head>\r\n<meta http-equiv=Content-Type 
        content=\"text/html; charset=utf-8\">\r\n</head>\r\n<body>";
    $str .="<style>tr{height: 50px;}</style>";
    $str .="<table border=1>";
    $str .= "<tr>";
    $line_arr = array();
    foreach ($heads as $line => $title) {
        if (in_array($title, $field_img_name)) {
            $line_arr[] = $line;
            $str .= "<th width='60'>{$title}</th>";
        } else {
            $str .= "<th>{$title}</th>";
        }
    }

    foreach ($datalist as $key=> $rt )
    {
        $str .= "<tr>";
        foreach ( $rt as $k => $v )
        {
            if (in_array($k, $line_arr) || in_array($k, $field_img_key)) {
                $str .= "<td><img src='{$v}' width='50' height='50' /></td>";
            } else {
                $str .= "<td>{$v}</td>";
            }
        }
        $str .= "</tr>\n";
    }
    $str .= "</table></body></html>";
    exit( $str );

}

/**
 * 写入Excel表头
 * @param array $head_arr = array(
 *     // array(array(字段名1, 其他设置), 字段名2) 其他设置（如跨列，跨行等） 可选
 *     array(
 *         array('序号', ['colspan' => 2, 'rowspan' => 2]),
 *         '订单号',
 *         '同行客户'
 *     )
 * )
 * @param string $file_name
 */
if (!function_exists('writeExcelHead')) {
    function writeExcelHead($head_arr, $file_name, $export_img = false)
    {
        $head_html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"> <head> <meta http-equiv=Content-Type content="text/html; charset=utf-8"> </head> <body>';
        if ($export_img) {
            $head_html .= '<style>tr{height: 50px;}</style>';
        }
        $head_html .= '<table border="1">';

        if (is_array($head_arr) && !empty($head_arr)) {
            foreach ($head_arr as $rows) {
                $head_html .= '<tr>';
                foreach ($rows as $head) {
                    if (!is_array($head)) {
                        $head_html .= "<th>{$head}</th>";
                    } else {
                        $other = isset($head[1]) ? $head[1] : [];
                        $head_html .= "<th";
                        if (!empty($other) && is_array($other)) {
                            foreach ($other as $k => $v) {
                                $head_html .= " {$k}='{$v}' ";
                            }
                        }
                        $head_html .= " >" . $head[0] . "</th>";
                    }
                }
                $head_html .= "</tr>";
            }
        }
        file_put_contents($file_name, $head_html, FILE_APPEND);
    }
}

/**
 * 数据写入Excel文件
 * @param $data
 * @param $column_keys
 * @param $file_name
 * @param array $field_img_key 图片字段名称
 * @param int $offset_sequence 序号偏移量（分页导出时，为了保持序号连续）
 */
if (!function_exists('writeExcelContent')) {
    function writeExcelContent($data, $column_keys, $file_name, $field_img_key = array(), $offset_sequence = 0)
    {
        foreach ($data as $k => $item) {
            $sequence = (int)$k + $offset_sequence + 1;//序号
            $str = "<tr>";
            foreach ($column_keys as $key) {
                if ('sequence' == $key) {//序号
                    $str .= "<td>{$sequence}</td>";
                } else if (in_array($key, $field_img_key)) {//图片字段
                    $str .= "<td><img src='{$item[$key]}' width='50' height='50' /></td>";
                } elseif (is_array($item[$key])) {//数组格式的值的处理
                    $_str = !empty($item[$key]) ? implode(';', $item[$key]) : '';
                    $str .= "<td>{$_str}</td>";
                } else {
                    //防止数字显示科学计数法
                    $style = isset($item[$key]) && is_numeric($item[$key]) && strlen($item[$key]) > 11 ? 'style="mso-number-format:\'\@\';"' : '';
                    //正常处理
                    $str .= "<td {$style}>{$item[$key]}</td>";
                }
            }
            $str .= "</tr>\n";
            file_put_contents($file_name, $str, FILE_APPEND);
            unset($str);
        }
    }
}

/**
 * 写入Excel页脚
 * @param $file_name
 */
if (!function_exists('writeExcelFoot')) {
    function writeExcelFoot($file_name)
    {
        $str_foot = "</tr></table></body></html>";
        file_put_contents($file_name, $str_foot, FILE_APPEND);
    }
}