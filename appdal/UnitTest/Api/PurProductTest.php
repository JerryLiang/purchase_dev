<?php

namespace UnitTest\Api;

use App\Model\Api\PurProductModel;
use PHPUnit\Framework\TestCase;

/**
 * PurProductTest
 * Class PurProductTest
 * Create With ClassGeneration
 */
class PurProductTest extends TestCase
{
	public $modelName = '/Api/PurProduct';


	public function testAdd()
	{
		$data = [];
		$data['sku'] = '测试文本rxKa5E';
		$data['product_name'] = '测试文本TkEpZr';
		$data['product_category_id'] = '23523';
		$data['product_line_id'] = '52515';
		$data['product_status'] = '2';
		$data['product_img_url'] = '测试文本MlRkm8';
		$data['product_thumb_url'] = '测试文本B8l3GC';
		$data['uploadimgs'] = '测试文本Iu7tbX';
		$data['product_cn_link'] = '测试文本ntO6d1';
		$data['product_en_link'] = '测试文本b6JVcg';
		$data['create_id'] = '42085';
		$data['create_user_name'] = '测试文本I2mS4A';
		$data['create_time'] = '';
		$data['product_cost'] = '99036.3';
		$data['product_type'] = '2';
		$data['product_package_code'] = '测试文本53DuT9';
		$data['purchase_packaging'] = '测试文本0NuSrF';
		$data['supply_status'] = '0';
		$data['supplier_code'] = '测试文本dzLKhn';
		$data['supplier_name'] = '测试文本wWKMR2';
		$data['purchase_price'] = '57542.6';
		$data['sale_attribute'] = '测试文本YhtKv5';
		$data['note'] = '测试文本3TDFyd';
		$data['last_price'] = '14238.9';
		$data['avg_freight'] = '93786.8';
		$data['avg_purchase_cost'] = '98783.4';
		$data['is_drawback'] = '1';
		$data['tax_rate'] = '0';
		$data['ticketed_point'] = '10591.3';
		$data['declare_cname'] = '测试文本G2wc90';
		$data['declare_unit'] = '测试文本wrDeqW';
		$data['export_model'] = '测试文本K3Ozdf';
		$data['export_cname'] = '测试文本4bnHkp';
		$data['export_ename'] = '测试文本nWlHCX';
		$data['customs_code'] = '测试文本LMbcxu';
		$data['is_sample'] = '3';
		$data['is_multi'] = '2';
		$data['is_inspection'] = '0';
		$data['is_new'] = '1';
		$data['is_boutique'] = '3';
		$data['is_repackage'] = '1';
		$data['is_weightdot'] = '3';
		$data['is_relate_ali'] = '1';
		$data['is_equal_sup_id'] = '2';
		$data['is_equal_sup_name'] = '2';
		$data['relate_ali_name'] = '测试文本VgNBbL';
		$data['is_abnormal'] = '2';
		$data['audit_status'] = '3';
		$data['days_sales_7'] = '87559';
		$data['days_sales_15'] = '29083';
		$data['days_sales_30'] = '81314';
		$data['days_sales_60'] = '68887';
		$data['days_sales_90'] = '83674';
		$data['sku_sale7'] = '测试文本RWgqo8';
		$data['state_type'] = '3';
		$data['is_pull_logis'] = '0';
		$data['original_start_qty'] = '62288';
		$data['original_start_qty_unit'] = '测试文本1jAOb6';
		$data['starting_qty'] = '57987';
		$data['starting_qty_unit'] = '测试文本eT5oO7';
		$data['ali_ratio_own'] = '85478';
		$data['ali_ratio_out'] = '46193';
		$data['is_invalid'] = '3';
		$data['is_from_multi'] = '0';
		$data['productismulti'] = '0';
		$data['producttype'] = '1';
		$data['days_sales_3'] = '91578';
		$data['product_weight'] = '20615.5';
		$data['coupon_rate'] = '10326.2';
		$data['maintain_ticketed_point'] = '0';
		$data['audit_status_log'] = '2';
		$data['record_change_time'] = '';
		$data['is_purchasing'] = '1';
		$data['new_flag'] = '0';
		$data['is_tongtu_purchase'] = '2';
		$data['is_new_purchase'] = '0';
		$data['is_overseas_first_order'] = '3';
		$data['is_old_purchase'] = '2';
		$data['is_gateway'] = '1';
		$data['inside_number'] = '64254';
		$data['box_size'] = '测试文本w06CJK';
		$data['outer_box_volume'] = '59149.2';
		$data['product_volume'] = '35696.3';
		$data['sample_packaging_type'] = '测试文本lKrJic';
		$data['sample_package_size'] = '测试文本rZlP8Y';
		$data['sample_package_length'] = '37374.9';
		$data['sample_package_width'] = '41769.6';
		$data['sample_package_heigth'] = '42403.5';
		$data['sample_package_weight'] = '48710.7';
		$data['sku_message'] = '测试文本LJdMny';
		$data['product_brand'] = '测试文本1vWH4S';
		$data['product_model'] = '测试文本RwBNC8';
		$data['sku_state_type'] = '0';
		$data['is_customized'] = '0';
		$data['original_devliy'] = '58931.9';
		$data['devliy'] = '98288.7';
		$data['net_weight'] = '39053.3';
		$data['rought_weight'] = '46808.4';
		$data['development'] = '82653';
		$data['is_logo'] = '3';
		$data['logo_images'] = '测试文本fOqkt5';
		$data['no_logo_images'] = '测试文本tBTMpR';
		$data['logo_images_thumb_url'] = '测试文本XvT6g0';
		$data['no_logo_images_thumb_url'] = '测试文本VjJG1s';
		$data['update_time'] = '';
		$data['long_delivery'] = '2';
		$data['is_consign'] = '2';
		$data['is_shipping'] = '1';
		$data['unsale_reason'] = '测试文本oSMfym';
		$data['sku_change_data'] = '测试文本YnmxKb';
		$response = $this->request('add',$data);
		$model = new PurProductModel();
		$model->destroy($response->result->id);
		//var_dump(json_encode($response,JSON_UNESCAPED_UNICODE));
	}


	public function testGetOne()
	{
		$data = [];
		$data['sku'] = '测试文本LBV6iA';
		$data['product_name'] = '测试文本KjZq4S';
		$data['product_category_id'] = '12745';
		$data['product_line_id'] = '75034';
		$data['product_status'] = '2';
		$data['product_img_url'] = '测试文本9LsdUa';
		$data['product_thumb_url'] = '测试文本Qn97iX';
		$data['uploadimgs'] = '测试文本Bx7Xn4';
		$data['product_cn_link'] = '测试文本AsEpCX';
		$data['product_en_link'] = '测试文本ugM3Qd';
		$data['create_id'] = '43729';
		$data['create_user_name'] = '测试文本H5lhcW';
		$data['create_time'] = '';
		$data['product_cost'] = '25473';
		$data['product_type'] = '2';
		$data['product_package_code'] = '测试文本MSYeDb';
		$data['purchase_packaging'] = '测试文本10hCat';
		$data['supply_status'] = '0';
		$data['supplier_code'] = '测试文本IDV4Yl';
		$data['supplier_name'] = '测试文本iQ8aCy';
		$data['purchase_price'] = '66313.5';
		$data['sale_attribute'] = '测试文本ESwicA';
		$data['note'] = '测试文本amFwqc';
		$data['last_price'] = '31649';
		$data['avg_freight'] = '99676.9';
		$data['avg_purchase_cost'] = '28654.8';
		$data['is_drawback'] = '0';
		$data['tax_rate'] = '2';
		$data['ticketed_point'] = '55413.8';
		$data['declare_cname'] = '测试文本tmNIiw';
		$data['declare_unit'] = '测试文本AH40fJ';
		$data['export_model'] = '测试文本KOebZt';
		$data['export_cname'] = '测试文本BNVvAp';
		$data['export_ename'] = '测试文本yTP16o';
		$data['customs_code'] = '测试文本Abka05';
		$data['is_sample'] = '1';
		$data['is_multi'] = '1';
		$data['is_inspection'] = '1';
		$data['is_new'] = '2';
		$data['is_boutique'] = '0';
		$data['is_repackage'] = '3';
		$data['is_weightdot'] = '3';
		$data['is_relate_ali'] = '0';
		$data['is_equal_sup_id'] = '1';
		$data['is_equal_sup_name'] = '2';
		$data['relate_ali_name'] = '测试文本6HCJQt';
		$data['is_abnormal'] = '1';
		$data['audit_status'] = '0';
		$data['days_sales_7'] = '79993';
		$data['days_sales_15'] = '38122';
		$data['days_sales_30'] = '13595';
		$data['days_sales_60'] = '93684';
		$data['days_sales_90'] = '41453';
		$data['sku_sale7'] = '测试文本c1upV6';
		$data['state_type'] = '3';
		$data['is_pull_logis'] = '1';
		$data['original_start_qty'] = '32624';
		$data['original_start_qty_unit'] = '测试文本56NB8s';
		$data['starting_qty'] = '40347';
		$data['starting_qty_unit'] = '测试文本yEv3RF';
		$data['ali_ratio_own'] = '16453';
		$data['ali_ratio_out'] = '46830';
		$data['is_invalid'] = '2';
		$data['is_from_multi'] = '0';
		$data['productismulti'] = '3';
		$data['producttype'] = '1';
		$data['days_sales_3'] = '86678';
		$data['product_weight'] = '12473.6';
		$data['coupon_rate'] = '54607.2';
		$data['maintain_ticketed_point'] = '0';
		$data['audit_status_log'] = '3';
		$data['record_change_time'] = '';
		$data['is_purchasing'] = '1';
		$data['new_flag'] = '1';
		$data['is_tongtu_purchase'] = '3';
		$data['is_new_purchase'] = '3';
		$data['is_overseas_first_order'] = '0';
		$data['is_old_purchase'] = '3';
		$data['is_gateway'] = '2';
		$data['inside_number'] = '89590';
		$data['box_size'] = '测试文本gp9nTs';
		$data['outer_box_volume'] = '86790.3';
		$data['product_volume'] = '28893.2';
		$data['sample_packaging_type'] = '测试文本5cEwGo';
		$data['sample_package_size'] = '测试文本TaI4g6';
		$data['sample_package_length'] = '58159.6';
		$data['sample_package_width'] = '94804.5';
		$data['sample_package_heigth'] = '53923.5';
		$data['sample_package_weight'] = '17514.4';
		$data['sku_message'] = '测试文本iFC4WB';
		$data['product_brand'] = '测试文本Ot7yup';
		$data['product_model'] = '测试文本6eUvlD';
		$data['sku_state_type'] = '2';
		$data['is_customized'] = '1';
		$data['original_devliy'] = '89293.6';
		$data['devliy'] = '80680.6';
		$data['net_weight'] = '90338.3';
		$data['rought_weight'] = '86327.2';
		$data['development'] = '59292';
		$data['is_logo'] = '1';
		$data['logo_images'] = '测试文本0zipQv';
		$data['no_logo_images'] = '测试文本pRh0Ui';
		$data['logo_images_thumb_url'] = '测试文本eKNzPg';
		$data['no_logo_images_thumb_url'] = '测试文本LyoBdX';
		$data['update_time'] = '';
		$data['long_delivery'] = '1';
		$data['is_consign'] = '3';
		$data['is_shipping'] = '0';
		$data['unsale_reason'] = '测试文本xTrKe0';
		$data['sku_change_data'] = '测试文本ADxGJI';
		$model = new PurProductModel();
		$model->data($data)->save();

		$data = [];
		$data['id'] = $model->id;
		$response = $this->request('getOne',$data);
		$model->destroy($model->id);

		//var_dump(json_encode($response,JSON_UNESCAPED_UNICODE));
	}


	public function testUpdate()
	{
		$data = [];
		$data['sku'] = '测试文本EybMtm';
		$data['product_name'] = '测试文本AYtFem';
		$data['product_category_id'] = '26813';
		$data['product_line_id'] = '48624';
		$data['product_status'] = '1';
		$data['product_img_url'] = '测试文本AKde4t';
		$data['product_thumb_url'] = '测试文本K4vkIi';
		$data['uploadimgs'] = '测试文本3lsBqU';
		$data['product_cn_link'] = '测试文本FzUjVL';
		$data['product_en_link'] = '测试文本t7r1fz';
		$data['create_id'] = '81758';
		$data['create_user_name'] = '测试文本LEk3lA';
		$data['create_time'] = '';
		$data['product_cost'] = '53979.4';
		$data['product_type'] = '2';
		$data['product_package_code'] = '测试文本JzepNS';
		$data['purchase_packaging'] = '测试文本EaoZCG';
		$data['supply_status'] = '2';
		$data['supplier_code'] = '测试文本ElijcN';
		$data['supplier_name'] = '测试文本W1QMFc';
		$data['purchase_price'] = '21481.9';
		$data['sale_attribute'] = '测试文本f09n3s';
		$data['note'] = '测试文本yf7XuA';
		$data['last_price'] = '21694.1';
		$data['avg_freight'] = '85301.7';
		$data['avg_purchase_cost'] = '76497.7';
		$data['is_drawback'] = '1';
		$data['tax_rate'] = '2';
		$data['ticketed_point'] = '85550.7';
		$data['declare_cname'] = '测试文本g6s1yw';
		$data['declare_unit'] = '测试文本kOL3y2';
		$data['export_model'] = '测试文本ESR7lp';
		$data['export_cname'] = '测试文本HG6faM';
		$data['export_ename'] = '测试文本YidIrb';
		$data['customs_code'] = '测试文本CYuhrc';
		$data['is_sample'] = '1';
		$data['is_multi'] = '3';
		$data['is_inspection'] = '2';
		$data['is_new'] = '0';
		$data['is_boutique'] = '2';
		$data['is_repackage'] = '2';
		$data['is_weightdot'] = '1';
		$data['is_relate_ali'] = '1';
		$data['is_equal_sup_id'] = '3';
		$data['is_equal_sup_name'] = '0';
		$data['relate_ali_name'] = '测试文本1xHquw';
		$data['is_abnormal'] = '3';
		$data['audit_status'] = '2';
		$data['days_sales_7'] = '32552';
		$data['days_sales_15'] = '77410';
		$data['days_sales_30'] = '26714';
		$data['days_sales_60'] = '68361';
		$data['days_sales_90'] = '45971';
		$data['sku_sale7'] = '测试文本ANHC0V';
		$data['state_type'] = '2';
		$data['is_pull_logis'] = '3';
		$data['original_start_qty'] = '38364';
		$data['original_start_qty_unit'] = '测试文本HwynVZ';
		$data['starting_qty'] = '17884';
		$data['starting_qty_unit'] = '测试文本rCYW3a';
		$data['ali_ratio_own'] = '84043';
		$data['ali_ratio_out'] = '97791';
		$data['is_invalid'] = '1';
		$data['is_from_multi'] = '2';
		$data['productismulti'] = '0';
		$data['producttype'] = '0';
		$data['days_sales_3'] = '66507';
		$data['product_weight'] = '48676.1';
		$data['coupon_rate'] = '24959.4';
		$data['maintain_ticketed_point'] = '3';
		$data['audit_status_log'] = '0';
		$data['record_change_time'] = '';
		$data['is_purchasing'] = '3';
		$data['new_flag'] = '3';
		$data['is_tongtu_purchase'] = '2';
		$data['is_new_purchase'] = '0';
		$data['is_overseas_first_order'] = '0';
		$data['is_old_purchase'] = '2';
		$data['is_gateway'] = '3';
		$data['inside_number'] = '18081';
		$data['box_size'] = '测试文本uhJqtY';
		$data['outer_box_volume'] = '30291.7';
		$data['product_volume'] = '64355';
		$data['sample_packaging_type'] = '测试文本nXEJPf';
		$data['sample_package_size'] = '测试文本A9WLo4';
		$data['sample_package_length'] = '44808.6';
		$data['sample_package_width'] = '90405.4';
		$data['sample_package_heigth'] = '21333.9';
		$data['sample_package_weight'] = '35549.2';
		$data['sku_message'] = '测试文本BPUO2S';
		$data['product_brand'] = '测试文本91iL7f';
		$data['product_model'] = '测试文本CJVzpM';
		$data['sku_state_type'] = '0';
		$data['is_customized'] = '2';
		$data['original_devliy'] = '91747.7';
		$data['devliy'] = '53657.1';
		$data['net_weight'] = '88476.5';
		$data['rought_weight'] = '53445.3';
		$data['development'] = '49584';
		$data['is_logo'] = '1';
		$data['logo_images'] = '测试文本xBGtva';
		$data['no_logo_images'] = '测试文本Af1gnm';
		$data['logo_images_thumb_url'] = '测试文本NgKo1F';
		$data['no_logo_images_thumb_url'] = '测试文本5qhs0R';
		$data['update_time'] = '';
		$data['long_delivery'] = '1';
		$data['is_consign'] = '1';
		$data['is_shipping'] = '2';
		$data['unsale_reason'] = '测试文本FL9bYG';
		$data['sku_change_data'] = '测试文本p2njHq';
		$model = new PurProductModel();
		$model->data($data)->save();

		$update = [];
		$update['id'] = $model->id;
		$update['sku'] = '测试文本9sab7H';
		$update['product_name'] = '测试文本YcRjmP';
		$update['product_category_id'] = '19798';
		$update['product_line_id'] = '88315';
		$update['product_status'] = '3';
		$update['product_img_url'] = '测试文本Wdq7hg';
		$update['product_thumb_url'] = '测试文本ZUnQGB';
		$update['uploadimgs'] = '测试文本o3f1Ug';
		$update['product_cn_link'] = '测试文本E3QpUa';
		$update['product_en_link'] = '测试文本VJuIyv';
		$update['create_id'] = '60640';
		$update['create_user_name'] = '测试文本ICqlm0';
		$update['create_time'] = '';
		$update['product_cost'] = '19213.6';
		$update['product_type'] = '0';
		$update['product_package_code'] = '测试文本OEz4AT';
		$update['purchase_packaging'] = '测试文本6tm4Wg';
		$update['supply_status'] = '2';
		$update['supplier_code'] = '测试文本BVyoXN';
		$update['supplier_name'] = '测试文本r3xeTU';
		$update['purchase_price'] = '56432.6';
		$update['sale_attribute'] = '测试文本zf4jos';
		$update['note'] = '测试文本klC4Wm';
		$update['last_price'] = '81450.5';
		$update['avg_freight'] = '35983.6';
		$update['avg_purchase_cost'] = '22814.3';
		$update['is_drawback'] = '2';
		$update['tax_rate'] = '0';
		$update['ticketed_point'] = '62035.4';
		$update['declare_cname'] = '测试文本BRIyhZ';
		$update['declare_unit'] = '测试文本1FcWdy';
		$update['export_model'] = '测试文本VOPoyv';
		$update['export_cname'] = '测试文本9iLtHG';
		$update['export_ename'] = '测试文本nz2wS9';
		$update['customs_code'] = '测试文本z9enyk';
		$update['is_sample'] = '2';
		$update['is_multi'] = '3';
		$update['is_inspection'] = '2';
		$update['is_new'] = '2';
		$update['is_boutique'] = '1';
		$update['is_repackage'] = '1';
		$update['is_weightdot'] = '2';
		$update['is_relate_ali'] = '3';
		$update['is_equal_sup_id'] = '3';
		$update['is_equal_sup_name'] = '1';
		$update['relate_ali_name'] = '测试文本6Beg2s';
		$update['is_abnormal'] = '0';
		$update['audit_status'] = '2';
		$update['days_sales_7'] = '83824';
		$update['days_sales_15'] = '87638';
		$update['days_sales_30'] = '76471';
		$update['days_sales_60'] = '28436';
		$update['days_sales_90'] = '17253';
		$update['sku_sale7'] = '测试文本IckXqZ';
		$update['state_type'] = '1';
		$update['is_pull_logis'] = '1';
		$update['original_start_qty'] = '63233';
		$update['original_start_qty_unit'] = '测试文本btvPN5';
		$update['starting_qty'] = '76691';
		$update['starting_qty_unit'] = '测试文本JByGFE';
		$update['ali_ratio_own'] = '34551';
		$update['ali_ratio_out'] = '26932';
		$update['is_invalid'] = '3';
		$update['is_from_multi'] = '2';
		$update['productismulti'] = '0';
		$update['producttype'] = '1';
		$update['days_sales_3'] = '89166';
		$update['product_weight'] = '64860.2';
		$update['coupon_rate'] = '41571.9';
		$update['maintain_ticketed_point'] = '0';
		$update['audit_status_log'] = '2';
		$update['record_change_time'] = '';
		$update['is_purchasing'] = '1';
		$update['new_flag'] = '3';
		$update['is_tongtu_purchase'] = '2';
		$update['is_new_purchase'] = '2';
		$update['is_overseas_first_order'] = '1';
		$update['is_old_purchase'] = '2';
		$update['is_gateway'] = '1';
		$update['inside_number'] = '10947';
		$update['box_size'] = '测试文本BKCJ5N';
		$update['outer_box_volume'] = '30795';
		$update['product_volume'] = '66389.5';
		$update['sample_packaging_type'] = '测试文本QF0u5p';
		$update['sample_package_size'] = '测试文本zOAjam';
		$update['sample_package_length'] = '60913.4';
		$update['sample_package_width'] = '38506.8';
		$update['sample_package_heigth'] = '24758.7';
		$update['sample_package_weight'] = '64613.4';
		$update['sku_message'] = '测试文本Nds5DK';
		$update['product_brand'] = '测试文本u7ldr4';
		$update['product_model'] = '测试文本LuRkaB';
		$update['sku_state_type'] = '2';
		$update['is_customized'] = '2';
		$update['original_devliy'] = '86584.5';
		$update['devliy'] = '58151.6';
		$update['net_weight'] = '78326.9';
		$update['rought_weight'] = '35627.4';
		$update['development'] = '44125';
		$update['is_logo'] = '3';
		$update['logo_images'] = '测试文本oXir7j';
		$update['no_logo_images'] = '测试文本o0tUFQ';
		$update['logo_images_thumb_url'] = '测试文本sIdPTH';
		$update['no_logo_images_thumb_url'] = '测试文本XmZFNp';
		$update['update_time'] = '';
		$update['long_delivery'] = '1';
		$update['is_consign'] = '2';
		$update['is_shipping'] = '2';
		$update['unsale_reason'] = '测试文本gCKab9';
		$update['sku_change_data'] = '测试文本lHVONR';
		$response = $this->request('update',$update);
		$model->destroy($model->id);
		//var_dump(json_encode($response,JSON_UNESCAPED_UNICODE));
	}


	public function testGetList()
	{
		$model = new PurProductModel();
		$data = [];
		$response = $this->request('getList',$data);

		//var_dump(json_encode($response,JSON_UNESCAPED_UNICODE));
	}


	public function testDel()
	{
		$data = [];
		$data['sku'] = '测试文本a5iPtx';
		$data['product_name'] = '测试文本CTY7oh';
		$data['product_category_id'] = '21998';
		$data['product_line_id'] = '25050';
		$data['product_status'] = '2';
		$data['product_img_url'] = '测试文本FuOVBo';
		$data['product_thumb_url'] = '测试文本gHwqer';
		$data['uploadimgs'] = '测试文本Ux2Hia';
		$data['product_cn_link'] = '测试文本KjD3Bh';
		$data['product_en_link'] = '测试文本7fxhmV';
		$data['create_id'] = '19547';
		$data['create_user_name'] = '测试文本YMaLAE';
		$data['create_time'] = '';
		$data['product_cost'] = '38974.2';
		$data['product_type'] = '3';
		$data['product_package_code'] = '测试文本Y4fTKV';
		$data['purchase_packaging'] = '测试文本7D4y3X';
		$data['supply_status'] = '2';
		$data['supplier_code'] = '测试文本HBThnU';
		$data['supplier_name'] = '测试文本HfKD8l';
		$data['purchase_price'] = '58783.5';
		$data['sale_attribute'] = '测试文本3QELOD';
		$data['note'] = '测试文本9Dxp8P';
		$data['last_price'] = '45029.8';
		$data['avg_freight'] = '68521.4';
		$data['avg_purchase_cost'] = '68151.6';
		$data['is_drawback'] = '3';
		$data['tax_rate'] = '0';
		$data['ticketed_point'] = '42787.6';
		$data['declare_cname'] = '测试文本HPCUwR';
		$data['declare_unit'] = '测试文本zLtH5i';
		$data['export_model'] = '测试文本nRSvJW';
		$data['export_cname'] = '测试文本07pmYf';
		$data['export_ename'] = '测试文本iBjXAK';
		$data['customs_code'] = '测试文本sRNkMA';
		$data['is_sample'] = '2';
		$data['is_multi'] = '1';
		$data['is_inspection'] = '0';
		$data['is_new'] = '3';
		$data['is_boutique'] = '0';
		$data['is_repackage'] = '3';
		$data['is_weightdot'] = '0';
		$data['is_relate_ali'] = '2';
		$data['is_equal_sup_id'] = '2';
		$data['is_equal_sup_name'] = '3';
		$data['relate_ali_name'] = '测试文本PbzRLJ';
		$data['is_abnormal'] = '3';
		$data['audit_status'] = '2';
		$data['days_sales_7'] = '63825';
		$data['days_sales_15'] = '64077';
		$data['days_sales_30'] = '95889';
		$data['days_sales_60'] = '96827';
		$data['days_sales_90'] = '47973';
		$data['sku_sale7'] = '测试文本zCtHDb';
		$data['state_type'] = '3';
		$data['is_pull_logis'] = '2';
		$data['original_start_qty'] = '11877';
		$data['original_start_qty_unit'] = '测试文本aVRDHm';
		$data['starting_qty'] = '10747';
		$data['starting_qty_unit'] = '测试文本8POLEa';
		$data['ali_ratio_own'] = '94829';
		$data['ali_ratio_out'] = '94913';
		$data['is_invalid'] = '0';
		$data['is_from_multi'] = '2';
		$data['productismulti'] = '1';
		$data['producttype'] = '0';
		$data['days_sales_3'] = '41580';
		$data['product_weight'] = '10319.9';
		$data['coupon_rate'] = '28394.7';
		$data['maintain_ticketed_point'] = '0';
		$data['audit_status_log'] = '0';
		$data['record_change_time'] = '';
		$data['is_purchasing'] = '1';
		$data['new_flag'] = '2';
		$data['is_tongtu_purchase'] = '3';
		$data['is_new_purchase'] = '0';
		$data['is_overseas_first_order'] = '0';
		$data['is_old_purchase'] = '1';
		$data['is_gateway'] = '0';
		$data['inside_number'] = '93174';
		$data['box_size'] = '测试文本2uFX0C';
		$data['outer_box_volume'] = '95915.5';
		$data['product_volume'] = '35092.4';
		$data['sample_packaging_type'] = '测试文本RTXI0A';
		$data['sample_package_size'] = '测试文本yjSgVa';
		$data['sample_package_length'] = '51685';
		$data['sample_package_width'] = '32978.6';
		$data['sample_package_heigth'] = '38065';
		$data['sample_package_weight'] = '47447.8';
		$data['sku_message'] = '测试文本deOpcE';
		$data['product_brand'] = '测试文本2IlpuC';
		$data['product_model'] = '测试文本bkMTqC';
		$data['sku_state_type'] = '2';
		$data['is_customized'] = '3';
		$data['original_devliy'] = '64625.3';
		$data['devliy'] = '70594.6';
		$data['net_weight'] = '79035.9';
		$data['rought_weight'] = '89316.2';
		$data['development'] = '88577';
		$data['is_logo'] = '3';
		$data['logo_images'] = '测试文本x6EmBh';
		$data['no_logo_images'] = '测试文本bz6AhZ';
		$data['logo_images_thumb_url'] = '测试文本uth4UJ';
		$data['no_logo_images_thumb_url'] = '测试文本YIKDCr';
		$data['update_time'] = '';
		$data['long_delivery'] = '2';
		$data['is_consign'] = '1';
		$data['is_shipping'] = '0';
		$data['unsale_reason'] = '测试文本7qceE0';
		$data['sku_change_data'] = '测试文本58ve60';
		$model = new PurProductModel();
		$model->data($data)->save();

		$delData = [];
		$delData['id'] = $model->id;
		$response = $this->request('delete',$delData);
		//var_dump(json_encode($response,JSON_UNESCAPED_UNICODE));
	}
}

