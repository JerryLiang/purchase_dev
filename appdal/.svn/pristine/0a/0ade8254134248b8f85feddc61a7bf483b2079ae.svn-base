<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/8
 * Time: 13:48
 */
require_once APPPATH . 'modules/basic/classes/contracts/AbstractHugeExport.php';

class ShipmentPlanCancelExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Shipment_cancel_list_model', 'm_shipment_cancel', false, 'purchase_shipment');
        $this->_db = $this->_ci->m_shipment_cancel->getDatabase();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('purchase_shipment/classes/ShipmentPlanCancelTemplate');
        $template = $this->_ci->ShipmentPlanCancelTemplate->get_default_template_cols();
        return $template;
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::set_translator()
     */
    public function set_translator() : AbstractHugeExport
    {
        if ($this->_format_type == EXPORT_VIEW_NATIVE)
        {
            return $this;
        }
        $this->col_map = [

        ];

        $this->tran_func_map = [
            'audit_status' => 'getShipmentPlanCancelAuditStatus',
            'shipment_type' => 'getShipmentType',
            'suggest_order_status' => 'getPurchaseStatus',
            'is_drawback' => 'getIsDrawback',
        ];

        return $this;
    }

    /**
     *
     * {@inheritDoc}
     * @see HugeExportable::run()
     */
    public function run()
    {
        try
        {

            $this->before();
            set_time_limit(0);
            $pick_cols  = $this->_cols;
            $col_map    = $this->col_map;
            $tran_func_map   = $this->tran_func_map;
            $file_name  = '发运管理-计划部取消列表_'.date('YmdHi');
            if ($this->_format_type == EXPORT_VIEW_NATIVE)
            {
                return false;
            }
            else
            {

                $trans = function ($row) use ($pick_cols, $col_map, $tran_func_map) {
                    $new     = [];
                    $time_cols    = ['create_time', 'apply_time','audit_time'];
                    $tab_cols    = ['sku'];
                    foreach ($pick_cols as $col)
                    {
                        if (in_array($col, $time_cols))
                        {
                            $new[$col] = empty($row[$col]) || $row[$col]=='0000-00-00 00:00:00'? '' : $row[$col]."\t";
                            continue;
                        }
                        if (in_array($col, $tab_cols))
                        {
                            $new[$col] = $row[$col]."\t";
                            continue;
                        }

                        if(isset($row[$col])){
                            if (isset($tran_func_map[$col])){
                                $new[$col] = call_user_func($tran_func_map[$col],$row[$col]);
                            }else{
                                $new[$col] = $row[$col];
                            }
                        }

                        if (empty($new[$col])){
                            $new[$col] = '';
                        }

                    }
                    return $new;
                };
            }

            $this->_ci->load->dbutil();

            $db_query = $this->_db->query_unbuffer($this->data_sql);
            $genertor = $this->_ci->dbutil->csv_from_yeild_result($db_query, $trans, 100);
            $file_path = $this->output($file_name, $genertor);
            $file_path = $this->get_download_url($file_path);
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', '导出csv出现异常：'.$e->getMessage());
            $file_path = '';
        }

        finally
        {
            $db_query && $db_query->free_result();
            $this->after();
            return $file_path;
        }
    }

    public function __destruct()
    {

    }

}