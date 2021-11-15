<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/10
 * Time: 15:01
 */
class PurchaseConfirmExportService
{
    /**
     * client
     *
     * @var array
     */
    public static $s_encode = ['UTF-8'] ;

    private $_entity;

    private $_ci;

    /**
     * 模板
     * @var unknown
     */
    private $_template;

    /**
     * 文件类型
     * @var unknown
     */
    private $_file_type;

    /**
     * 前端展示类型
     *
     * @var unknown
     */
    private $_data_type;

    /**
     * 指定的文件类型
     *
     * @var array
     */
    private $_allow_file_types = ['csv', 'xls', 'xlsx', 'pdf'];

    private $_gids;

    private $_data;

    /**
     * 导出选择的字段
     * @var unknown
     */
    private $_profile;

    /**
     * 导出格式  1 原生  2 可视化
     * @var unknown
     */
    private $_format_type;

    /**
     *
     * @param unknown $template
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
    }

    public function setTemplate($post)
    {
        $this->_template = $post['template'] ?? __CLASS__;
        $this->_data_type = $post['data_type'] ?? VIEW_BROWSER;
        $this->_charset   = $post['charset'] ?? 'UTF-8';
        //csv
        $this->_data_type = $post['data_type'] ?? 3;
        if (!isset($post['gid']))
        {
            //默认选择当前筛选
            $this->_gids = '';
        }
        else {
            $this->_gids = $post['gid'];
        }
        $this->_profile = $post['profile'] ?? '';
        if ($this->_profile == '')
        {

            $this->_profile = '*';
        }
        else
        {
            $this->_profile = is_array($this->_profile) ? $this->_profile : explode(',', $this->_profile);
        }
        $this->_format_type = $post['format_type'] ?? EXPORT_VIEW_PRETTY;
    }

    /**
     * 导出
     *
     * @param unknown $post
     */
    public function export($file_type = 'csv')
    {
        $this->_file_type = strtolower($file_type);
        if (!in_array($this->_file_type, $this->_allow_file_types))
        {
            throw new \InvalidArgumentException(sprintf('目前暂不支持这种格式'), 3001);
        }
        return $this->quick_export($this->_gids, $this->_profile, $this->_format_type, $this->_data_type, $this->_charset);
    }

    /**
     *
     * @param string $gids 页面选择的gids，为空表示从搜索条件获取
     * @param string $profile 用户选择导出的列
     * @param string $format_type 导出csv的格式， 可读还是用于修改的原生字段
     * @throws \RuntimeException
     * @throws \OverflowException
     * @return unknown
     */
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8')
    {
        $this->_ci->load->model('return/Return_after_storage_main_model', 'm_return_main', false, 'purchase');
        $this->_ci->load->model('return/Return_after_storage_part_model', 'm_return_part', false, 'purchase');
        $db = $this->_ci->m_return_part->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $clone_db  = clone $db;
            $gids_arr  = explode(',', $gids);
            $total = count($gids_arr);
            //表名
            $part_t = $this->_ci->m_return_part->getTable();
            $main_t = $this->_ci->m_return_main->getTable();

            $quick_sql = $clone_db->from($part_t)
                ->join($main_t,"$main_t.main_number = {$part_t}.main_number",'left')
                ->where_in("{$part_t}.id", $gids_arr)->order_by('create_time desc, id desc')->get_compiled_select('', false);
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::PURCHASE_RETURN_CONFIRM_LIST_SEARCH_EXPORT)->get();

            $total = substr($quick_sql, 0, 10);
            $quick_sql = substr($quick_sql, 10);
            if (!$quick_sql)
            {
                throw new \RuntimeException(sprintf('请选择要导出的资源'));
            }
//echo ($quick_sql);exit;
//            if ($total > 300000)
//            {
//                throw new \OverflowException(sprintf('最多只能导出%d条数据，请筛选相关条件导出；如需导出30W条以上的数据，请找相关负责人', 300000), 500);
//            }
            $data_type = VIEW_FILE;
//            if ($total > 300000)
//            {
//                //强制转文件模式
//                $data_type = VIEW_FILE;
//            }
//            else
//            {
//                if ($data_type == VIEW_AUTO)
//                {
//                    $data_type = VIEW_BROWSER;
//                }
//            }
        }
        //导出excel
        if ($this->_file_type == 'xlsx'){
            if ($total > 50000)
            {
                throw new \OverflowException(sprintf('最多只能导出%d条数据，请筛选相关条件导出；如需导出5W条以上的数据，请找相关负责人', 50000), 500);
            }
            $this->_ci->lang->load('common_lang');
            $this->data['data_list']['key'] = array_column($this->_ci->lang->myline('return_purchase_confirm_list'),'label');
            $this->data['data_list']['value'] = $this->_ci->m_return_main->query_quick_sql($quick_sql);
            $this->_ci->load->service('return/PurchaseConfirmListService');
            $this->data = $this->_ci->purchaseconfirmlistservice->translate($this->data,true);

            $result = array(
                'heads' => $this->data['data_list']['key'],
                'data_values' => $this->data['data_list']['value'],
                'file_name' =>  '采购明细_' . date('YmdHis') . '.xlsx',
                'field_img_name' => array('图片'),
                'field_img_key' => array('product_img_url_thumbnails')
            );
            return $result;
        }elseif ($this->_file_type == 'csv'){
            if ($total > 100000)
            {
                throw new \OverflowException(sprintf('最多只能导出%d条数据，请筛选相关条件导出；如需导出10W条以上的数据，请找相关负责人', 100000), 500);
            }
            $this->_ci->load->classes('purchase/classes/PurchaseConfirmExport');
            $this->_ci->PurchaseConfirmExport
                ->set_format_type($format_type)
                ->set_data_type($data_type)
                ->set_out_charset($charset)
                ->set_title_map($profile)
                ->set_translator()
                ->set_data_sql($quick_sql)
                ->set_export_nums($total);
            return $this->_ci->PurchaseConfirmExport->run();
        }else{
            throw new \InvalidArgumentException(sprintf('目前暂不支持这种格式'), 3001);
        }


    }
}