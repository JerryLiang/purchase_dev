<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/10
 * Time: 15:01
 */
class PlanCancelExportService
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
        $this->_ci->load->model('Shipment_cancel_list_model', 'm_shipment_cancel', false, 'purchase_shipment');
        $db = $this->_ci->m_shipment_cancel->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $clone_db  = clone $db;
            $gids_arr  = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $clone_db->from($this->_ci->m_shipment_cancel->getTable())->where_in('id', $gids_arr)->order_by('create_time desc, id desc')->get_compiled_select('', false);
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::PURCHASE_SHIPMENT_PLAN_CANCEL_SEARCH_EXPORT)->get();

            $total = substr($quick_sql, 0, 10);
            $quick_sql = substr($quick_sql, 10);
            if (!$quick_sql)
            {
                throw new \RuntimeException(sprintf('请选择要导出的资源'));
            }

            $data_type = VIEW_FILE;
        }

        //导出excel
        if ($this->_file_type == 'xlsx'){
            throw new \InvalidArgumentException(sprintf('目前暂不支持这种格式'), 3001);
        }elseif ($this->_file_type == 'csv'){
            if ($total > 100000)
            {
                throw new \OverflowException(sprintf('最多只能导出%d条数据，请筛选相关条件导出；如需导出10W条以上的数据，请找相关负责人', 100000), 500);
            }

            $this->_ci->load->classes('purchase_shipment/classes/ShipmentPlanCancelExport');
            $this->_ci->ShipmentPlanCancelExport
                ->set_format_type($format_type)
                ->set_data_type($data_type)
                ->set_out_charset($charset)
                ->set_title_map($profile)
                ->set_translator()
                ->set_data_sql($quick_sql)
                ->set_export_nums($total);
            return $this->_ci->ShipmentPlanCancelExport->run();
        }else{
            throw new \InvalidArgumentException(sprintf('目前暂不支持这种格式'), 3001);
        }
    }


}