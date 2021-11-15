<?php
/**
 * PDF操作类
 * User: Jaxton
 * Date: 2019/01/14 10:23
 */

class Print_pdf_model extends Purchase_model {

	public function __construct(){
		require_once  APPPATH . "third_party/tcpdf/tcpdf.php";
	}

    /**
      * 在服务器生成PDF 文件
     **/
    public function new_print_pdf($html,$type='E'){

        //初始化TCPDF类
        $tcpdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT,true, 'UTF-8', false);

        //设置作者，标题，文件属性
        $tcpdf->SetTitle('PDF');
        $tcpdf->SetKeywords('PDF, TCPDF');

        // 设置页眉和页脚信息
        $tcpdf->setPrintHeader(false);    //页面头部横线取消
        $tcpdf->setPrintFooter(false); //页面底部更显取消


        //设置文档对齐，间距，字体，图片
        $tcpdf->SetCreator(PDF_CREATOR);
        $tcpdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        //$tcpdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $tcpdf->SetMargins(1, 2, 1);
        $tcpdf->setCellPaddings(0, 0, 0, 0);
        $tcpdf->setCellPaddings(0, 0, 0, 0);

        //自动分页
        $tcpdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // $tcpdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // $tcpdf->setFontSubsetting(true);
        // $tcpdf->setPageMark();

        //设置正文字体，大小   （stsongstdlight，网上说这个字体支持的文字更全，支持中文不乱码）
        $tcpdf->SetFont('stsongstdlight', '', 2);

        //创建页面，渲染PDF
        $tcpdf->AddPage();

        //$html = '<h1>test</h1>';

        $tcpdf->writeHTML($html, true, false, true, true, '');
        $tcpdf->lastPage();

        //PDF输出   I：在浏览器中打开，D：下载，F：在服务器生成pdf ，S：只返回pdf的字符串
        $filePath=get_export_path().date('Ymdhis').'.pdf';

        $tcpdf->Output($filePath,$type);
        return $filePath;
    }
	
	public function print_pdf($html,$type='I'){

		//初始化TCPDF类
		$tcpdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT,true, 'UTF-8', false);

		//设置作者，标题，文件属性
		$tcpdf->SetTitle('PDF');
		$tcpdf->SetKeywords('PDF, TCPDF');

		// 设置页眉和页脚信息
		$tcpdf->setPrintHeader(false);    //页面头部横线取消
        $tcpdf->setPrintFooter(false); //页面底部更显取消


		//设置文档对齐，间距，字体，图片
		$tcpdf->SetCreator(PDF_CREATOR);
		$tcpdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		//$tcpdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$tcpdf->SetMargins(1, 2, 1); 
		$tcpdf->setCellPaddings(0, 0, 0, 0);
		$tcpdf->setCellPaddings(0, 0, 0, 0);

		//自动分页
		 $tcpdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		// $tcpdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		// $tcpdf->setFontSubsetting(true);
		// $tcpdf->setPageMark();

		//设置正文字体，大小   （stsongstdlight，网上说这个字体支持的文字更全，支持中文不乱码）
		$tcpdf->SetFont('stsongstdlight', '', 2);

		//创建页面，渲染PDF
		$tcpdf->AddPage();

		//$html = '<h1>test</h1>';

		$tcpdf->writeHTML($html, true, false, true, true, '');
		$tcpdf->lastPage();

		//PDF输出   I：在浏览器中打开，D：下载，F：在服务器生成pdf ，S：只返回pdf的字符串
		$filePath=get_export_path().date('Ymdhis').'.pdf';
		$tcpdf->Output($filePath,'E');
		return $filePath;
	}

	
	public static function writePdf($content)
    {
        $pdf = new TCPDF('P', 'cm', array(800, 300), true, 'UTF-8', false);//('Landscape', 'cm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        
        //$pdf->SetHeaderData("logo.jpg", 70, 'wanglibao Agreement' . '', '');
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->AddPage();
        //$pdf->setPageMark();
        $pdf->SetFont('stsongstdlight', '', 7);
        $title = <<<EOD
<h2>标题</h2>
EOD;

        $pdf->writeHTML($content, true, false, false, false, '');
//         $pdf->writeHTML($content, true, 0, true, true);
//         $pdf->writeHTMLCell(0, 0, '', '', $content, 0, 1, 0, true, 'C', true);
        $pdf->lastPage();
        $pdf->Output(date('Y-m-d') . '.pdf', 'D');
    }
}