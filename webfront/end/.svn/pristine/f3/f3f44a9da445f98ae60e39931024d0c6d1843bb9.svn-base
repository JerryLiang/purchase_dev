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
    $str = "<html xmlns:c=\"urn:schemas-microsoft-com:office:office\"\r\nxmlns:x=\"urn:schemas-microsoft-com:office:excel\"
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
            if ( $k != 'purchase_order_status' && (in_array($k, $line_arr) || in_array($k, $field_img_key))) {
                $str .= "<td><img src='{$v}' width='50' height='50' /></td>";
            } elseif(is_numeric($v) && strlen($v) > 9) {
                //
                $str .= "<td width='300' style='vnd.ms-excel.numberformat:@'>".$v."</td>";

            }else{
                 if( is_array($v))
                 {
                     continue;
                 }
                 $str .= "<td style='vnd.ms-excel.numberformat:@'>{$v}</td>";
            }
        }
        $str .= "</tr>\n";
    }
    $str .= "</table></body></html>";

    $str = str_replace(",","",$str);
    exit( $str );

}