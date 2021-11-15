<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/8
 * Time: 11:48
 */

require_once APPPATH . 'modules/basic/classes/contracts/DefaultTemplate.php';


class ShipmentPlanCancelTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->service('basic/UsercfgProfileService');
        $special_field = [];
        $sign_field    = [];

        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('shipment_plan_cancel_list', [], $special_field, [], $sign_field);

    }
}