<?php
/**
 * User: roy
 * Time: 11:15
 */

class ImageResize
{
    /** 遍历获取文件下的文件
     * @author roy
     * @param $dir
     * @return array|bool
     */
    public static function read_all($dir)
    {
        if (!is_dir($dir)) return false;
        $handle    = opendir($dir);
        $temp_list = [];
        if ($handle) {
            while (($fl = readdir($handle)) !== false) {
                $temp = $dir . DIRECTORY_SEPARATOR . $fl;
                if (is_dir($temp) && $fl != '.' && $fl != '..') {
                    read_all($temp);
                } else {
                    if ($fl != '.' && $fl != '..') {
                        $temp_list[] = $fl;
                    }
                }
            }
        }
        return $temp_list;
    }

    /**
     * @author roy
     * @param $src 图片路径
     * @param null $width 宽度
     * @param null $height 高度
     * @param null $filename 图片名称
     * @return bool
     */
    public static function mkThumbnail($src, $width = null, $height = null, $filename = null)
    {
        if (!isset($width) && !isset($height))
            return false;
        if (isset($width) && $width <= 0)
            return false;
        if (isset($height) && $height <= 0)
            return false;

        $size = getimagesize($src);
        if (!$size)
            return false;

        list($src_w, $src_h, $src_type) = $size;
        $src_mime = $size['mime'];
        switch ($src_type) {
            case 1 :
                $img_type = 'gif';
                break;
            case 2 :
                $img_type = 'jpeg';
                break;
            case 3 :
                $img_type = 'png';
                break;
            case 15 :
                $img_type = 'wbmp';
                break;
            default :
                return false;
        }

        if (!isset($width))
            $width = $src_w * ($height / $src_h);
        if (!isset($height))
            $height = $src_h * ($width / $src_w);

        $imagecreatefunc = 'imagecreatefrom' . $img_type;
        $src_img         = $imagecreatefunc($src);
        $dest_img        = imagecreatetruecolor($width, $height);
        imagecopyresampled($dest_img, $src_img, 0, 0, 0, 0, $width, $height, $src_w, $src_h);
        $imagefunc = 'image' . $img_type;
        if ($filename) {
            $imagefunc($dest_img, $filename);
        } else {
            header('Content-Type: ' . $src_mime);
            $imagefunc($dest_img);
        }
        imagedestroy($src_img);//原图销毁
        imagedestroy($dest_img);//缩略图销毁
        return true;
    }

    /**
     * @desc 返回文件的拓展名
     * @param $file 文件绝对路径
     * @return string 文件拓展名
     */
    public function fileext($file)
    {
        return pathinfo($file, PATHINFO_EXTENSION);
    }
}