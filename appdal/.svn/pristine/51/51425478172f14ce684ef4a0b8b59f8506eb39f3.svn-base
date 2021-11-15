<?php

/**
 * PDF操作类
 * User: Jolon
 * Date: 2020/11/14 10:23
 */
class Print_pdf_deal{

    public function __construct(){

    }

    public function pdfRender($html, $title = '', $fileName, $type = 'D'){
        /*新建一个pdf文件：
       Orientation：orientation属性用来设置文档打印格式是“Portrait”还是“Landscape”。 Landscape为横式打印，Portrait为纵向打印
       Unit：设置页面的单位。pt：点为单位，mm：毫米为单位，cm：厘米为单位，in：英尺为单位
       Format：设置打印格式，一般设置为A4
       Unicode：为true，输入的文本为Unicode字符文本
       Encoding：设置编码格式，默认为utf-8
       Diskcache：为true，通过使用文件系统的临时缓存数据减少RAM的内存使用。 */
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        //设置文件信息
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor("jmcx");
        $pdf->SetTitle($title);
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
        //删除预定义的打印 页眉/页尾
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        //设置默认等宽字体
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        /*设置页面边幅：
        Left：左边幅
        Top：顶部边幅
        Right：右边幅
        Keepmargins：为true时，覆盖默认的PDF边幅。 */
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        /*设置单元格的边距：
        Left：左边距
        Top：顶部边距
        Right：右边距
        Bottom：底部边距。*/
        $pdf->setCellPaddings(0, 0, 0, 0);
        //GetX获得当前的横坐标，GetY获得当前的纵坐标。
        //  $pdf->GetX();
        //    $pdf->GetY();
        /*移动坐。SetX移动横坐标。 SetY，横坐标自动移动到左边距的距离，然后移动纵坐标。SetXY，移动横坐标跟纵坐标：
        X：横坐标，可设为$pdf->GetX()+数字
        Y：纵坐标，可设为$pdf->GetY()+数字
        Rtloff：true，左上角会一直作为坐标轴的原点
        Resetx：true，重设横坐标。 */
//       $pdf->SetX($x, $rtloff=false);
//       $pdf->SetY($y, $resetx=true, $rtloff=false);
//       $pdf->SetXY($x, $y, $rtloff=false);
        /*设置线条的风格：
        Width：设置线条粗细
        Cap：设置线条的两端形状
        Join：设置线条连接的形状
        Dash：设置虚线模式
        Color：设置线条颜色，一般设置为黑色，如：array(0, 0, 0)。*/
        $pdf->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0, 0, 0)));
        /*画一条线：
        x1：线条起点x坐标
        y1：线条起点y坐标
        x2：线条终点x坐标
        y2：线条终点y坐标
        style：SetLineStyle的效果一样
        */
//       $pdf->Line($x1, $y1, $x2, $y2, $style=array());
        /*执行一个换行符，横坐标自动移动到左边距的距离，纵坐标换到下一行：
        H：设置下行跟上一行的距离，默认的话，高度为最后一个单元格的高度
        Cell：true，添加左或右或上的间距到横坐标。 */
//       $pdf->Ln($h='', $cell=false);
        //设置自动分页符
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        //设置图像比例因子
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        //设置一些语言相关的字符串
//       $pdf->setLanguageArray("xx");
        /*设置字体：
 字体类型（如helvetica(Helvetica)黑体，times (Times-Roman)罗马字体）、风格（B粗体，I斜体，underline下划线等）、字体大小 */
        $pdf->SetFont('stsongstdlight', '', 8); //设置中文显示
        /*增加一个页面:
        Orientation：orientation属性用来设置文档打印格式。 Landscape为横式打印，Portrait为纵向打印。
        Format：设置此页面的打印格式。
        Keepmargins：true，以当前的边幅代替默认边幅来重写页面边幅。
        Tocpage：true，所添加的页面将被用来显示内容表。*/
        $pdf->AddPage();
        /*设置单行单元格：
        W：设置单元格的宽
        H：设置单元格的高
        Text：单元格文本
        Border：设置单元格的边框。0，无边框，1，一个框，L，左边框，R，右边框，B， 底边框，T，顶边框，LTRB指四个边都显示
        Ln：0，单元格后的内容插到表格右边或左边，1，单元格的下一行，2，在单元格下面
        Align：文本位置。L，左对齐，R，右对齐，C，居中，J，自动对齐
        Fill：填充。false，单元格的背景为透明，true，单元格必需被填充
        Link：设置单元格文本的链接。*/
        $pdf->Cell(0, 0, $title, 0, 1, 'C');
        $pdf->writeHTML($html);
        /*输入PDF文档 :
        Name：PDF保存的名字
        Dest：PDF输出的方式。I，默认值，在浏览器中打开；D，点击下载按钮， PDF文件会被下载下来；F，文件会被保存在服务器中；S，PDF会以字符串形式输出；E：PDF以邮件的附件输出。 */
        $pdf->Output($fileName.".pdf", $type);
    }

    /**
     * 生成 或 预览PDF文件
     * @param        $content
     * @param string $title
     * @param        $fileName
     * @param string $type
     * @param string $print_type 纸打印类型 A4 A3
     */
    public static function writePdf($content, $title = '', $fileName, $type = 'D', $file_css = '',$print_type='A4',$footer =''){
        require_once APPPATH."third_party/mpdf/mpdf.php";
        if (ob_get_contents()) ob_end_clean();
        $mpdf                   = new \mPDF('', $print_type, '', '', 0, 0);// A5-L A5横向
        $mpdf->autoScriptToLang = 1;
        $mpdf->autoLangToFont   = 1;
        $mpdf->SetDisplayMode(100);
        //添加页脚,页码到pdf中
        if(!empty($footer)){
            $mpdf->SetHTMLFooter($footer);
        }

        if(empty($fileName)){// 文件名为空的自动设置
            $file = 'HT-'.date('YmdHis').'.pdf';
        }elseif(stripos($fileName,'pdf') === false){// 没有文件后缀的自动添加
            $file = $fileName.'.pdf';
        }else{
            $file = $fileName;
        }
        if(empty($file_css)){
            $css1 = VIEWPATH."mycss/requestion.css";
        }else{
            $css1 = VIEWPATH."mycss/".$file_css;
        }
        $style1 = file_get_contents($css1);
        $mpdf->WriteHTML($style1, 1);
        $mpdf->WriteHTML($content, 2);
        $mpdf->Output($file, $type);
    }

    /**
     * 无css载入
     * @param $content
     * @param string $title
     * @param $fileName
     * @param string $type
     * @param string $file_css
     * @param string $print_type
     * @param string $footer
     * @throws MpdfException
     */
    public static function writePdfOnDom($content, $title = '', $fileName, $type = 'D', $file_css = '',$print_type='A4',$footer =''){
        require_once APPPATH."third_party/mpdf/mpdf.php";
        $mpdf = new \mPDF('', 'A4-L', '', '', 0, 0);// A5-L A5横向
        $mpdf->autoScriptToLang = 1;
        $mpdf->autoLangToFont   = 1;
        if(!empty($footer)){
            $mpdf->SetHTMLFooter($footer);
        }
        $mpdf->DefOrientation = "P";
        $mpdf->CurOrientation = "P";
        if(empty($fileName)){
            $file = 'SHD-'.date('YmdHis').'.pdf';
        }else{
            $file = $fileName.'.pdf';
        }
        $mpdf->WriteHTML($content);
        $mpdf->Output($file, $type);
    }

    /**
     * @param $content
     * @param $path
     * @param $fileName
     * @param string $type
     * @param string $print_type
     * @return false|string
     * @throws MpdfException
     */
    public static function fPdf($content,$path,$fileName, $type = 'f',$print_type='A4'){
        require_once APPPATH."third_party/mpdf/mpdf.php";
        $mpdf = new \mPDF('', $print_type, '', '', 0, 0);// A5-L A5横向
        $mpdf->autoScriptToLang = 1;
        $mpdf->autoLangToFont   = 1;
        $mpdf->SetDisplayMode(100);
        if(empty($fileName)){
            $fileName = 'HT-'.date('YmdHis').'.pdf';
        }else{
            $fileName = $fileName.'.pdf';
        }
        $filename= iconv("utf-8","gb2312",$fileName);
        $css1 = VIEWPATH_PRINT_TEMPLATE."print_template/mycss/requestion.css";
        $content = file_get_contents($content);
        $style1 = file_get_contents($css1);
        $mpdf->WriteHTML($style1, 1);
        $mpdf->WriteHTML($content, 2);
        $mpdf->Output($path.'/'.$filename,$type);
        return $filename;
    }

    /**
     * 文件生成压缩包
     * @param $file_list    绝对路径文件列表
     * @param $zip_file_path    绝对路径保存到的ZIP压缩包
     * @return bool|string
     */
    public function download_zip_package($file_list,$zip_file_path){
        if($file_list){
            require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Php_zip.php';
            $php_zip = new Php_zip();

            foreach ($file_list as $filename => $file_path) {
                $suffix = explode('.', basename($file_path));   //文件后缀
                if (empty($suffix)) continue;                    //获取后缀失败,跳过操作
                $suffix = '.' . array_pop($suffix);
                $filename = $filename.$suffix;

                $php_zip->add_file(file_get_contents($file_path), $filename);
            }
            //----------------------
            $php_zip->output($zip_file_path);

            return true;
        }else{
            return '文件列表为空';
        }
    }


    /**
     * 文件生成压缩包
     * @param $dir_path    模板文件夹路径
     * @param $zip_file_path    绝对路径保存到的ZIP压缩包
     * @return bool|string
     */
    public function create_zip_package($dir_path,$zip_file_path){
        if(!is_dir($dir_path)){
            return '文件夹路径错误';
        }
        require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Php_zip.php';
        $php_zip = new Php_zip();
        $php_zip->add_path($dir_path);
        $php_zip->output($zip_file_path);

        return true;
    }
}
