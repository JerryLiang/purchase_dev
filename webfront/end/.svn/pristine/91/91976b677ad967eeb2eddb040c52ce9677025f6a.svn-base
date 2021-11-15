<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Home extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index() {
        $this->load->view('index.html');
    }


    /**
     * 删除 缓存文件
     * @author Jolon
     * @url home/get_cache_files?dir=suggest
     */
    public function get_cache_files(){
        $dir    = $this->input->get_post('dir');
        $isRecursion   = $this->input->get_post('isRecursion');
        $files  = readAllFile(get_export_path($dir),'',$isRecursion);

        echo serialize($files);
        exit;
    }

    /**
     * 删除指定文件
     */
    public function delete_file(){
        $file = $this->input->get_post('file');
        if(stripos($file,'download_csv') === false){
            echo '目录无权限';exit;
        }
        if(file_exists($file)){
            unlink($file);
            echo 'sss';exit;
        }else{
            echo 'none';exit;
        }
    }

    public function esign_callback(){
        $data = [];
        $data['post'] = $_POST;
        $data['get'] = $_GET;

        $contentType = (isset($_SERVER['CONTENT_TYPE']) and !empty($_SERVER['CONTENT_TYPE'])) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($contentType, 'json') > 0) {
            $params = file_get_contents('php://input');
            $data['input'] = $params;
        }

        file_put_contents(get_export_path('esign_callback').date('Ymd').'.txt',date('Y-m-d H:i:s')."\t".json_encode($data).PHP_EOL,FILE_APPEND);
        echo json_encode(['code' => 200]);
        exit;
    }

}
/* End of file Home.php */
/* Location: ./application/modules/home/controllers/Home.php */