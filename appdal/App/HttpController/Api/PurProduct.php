<?php

namespace App\HttpController\Api;

use EasySwoole\Validate\Validate;
use App\Model\Api\PurProductModel;
use App\HttpController\Api\ApiBase;
use EasySwoole\Http\Message\Status;
use EasySwoole\Component\Context\ContextManager;
use EasySwoole\HttpAnnotation\AnnotationTag\Api;
use EasySwoole\HttpAnnotation\AnnotationTag\Param;
use EasySwoole\HttpAnnotation\AnnotationTag\Method;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiFail;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiGroup;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiSuccess;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiGroupAuth;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiDescription;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiSuccessParam;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiRequestExample;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiGroupDescription;
use EasySwoole\HttpAnnotation\AnnotationTag\InjectParamsContext;

/**
 * PurProduct
 * Class PurProduct
 * Create With ClassGeneration
 * @ApiGroup(groupName="/Api.PurProduct")
 * @ApiGroupAuth(name="")
 * @ApiGroupDescription("")
 */
class PurProduct extends ApiBase
{
	/**
	 * @Api(name="add",path="/Api/PurProduct/add")
	 * @ApiDescription("新增数据")
	 * @Method(allow={GET,POST})
	 * @InjectParamsContext(key="param")
	 * @ApiSuccessParam(name="code",description="状态码")
	 * @ApiSuccessParam(name="result",description="api请求结果")
	 * @ApiSuccessParam(name="msg",description="api提示信息")
	 * @ApiSuccess({"code":200,"result":[],"msg":"新增成功"})
	 * @ApiFail({"code":400,"result":[],"msg":"新增失败"})
	 * @Param(name="id",alias="ID",description="ID",lengthMax="11",required="")
	 * @Param(name="sku",alias="产品SKU",description="产品SKU",lengthMax="100",required="",defaultValue="")
	 * @Param(name="product_name",alias="产品名称",description="产品名称",lengthMax="200",required="",defaultValue="")
	 * @Param(name="product_category_id",alias="产品类目ID",description="产品类目ID",lengthMax="11",required="",defaultValue="0")
	 * @Param(name="product_line_id",alias="产品线ID",description="产品线ID",lengthMax="4",required="",defaultValue="0")
	 * @Param(name="product_status",alias="产品状态",description="产品状态",lengthMax="2",required="",defaultValue="0")
	 * @Param(name="product_img_url",alias="图片路径",description="图片路径",lengthMax="500",required="",defaultValue="")
	 * @Param(name="product_thumb_url",alias="缩略图图片路径",description="缩略图图片路径",lengthMax="500",required="",defaultValue="")
	 * @Param(name="uploadimgs",alias="图片信息",description="图片信息",required="")
	 * @Param(name="product_cn_link",alias="采购中文地址",description="采购中文地址",lengthMax="500",required="",defaultValue="")
	 * @Param(name="product_en_link",alias="采购英文地址",description="采购英文地址",lengthMax="500",required="",defaultValue="")
	 * @Param(name="create_id",alias="开发人员",description="开发人员",lengthMax="6",required="",defaultValue="0")
	 * @Param(name="create_user_name",alias="开发人姓名",description="开发人姓名",lengthMax="30",required="",defaultValue="")
	 * @Param(name="create_time",alias="开发时间",description="开发时间",required="",defaultValue="0000-00-00 00:00:00")
	 * @Param(name="product_cost",alias="开发成本",description="开发成本",lengthMax="Array",required="",defaultValue="0.000")
	 * @Param(name="product_type",alias="是否捆绑（1.不是,2.是）",description="是否捆绑（1.不是,2.是）",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="product_package_code",alias="包装[sku]",description="包装[sku]",lengthMax="100",required="",defaultValue="")
	 * @Param(name="purchase_packaging",alias="采购包装",description="采购包装",lengthMax="200",required="",defaultValue="")
	 * @Param(name="supply_status",alias="货源状态(1.正常,2.停产,3.断货,10:停产找货中)",description="货源状态(1.正常,2.停产,3.断货,10:停产找货中)",lengthMax="1",required="",defaultValue="1")
	 * @Param(name="supplier_code",alias="默认供应商CODE",description="默认供应商CODE",lengthMax="50",required="",defaultValue="")
	 * @Param(name="supplier_name",alias="默认供应商名称",description="默认供应商名称",lengthMax="50",required="",defaultValue="")
	 * @Param(name="purchase_price",alias="采购单价（供应商报价）",description="采购单价（供应商报价）",lengthMax="Array",required="",defaultValue="0.000")
	 * @Param(name="sale_attribute",alias="销售属性",description="销售属性",required="")
	 * @Param(name="note",alias="开发备注",description="开发备注",required="")
	 * @Param(name="last_price",alias="产品最新采购价",description="产品最新采购价",lengthMax="Array",required="",defaultValue="0.000")
	 * @Param(name="avg_freight",alias="平均运费成本",description="平均运费成本",lengthMax="Array",required="",defaultValue="0.000")
	 * @Param(name="avg_purchase_cost",alias="平均采购成本",description="平均采购成本",lengthMax="Array",required="",defaultValue="0.000")
	 * @Param(name="is_drawback",alias="是否可退税(0.否,1.可退税)",description="是否可退税(0.否,1.可退税)",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="tax_rate",alias="出口退税税率（*100,存整数）",description="出口退税税率（*100,存整数）",lengthMax="3",required="",defaultValue="0")
	 * @Param(name="ticketed_point",alias="税点（*100,存整数,开票点）",description="税点（*100,存整数,开票点）",lengthMax="Array",required="",defaultValue="0.000")
	 * @Param(name="declare_cname",alias="申报中文名",description="申报中文名",lengthMax="255",required="",defaultValue="")
	 * @Param(name="declare_unit",alias="申报单位",description="申报单位",lengthMax="255",required="",defaultValue="")
	 * @Param(name="export_model",alias="出口申报型号",description="出口申报型号",lengthMax="100",required="",defaultValue="")
	 * @Param(name="export_cname",alias="出口申报中文名",description="出口申报中文名",lengthMax="100",required="",defaultValue="")
	 * @Param(name="export_ename",alias="出口申报英文名(未使用)",description="出口申报英文名(未使用)",lengthMax="100",required="",defaultValue="")
	 * @Param(name="customs_code",alias="出口海关编码",description="出口海关编码",lengthMax="250",required="",defaultValue="")
	 * @Param(name="is_sample",alias="是否是样品（0.否,1.是）",description="是否是样品（0.否,1.是）",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_multi",alias="是否多属性(0.单品,1.多属性单品,2.多属性组合产品)",description="是否多属性(0.单品,1.多属性单品,2.多属性组合产品)",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_inspection",alias="是否商检（1.不商检,2.商检）",description="是否商检（1.不商检,2.商检）",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_new",alias="是否新品（0.否,1.是）",description="是否新品（0.否,1.是）",lengthMax="1",required="",defaultValue="1")
	 * @Param(name="is_boutique",alias="是否精品（0.否,1.是）",description="是否精品（0.否,1.是）",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_repackage",alias="是否二次包装（0.否,1.是）",description="是否二次包装（0.否,1.是）",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_weightdot",alias="是否重点SKU（0.否,1.是）",description="是否重点SKU（0.否,1.是）",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_relate_ali",alias="是否关联1688",description="是否关联1688",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_equal_sup_id",alias="1688供应商ID是否一致（0.未验证,1.一致,2.不一致）",description="1688供应商ID是否一致（0.未验证,1.一致,2.不一致）",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_equal_sup_name",alias="1688供应商名称是否一致（0.未验证,1.一致,2.不一致）",description="1688供应商名称是否一致（0.未验证,1.一致,2.不一致）",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="relate_ali_name",alias="关联操作人",description="关联操作人",lengthMax="10",required="",defaultValue="")
	 * @Param(name="is_abnormal",alias="是否异常 1[否] 2[是]",description="是否异常 1[否] 2[是]",lengthMax="1",required="",defaultValue="1")
	 * @Param(name="audit_status",alias="审核状态 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]",description="审核状态 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="days_sales_7",alias="7天销量",description="7天销量",lengthMax="11",required="",defaultValue="0")
	 * @Param(name="days_sales_15",alias="15天销量",description="15天销量",lengthMax="11",required="",defaultValue="0")
	 * @Param(name="days_sales_30",alias="30天销量",description="30天销量",lengthMax="11",required="",defaultValue="0")
	 * @Param(name="days_sales_60",alias="60天销量",description="60天销量",lengthMax="11",required="",defaultValue="0")
	 * @Param(name="days_sales_90",alias="90天销量",description="90天销量",lengthMax="11",required="",defaultValue="0")
	 * @Param(name="sku_sale7",lengthMax="255",required="")
	 * @Param(name="state_type",alias="开发类型 1常规产品  2试卖产品  3亚马逊产品  4通途产品  5亚马逊服装  6国内转海外仓  9代销产品",description="开发类型 1常规产品  2试卖产品  3亚马逊产品  4通途产品  5亚马逊服装  6国内转海外仓  9代销产品",lengthMax="3",required="",defaultValue="0")
	 * @Param(name="is_pull_logis",alias="是否从物流获取商检信息",description="是否从物流获取商检信息",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="original_start_qty",alias="原始最小起订量",description="原始最小起订量",lengthMax="10",required="",defaultValue="0")
	 * @Param(name="original_start_qty_unit",alias="原始最小起订量单位",description="原始最小起订量单位",lengthMax="100",required="",defaultValue="")
	 * @Param(name="starting_qty",alias="最小起订量",description="最小起订量",lengthMax="10",required="",defaultValue="0")
	 * @Param(name="starting_qty_unit",alias="最小起订量单位",description="最小起订量单位",lengthMax="100",required="",defaultValue="")
	 * @Param(name="ali_ratio_own",alias="单位对应关系（内部）",description="单位对应关系（内部）",lengthMax="10",required="",defaultValue="1")
	 * @Param(name="ali_ratio_out",alias="单位对应关系（外部）",description="单位对应关系（外部）",lengthMax="10",required="",defaultValue="1")
	 * @Param(name="is_invalid",alias="标识SKU链接是否失效 0 标识有效果，1标识失效",description="标识SKU链接是否失效 0 标识有效果，1标识失效",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_from_multi",alias="是否从ERP 获取商品信息 （JAVA）",description="是否从ERP 获取商品信息 （JAVA）",lengthMax="1",required="")
	 * @Param(name="productismulti",alias="0: 普通 1: 多属性单品 2: 多属性组合",description="0: 普通 1: 多属性单品 2: 多属性组合",lengthMax="1",required="")
	 * @Param(name="producttype",alias="1普通，2捆绑销售",description="1普通，2捆绑销售",lengthMax="1",required="")
	 * @Param(name="days_sales_3",alias="3天销量",description="3天销量",lengthMax="11",required="")
	 * @Param(name="product_weight",alias="样品包装重量",description="样品包装重量",lengthMax="Array",required="",defaultValue="0.00")
	 * @Param(name="coupon_rate",alias="票面税率",description="票面税率",lengthMax="Array",required="",defaultValue="0.000")
	 * @Param(name="maintain_ticketed_point",alias="开票点是否维护过，0表示没有维护，1表示维护",description="开票点是否维护过，0表示没有维护，1表示维护",lengthMax="1",required="",defaultValue="1")
	 * @Param(name="audit_status_log",alias="SKU 审核状态,同步product_update_log 表 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]5[待财务审核]",description="SKU 审核状态,同步product_update_log 表 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]5[待财务审核]",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="record_change_time",alias="记录修改时间(供应商门户用)",description="记录修改时间(供应商门户用)",required="",defaultValue="0000-00-00 00:00:00")
	 * @Param(name="is_purchasing",alias="是否需要代采 1表示否2表示是",description="是否需要代采 1表示否2表示是",lengthMax="1",required="",defaultValue="1")
	 * @Param(name="new_flag",alias="1表示查询过 0表示未查询",description="1表示查询过 0表示未查询",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_tongtu_purchase",alias="1：表示在老采购系统下过单，2表示没有",description="1：表示在老采购系统下过单，2表示没有",lengthMax="1",required="",defaultValue="2")
	 * @Param(name="is_new_purchase",alias="1：表示在采购系统下过单，2表示没有",description="1：表示在采购系统下过单，2表示没有",lengthMax="1",required="",defaultValue="2")
	 * @Param(name="is_overseas_first_order",alias="是否为海外首单 0表示否，1表示是",description="是否为海外首单 0表示否，1表示是",lengthMax="1",required="",defaultValue="1")
	 * @Param(name="is_old_purchase",alias="1：表示在老采购系统下过单，2表示没有",description="1：表示在老采购系统下过单，2表示没有",lengthMax="1",required="",defaultValue="2")
	 * @Param(name="is_gateway",alias="是否需要推送到门户系统 1表示是 0表示否",description="是否需要推送到门户系统 1表示是 0表示否",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="inside_number",alias="箱内数",description="箱内数",lengthMax="11",required="",defaultValue="0")
	 * @Param(name="box_size",alias="外箱尺寸",description="外箱尺寸",lengthMax="255",required="",defaultValue="")
	 * @Param(name="outer_box_volume",alias="外箱体积",description="外箱体积",lengthMax="Array",required="",defaultValue="0.00")
	 * @Param(name="product_volume",alias="产品体积",description="产品体积",lengthMax="Array",required="",defaultValue="0.00")
	 * @Param(name="sample_packaging_type",alias="来样包装类型",description="来样包装类型",lengthMax="255",required="",defaultValue="")
	 * @Param(name="sample_package_size",alias="样品包装尺寸",description="样品包装尺寸",lengthMax="255",required="",defaultValue="")
	 * @Param(name="sample_package_length",alias="包装产品长度",description="包装产品长度",lengthMax="Array",required="",defaultValue="0.00")
	 * @Param(name="sample_package_width",alias="包装产品宽度",description="包装产品宽度",lengthMax="Array",required="",defaultValue="0.00")
	 * @Param(name="sample_package_heigth",alias="包装产品高度",description="包装产品高度",lengthMax="Array",required="",defaultValue="0.00")
	 * @Param(name="sample_package_weight",alias="样品包装重量",description="样品包装重量",lengthMax="Array",required="",defaultValue="0.00")
	 * @Param(name="sku_message",alias="SKU商品参数",description="SKU商品参数",required="")
	 * @Param(name="product_brand",alias="SKU品牌",description="SKU品牌",lengthMax="255",required="",defaultValue="")
	 * @Param(name="product_model",alias="品牌型号",description="品牌型号",lengthMax="255",required="",defaultValue="")
	 * @Param(name="sku_state_type",alias="是否国内转海外 0表示否 6表示是",description="是否国内转海外 0表示否 6表示是",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_customized",alias="是否定制 1表示是 2表示否",description="是否定制 1表示是 2表示否",lengthMax="1",required="",defaultValue="2")
	 * @Param(name="original_devliy",lengthMax="Array",required="")
	 * @Param(name="devliy",lengthMax="Array",required="")
	 * @Param(name="net_weight",alias="净重",description="净重",lengthMax="Array",required="",defaultValue="0.00")
	 * @Param(name="rought_weight",alias="毛重",description="毛重",lengthMax="Array",required="",defaultValue="0.00")
	 * @Param(name="development",alias="开发类型（和产品系统保持一致） 1:常规产品 2:试卖产品 3:FBA精品 4:代销产品, 默认0",description="开发类型（和产品系统保持一致） 1:常规产品 2:试卖产品 3:FBA精品 4:代销产品, 默认0",lengthMax="4",required="",defaultValue="0")
	 * @Param(name="is_logo",alias="是否有LOGO 0表示无 1表示有",description="是否有LOGO 0表示无 1表示有",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="logo_images",alias="有LOGO 图片",description="有LOGO 图片",lengthMax="300",required="",defaultValue="")
	 * @Param(name="no_logo_images",alias="无LOGO 图片",description="无LOGO 图片",lengthMax="300",required="")
	 * @Param(name="logo_images_thumb_url",alias="有LOGO 图片压缩图片地址",description="有LOGO 图片压缩图片地址",lengthMax="255",required="",defaultValue="")
	 * @Param(name="no_logo_images_thumb_url",alias="有LOGO 图片压缩",description="有LOGO 图片压缩",lengthMax="255",required="",defaultValue="")
	 * @Param(name="update_time",alias="最近修改时间",description="最近修改时间",required="",defaultValue="CURRENT_TIMESTAMP")
	 * @Param(name="long_delivery",alias="是否为超长交期 1表示 否，2表示是",description="是否为超长交期 1表示 否，2表示是",lengthMax="1",required="",defaultValue="1")
	 * @Param(name="is_consign",alias="是否支持分销：0否，1是",description="是否支持分销：0否，1是",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="is_shipping",alias="是否包邮 1表示是 2表示否",description="是否包邮 1表示是 2表示否",lengthMax="1",required="",defaultValue="0")
	 * @Param(name="unsale_reason",alias="停售原因",description="停售原因",lengthMax="255",required="",defaultValue="")
	 * @Param(name="sku_change_data",alias="SKU变更类型，数据来源产品系统推送",description="SKU变更类型，数据来源产品系统推送",lengthMax="255",required="",defaultValue="")
	 */
	public function add()
	{
		$param = ContextManager::getInstance()->get('param');
		$data = [
		    'id'=>$param['id'],
		    'sku'=>$param['sku'],
		    'product_name'=>$param['product_name'],
		    'product_category_id'=>$param['product_category_id'],
		    'product_line_id'=>$param['product_line_id'],
		    'product_status'=>$param['product_status'],
		    'product_img_url'=>$param['product_img_url'],
		    'product_thumb_url'=>$param['product_thumb_url'],
		    'uploadimgs'=>$param['uploadimgs'],
		    'product_cn_link'=>$param['product_cn_link'],
		    'product_en_link'=>$param['product_en_link'],
		    'create_id'=>$param['create_id'],
		    'create_user_name'=>$param['create_user_name'],
		    'create_time'=>$param['create_time'],
		    'product_cost'=>$param['product_cost'],
		    'product_type'=>$param['product_type'],
		    'product_package_code'=>$param['product_package_code'],
		    'purchase_packaging'=>$param['purchase_packaging'],
		    'supply_status'=>$param['supply_status'],
		    'supplier_code'=>$param['supplier_code'],
		    'supplier_name'=>$param['supplier_name'],
		    'purchase_price'=>$param['purchase_price'],
		    'sale_attribute'=>$param['sale_attribute'],
		    'note'=>$param['note'],
		    'last_price'=>$param['last_price'],
		    'avg_freight'=>$param['avg_freight'],
		    'avg_purchase_cost'=>$param['avg_purchase_cost'],
		    'is_drawback'=>$param['is_drawback'],
		    'tax_rate'=>$param['tax_rate'],
		    'ticketed_point'=>$param['ticketed_point'],
		    'declare_cname'=>$param['declare_cname'],
		    'declare_unit'=>$param['declare_unit'],
		    'export_model'=>$param['export_model'],
		    'export_cname'=>$param['export_cname'],
		    'export_ename'=>$param['export_ename'],
		    'customs_code'=>$param['customs_code'],
		    'is_sample'=>$param['is_sample'],
		    'is_multi'=>$param['is_multi'],
		    'is_inspection'=>$param['is_inspection'],
		    'is_new'=>$param['is_new'],
		    'is_boutique'=>$param['is_boutique'],
		    'is_repackage'=>$param['is_repackage'],
		    'is_weightdot'=>$param['is_weightdot'],
		    'is_relate_ali'=>$param['is_relate_ali'],
		    'is_equal_sup_id'=>$param['is_equal_sup_id'],
		    'is_equal_sup_name'=>$param['is_equal_sup_name'],
		    'relate_ali_name'=>$param['relate_ali_name'],
		    'is_abnormal'=>$param['is_abnormal'],
		    'audit_status'=>$param['audit_status'],
		    'days_sales_7'=>$param['days_sales_7'],
		    'days_sales_15'=>$param['days_sales_15'],
		    'days_sales_30'=>$param['days_sales_30'],
		    'days_sales_60'=>$param['days_sales_60'],
		    'days_sales_90'=>$param['days_sales_90'],
		    'sku_sale7'=>$param['sku_sale7'],
		    'state_type'=>$param['state_type'],
		    'is_pull_logis'=>$param['is_pull_logis'],
		    'original_start_qty'=>$param['original_start_qty'],
		    'original_start_qty_unit'=>$param['original_start_qty_unit'],
		    'starting_qty'=>$param['starting_qty'],
		    'starting_qty_unit'=>$param['starting_qty_unit'],
		    'ali_ratio_own'=>$param['ali_ratio_own'],
		    'ali_ratio_out'=>$param['ali_ratio_out'],
		    'is_invalid'=>$param['is_invalid'],
		    'is_from_multi'=>$param['is_from_multi'],
		    'productismulti'=>$param['productismulti'],
		    'producttype'=>$param['producttype'],
		    'days_sales_3'=>$param['days_sales_3'],
		    'product_weight'=>$param['product_weight'],
		    'coupon_rate'=>$param['coupon_rate'],
		    'maintain_ticketed_point'=>$param['maintain_ticketed_point'],
		    'audit_status_log'=>$param['audit_status_log'],
		    'record_change_time'=>$param['record_change_time'],
		    'is_purchasing'=>$param['is_purchasing'],
		    'new_flag'=>$param['new_flag'],
		    'is_tongtu_purchase'=>$param['is_tongtu_purchase'],
		    'is_new_purchase'=>$param['is_new_purchase'],
		    'is_overseas_first_order'=>$param['is_overseas_first_order'],
		    'is_old_purchase'=>$param['is_old_purchase'],
		    'is_gateway'=>$param['is_gateway'],
		    'inside_number'=>$param['inside_number'],
		    'box_size'=>$param['box_size'],
		    'outer_box_volume'=>$param['outer_box_volume'],
		    'product_volume'=>$param['product_volume'],
		    'sample_packaging_type'=>$param['sample_packaging_type'],
		    'sample_package_size'=>$param['sample_package_size'],
		    'sample_package_length'=>$param['sample_package_length'],
		    'sample_package_width'=>$param['sample_package_width'],
		    'sample_package_heigth'=>$param['sample_package_heigth'],
		    'sample_package_weight'=>$param['sample_package_weight'],
		    'sku_message'=>$param['sku_message'],
		    'product_brand'=>$param['product_brand'],
		    'product_model'=>$param['product_model'],
		    'sku_state_type'=>$param['sku_state_type'],
		    'is_customized'=>$param['is_customized'],
		    'original_devliy'=>$param['original_devliy'],
		    'devliy'=>$param['devliy'],
		    'net_weight'=>$param['net_weight'],
		    'rought_weight'=>$param['rought_weight'],
		    'development'=>$param['development'],
		    'is_logo'=>$param['is_logo'],
		    'logo_images'=>$param['logo_images'],
		    'no_logo_images'=>$param['no_logo_images'],
		    'logo_images_thumb_url'=>$param['logo_images_thumb_url'],
		    'no_logo_images_thumb_url'=>$param['no_logo_images_thumb_url'],
		    'update_time'=>$param['update_time'],
		    'long_delivery'=>$param['long_delivery'],
		    'is_consign'=>$param['is_consign'],
		    'is_shipping'=>$param['is_shipping'],
		    'unsale_reason'=>$param['unsale_reason'],
		    'sku_change_data'=>$param['sku_change_data'],
		];
		$model = new PurProductModel($data);
		$model->save();
		$this->writeJson(Status::CODE_OK, $model->toArray(), "新增成功");
	}


	/**
	 * @Api(name="update",path="/Api/PurProduct/update")
	 * @ApiDescription("更新数据")
	 * @Method(allow={GET,POST})
	 * @InjectParamsContext(key="param")
	 * @ApiSuccessParam(name="code",description="状态码")
	 * @ApiSuccessParam(name="result",description="api请求结果")
	 * @ApiSuccessParam(name="msg",description="api提示信息")
	 * @ApiSuccess({"code":200,"result":[],"msg":"更新成功"})
	 * @ApiFail({"code":400,"result":[],"msg":"更新失败"})
	 * @Param(name="id",alias="ID",description="ID",lengthMax="11",required="")
	 * @Param(name="sku",alias="产品SKU",description="产品SKU",lengthMax="100",optional="",defaultValue="")
	 * @Param(name="product_name",alias="产品名称",description="产品名称",lengthMax="200",optional="",defaultValue="")
	 * @Param(name="product_category_id",alias="产品类目ID",description="产品类目ID",lengthMax="11",optional="",defaultValue="0")
	 * @Param(name="product_line_id",alias="产品线ID",description="产品线ID",lengthMax="4",optional="",defaultValue="0")
	 * @Param(name="product_status",alias="产品状态",description="产品状态",lengthMax="2",optional="",defaultValue="0")
	 * @Param(name="product_img_url",alias="图片路径",description="图片路径",lengthMax="500",optional="",defaultValue="")
	 * @Param(name="product_thumb_url",alias="缩略图图片路径",description="缩略图图片路径",lengthMax="500",optional="",defaultValue="")
	 * @Param(name="uploadimgs",alias="图片信息",description="图片信息",optional="")
	 * @Param(name="product_cn_link",alias="采购中文地址",description="采购中文地址",lengthMax="500",optional="",defaultValue="")
	 * @Param(name="product_en_link",alias="采购英文地址",description="采购英文地址",lengthMax="500",optional="",defaultValue="")
	 * @Param(name="create_id",alias="开发人员",description="开发人员",lengthMax="6",optional="",defaultValue="0")
	 * @Param(name="create_user_name",alias="开发人姓名",description="开发人姓名",lengthMax="30",optional="",defaultValue="")
	 * @Param(name="create_time",alias="开发时间",description="开发时间",optional="",defaultValue="0000-00-00 00:00:00")
	 * @Param(name="product_cost",alias="开发成本",description="开发成本",lengthMax="Array",optional="",defaultValue="0.000")
	 * @Param(name="product_type",alias="是否捆绑（1.不是,2.是）",description="是否捆绑（1.不是,2.是）",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="product_package_code",alias="包装[sku]",description="包装[sku]",lengthMax="100",optional="",defaultValue="")
	 * @Param(name="purchase_packaging",alias="采购包装",description="采购包装",lengthMax="200",optional="",defaultValue="")
	 * @Param(name="supply_status",alias="货源状态(1.正常,2.停产,3.断货,10:停产找货中)",description="货源状态(1.正常,2.停产,3.断货,10:停产找货中)",lengthMax="1",optional="",defaultValue="1")
	 * @Param(name="supplier_code",alias="默认供应商CODE",description="默认供应商CODE",lengthMax="50",optional="",defaultValue="")
	 * @Param(name="supplier_name",alias="默认供应商名称",description="默认供应商名称",lengthMax="50",optional="",defaultValue="")
	 * @Param(name="purchase_price",alias="采购单价（供应商报价）",description="采购单价（供应商报价）",lengthMax="Array",optional="",defaultValue="0.000")
	 * @Param(name="sale_attribute",alias="销售属性",description="销售属性",optional="")
	 * @Param(name="note",alias="开发备注",description="开发备注",optional="")
	 * @Param(name="last_price",alias="产品最新采购价",description="产品最新采购价",lengthMax="Array",optional="",defaultValue="0.000")
	 * @Param(name="avg_freight",alias="平均运费成本",description="平均运费成本",lengthMax="Array",optional="",defaultValue="0.000")
	 * @Param(name="avg_purchase_cost",alias="平均采购成本",description="平均采购成本",lengthMax="Array",optional="",defaultValue="0.000")
	 * @Param(name="is_drawback",alias="是否可退税(0.否,1.可退税)",description="是否可退税(0.否,1.可退税)",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="tax_rate",alias="出口退税税率（*100,存整数）",description="出口退税税率（*100,存整数）",lengthMax="3",optional="",defaultValue="0")
	 * @Param(name="ticketed_point",alias="税点（*100,存整数,开票点）",description="税点（*100,存整数,开票点）",lengthMax="Array",optional="",defaultValue="0.000")
	 * @Param(name="declare_cname",alias="申报中文名",description="申报中文名",lengthMax="255",optional="",defaultValue="")
	 * @Param(name="declare_unit",alias="申报单位",description="申报单位",lengthMax="255",optional="",defaultValue="")
	 * @Param(name="export_model",alias="出口申报型号",description="出口申报型号",lengthMax="100",optional="",defaultValue="")
	 * @Param(name="export_cname",alias="出口申报中文名",description="出口申报中文名",lengthMax="100",optional="",defaultValue="")
	 * @Param(name="export_ename",alias="出口申报英文名(未使用)",description="出口申报英文名(未使用)",lengthMax="100",optional="",defaultValue="")
	 * @Param(name="customs_code",alias="出口海关编码",description="出口海关编码",lengthMax="250",optional="",defaultValue="")
	 * @Param(name="is_sample",alias="是否是样品（0.否,1.是）",description="是否是样品（0.否,1.是）",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_multi",alias="是否多属性(0.单品,1.多属性单品,2.多属性组合产品)",description="是否多属性(0.单品,1.多属性单品,2.多属性组合产品)",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_inspection",alias="是否商检（1.不商检,2.商检）",description="是否商检（1.不商检,2.商检）",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_new",alias="是否新品（0.否,1.是）",description="是否新品（0.否,1.是）",lengthMax="1",optional="",defaultValue="1")
	 * @Param(name="is_boutique",alias="是否精品（0.否,1.是）",description="是否精品（0.否,1.是）",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_repackage",alias="是否二次包装（0.否,1.是）",description="是否二次包装（0.否,1.是）",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_weightdot",alias="是否重点SKU（0.否,1.是）",description="是否重点SKU（0.否,1.是）",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_relate_ali",alias="是否关联1688",description="是否关联1688",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_equal_sup_id",alias="1688供应商ID是否一致（0.未验证,1.一致,2.不一致）",description="1688供应商ID是否一致（0.未验证,1.一致,2.不一致）",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_equal_sup_name",alias="1688供应商名称是否一致（0.未验证,1.一致,2.不一致）",description="1688供应商名称是否一致（0.未验证,1.一致,2.不一致）",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="relate_ali_name",alias="关联操作人",description="关联操作人",lengthMax="10",optional="",defaultValue="")
	 * @Param(name="is_abnormal",alias="是否异常 1[否] 2[是]",description="是否异常 1[否] 2[是]",lengthMax="1",optional="",defaultValue="1")
	 * @Param(name="audit_status",alias="审核状态 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]",description="审核状态 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="days_sales_7",alias="7天销量",description="7天销量",lengthMax="11",optional="",defaultValue="0")
	 * @Param(name="days_sales_15",alias="15天销量",description="15天销量",lengthMax="11",optional="",defaultValue="0")
	 * @Param(name="days_sales_30",alias="30天销量",description="30天销量",lengthMax="11",optional="",defaultValue="0")
	 * @Param(name="days_sales_60",alias="60天销量",description="60天销量",lengthMax="11",optional="",defaultValue="0")
	 * @Param(name="days_sales_90",alias="90天销量",description="90天销量",lengthMax="11",optional="",defaultValue="0")
	 * @Param(name="sku_sale7",lengthMax="255",optional="")
	 * @Param(name="state_type",alias="开发类型 1常规产品  2试卖产品  3亚马逊产品  4通途产品  5亚马逊服装  6国内转海外仓  9代销产品",description="开发类型 1常规产品  2试卖产品  3亚马逊产品  4通途产品  5亚马逊服装  6国内转海外仓  9代销产品",lengthMax="3",optional="",defaultValue="0")
	 * @Param(name="is_pull_logis",alias="是否从物流获取商检信息",description="是否从物流获取商检信息",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="original_start_qty",alias="原始最小起订量",description="原始最小起订量",lengthMax="10",optional="",defaultValue="0")
	 * @Param(name="original_start_qty_unit",alias="原始最小起订量单位",description="原始最小起订量单位",lengthMax="100",optional="",defaultValue="")
	 * @Param(name="starting_qty",alias="最小起订量",description="最小起订量",lengthMax="10",optional="",defaultValue="0")
	 * @Param(name="starting_qty_unit",alias="最小起订量单位",description="最小起订量单位",lengthMax="100",optional="",defaultValue="")
	 * @Param(name="ali_ratio_own",alias="单位对应关系（内部）",description="单位对应关系（内部）",lengthMax="10",optional="",defaultValue="1")
	 * @Param(name="ali_ratio_out",alias="单位对应关系（外部）",description="单位对应关系（外部）",lengthMax="10",optional="",defaultValue="1")
	 * @Param(name="is_invalid",alias="标识SKU链接是否失效 0 标识有效果，1标识失效",description="标识SKU链接是否失效 0 标识有效果，1标识失效",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_from_multi",alias="是否从ERP 获取商品信息 （JAVA）",description="是否从ERP 获取商品信息 （JAVA）",lengthMax="1",optional="")
	 * @Param(name="productismulti",alias="0: 普通 1: 多属性单品 2: 多属性组合",description="0: 普通 1: 多属性单品 2: 多属性组合",lengthMax="1",optional="")
	 * @Param(name="producttype",alias="1普通，2捆绑销售",description="1普通，2捆绑销售",lengthMax="1",optional="")
	 * @Param(name="days_sales_3",alias="3天销量",description="3天销量",lengthMax="11",optional="")
	 * @Param(name="product_weight",alias="样品包装重量",description="样品包装重量",lengthMax="Array",optional="",defaultValue="0.00")
	 * @Param(name="coupon_rate",alias="票面税率",description="票面税率",lengthMax="Array",optional="",defaultValue="0.000")
	 * @Param(name="maintain_ticketed_point",alias="开票点是否维护过，0表示没有维护，1表示维护",description="开票点是否维护过，0表示没有维护，1表示维护",lengthMax="1",optional="",defaultValue="1")
	 * @Param(name="audit_status_log",alias="SKU 审核状态,同步product_update_log 表 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]5[待财务审核]",description="SKU 审核状态,同步product_update_log 表 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]5[待财务审核]",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="record_change_time",alias="记录修改时间(供应商门户用)",description="记录修改时间(供应商门户用)",optional="",defaultValue="0000-00-00 00:00:00")
	 * @Param(name="is_purchasing",alias="是否需要代采 1表示否2表示是",description="是否需要代采 1表示否2表示是",lengthMax="1",optional="",defaultValue="1")
	 * @Param(name="new_flag",alias="1表示查询过 0表示未查询",description="1表示查询过 0表示未查询",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_tongtu_purchase",alias="1：表示在老采购系统下过单，2表示没有",description="1：表示在老采购系统下过单，2表示没有",lengthMax="1",optional="",defaultValue="2")
	 * @Param(name="is_new_purchase",alias="1：表示在采购系统下过单，2表示没有",description="1：表示在采购系统下过单，2表示没有",lengthMax="1",optional="",defaultValue="2")
	 * @Param(name="is_overseas_first_order",alias="是否为海外首单 0表示否，1表示是",description="是否为海外首单 0表示否，1表示是",lengthMax="1",optional="",defaultValue="1")
	 * @Param(name="is_old_purchase",alias="1：表示在老采购系统下过单，2表示没有",description="1：表示在老采购系统下过单，2表示没有",lengthMax="1",optional="",defaultValue="2")
	 * @Param(name="is_gateway",alias="是否需要推送到门户系统 1表示是 0表示否",description="是否需要推送到门户系统 1表示是 0表示否",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="inside_number",alias="箱内数",description="箱内数",lengthMax="11",optional="",defaultValue="0")
	 * @Param(name="box_size",alias="外箱尺寸",description="外箱尺寸",lengthMax="255",optional="",defaultValue="")
	 * @Param(name="outer_box_volume",alias="外箱体积",description="外箱体积",lengthMax="Array",optional="",defaultValue="0.00")
	 * @Param(name="product_volume",alias="产品体积",description="产品体积",lengthMax="Array",optional="",defaultValue="0.00")
	 * @Param(name="sample_packaging_type",alias="来样包装类型",description="来样包装类型",lengthMax="255",optional="",defaultValue="")
	 * @Param(name="sample_package_size",alias="样品包装尺寸",description="样品包装尺寸",lengthMax="255",optional="",defaultValue="")
	 * @Param(name="sample_package_length",alias="包装产品长度",description="包装产品长度",lengthMax="Array",optional="",defaultValue="0.00")
	 * @Param(name="sample_package_width",alias="包装产品宽度",description="包装产品宽度",lengthMax="Array",optional="",defaultValue="0.00")
	 * @Param(name="sample_package_heigth",alias="包装产品高度",description="包装产品高度",lengthMax="Array",optional="",defaultValue="0.00")
	 * @Param(name="sample_package_weight",alias="样品包装重量",description="样品包装重量",lengthMax="Array",optional="",defaultValue="0.00")
	 * @Param(name="sku_message",alias="SKU商品参数",description="SKU商品参数",optional="")
	 * @Param(name="product_brand",alias="SKU品牌",description="SKU品牌",lengthMax="255",optional="",defaultValue="")
	 * @Param(name="product_model",alias="品牌型号",description="品牌型号",lengthMax="255",optional="",defaultValue="")
	 * @Param(name="sku_state_type",alias="是否国内转海外 0表示否 6表示是",description="是否国内转海外 0表示否 6表示是",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_customized",alias="是否定制 1表示是 2表示否",description="是否定制 1表示是 2表示否",lengthMax="1",optional="",defaultValue="2")
	 * @Param(name="original_devliy",lengthMax="Array",optional="")
	 * @Param(name="devliy",lengthMax="Array",optional="")
	 * @Param(name="net_weight",alias="净重",description="净重",lengthMax="Array",optional="",defaultValue="0.00")
	 * @Param(name="rought_weight",alias="毛重",description="毛重",lengthMax="Array",optional="",defaultValue="0.00")
	 * @Param(name="development",alias="开发类型（和产品系统保持一致） 1:常规产品 2:试卖产品 3:FBA精品 4:代销产品, 默认0",description="开发类型（和产品系统保持一致） 1:常规产品 2:试卖产品 3:FBA精品 4:代销产品, 默认0",lengthMax="4",optional="",defaultValue="0")
	 * @Param(name="is_logo",alias="是否有LOGO 0表示无 1表示有",description="是否有LOGO 0表示无 1表示有",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="logo_images",alias="有LOGO 图片",description="有LOGO 图片",lengthMax="300",optional="",defaultValue="")
	 * @Param(name="no_logo_images",alias="无LOGO 图片",description="无LOGO 图片",lengthMax="300",optional="")
	 * @Param(name="logo_images_thumb_url",alias="有LOGO 图片压缩图片地址",description="有LOGO 图片压缩图片地址",lengthMax="255",optional="",defaultValue="")
	 * @Param(name="no_logo_images_thumb_url",alias="有LOGO 图片压缩",description="有LOGO 图片压缩",lengthMax="255",optional="",defaultValue="")
	 * @Param(name="update_time",alias="最近修改时间",description="最近修改时间",optional="",defaultValue="CURRENT_TIMESTAMP")
	 * @Param(name="long_delivery",alias="是否为超长交期 1表示 否，2表示是",description="是否为超长交期 1表示 否，2表示是",lengthMax="1",optional="",defaultValue="1")
	 * @Param(name="is_consign",alias="是否支持分销：0否，1是",description="是否支持分销：0否，1是",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="is_shipping",alias="是否包邮 1表示是 2表示否",description="是否包邮 1表示是 2表示否",lengthMax="1",optional="",defaultValue="0")
	 * @Param(name="unsale_reason",alias="停售原因",description="停售原因",lengthMax="255",optional="",defaultValue="")
	 * @Param(name="sku_change_data",alias="SKU变更类型，数据来源产品系统推送",description="SKU变更类型，数据来源产品系统推送",lengthMax="255",optional="",defaultValue="")
	 */
	public function update()
	{
		$param = ContextManager::getInstance()->get('param');
		$model = new PurProductModel();
		$info = $model->get(['id' => $param['id']]);
		if (empty($info)) {
		    $this->writeJson(Status::CODE_BAD_REQUEST, [], '该数据不存在');
		    return false;
		}
		$updateData = [];

		$updateData['sku']=$param['sku'] ?? $info->sku;
		$updateData['product_name']=$param['product_name'] ?? $info->product_name;
		$updateData['product_category_id']=$param['product_category_id'] ?? $info->product_category_id;
		$updateData['product_line_id']=$param['product_line_id'] ?? $info->product_line_id;
		$updateData['product_status']=$param['product_status'] ?? $info->product_status;
		$updateData['product_img_url']=$param['product_img_url'] ?? $info->product_img_url;
		$updateData['product_thumb_url']=$param['product_thumb_url'] ?? $info->product_thumb_url;
		$updateData['uploadimgs']=$param['uploadimgs'] ?? $info->uploadimgs;
		$updateData['product_cn_link']=$param['product_cn_link'] ?? $info->product_cn_link;
		$updateData['product_en_link']=$param['product_en_link'] ?? $info->product_en_link;
		$updateData['create_id']=$param['create_id'] ?? $info->create_id;
		$updateData['create_user_name']=$param['create_user_name'] ?? $info->create_user_name;
		$updateData['create_time']=$param['create_time'] ?? $info->create_time;
		$updateData['product_cost']=$param['product_cost'] ?? $info->product_cost;
		$updateData['product_type']=$param['product_type'] ?? $info->product_type;
		$updateData['product_package_code']=$param['product_package_code'] ?? $info->product_package_code;
		$updateData['purchase_packaging']=$param['purchase_packaging'] ?? $info->purchase_packaging;
		$updateData['supply_status']=$param['supply_status'] ?? $info->supply_status;
		$updateData['supplier_code']=$param['supplier_code'] ?? $info->supplier_code;
		$updateData['supplier_name']=$param['supplier_name'] ?? $info->supplier_name;
		$updateData['purchase_price']=$param['purchase_price'] ?? $info->purchase_price;
		$updateData['sale_attribute']=$param['sale_attribute'] ?? $info->sale_attribute;
		$updateData['note']=$param['note'] ?? $info->note;
		$updateData['last_price']=$param['last_price'] ?? $info->last_price;
		$updateData['avg_freight']=$param['avg_freight'] ?? $info->avg_freight;
		$updateData['avg_purchase_cost']=$param['avg_purchase_cost'] ?? $info->avg_purchase_cost;
		$updateData['is_drawback']=$param['is_drawback'] ?? $info->is_drawback;
		$updateData['tax_rate']=$param['tax_rate'] ?? $info->tax_rate;
		$updateData['ticketed_point']=$param['ticketed_point'] ?? $info->ticketed_point;
		$updateData['declare_cname']=$param['declare_cname'] ?? $info->declare_cname;
		$updateData['declare_unit']=$param['declare_unit'] ?? $info->declare_unit;
		$updateData['export_model']=$param['export_model'] ?? $info->export_model;
		$updateData['export_cname']=$param['export_cname'] ?? $info->export_cname;
		$updateData['export_ename']=$param['export_ename'] ?? $info->export_ename;
		$updateData['customs_code']=$param['customs_code'] ?? $info->customs_code;
		$updateData['is_sample']=$param['is_sample'] ?? $info->is_sample;
		$updateData['is_multi']=$param['is_multi'] ?? $info->is_multi;
		$updateData['is_inspection']=$param['is_inspection'] ?? $info->is_inspection;
		$updateData['is_new']=$param['is_new'] ?? $info->is_new;
		$updateData['is_boutique']=$param['is_boutique'] ?? $info->is_boutique;
		$updateData['is_repackage']=$param['is_repackage'] ?? $info->is_repackage;
		$updateData['is_weightdot']=$param['is_weightdot'] ?? $info->is_weightdot;
		$updateData['is_relate_ali']=$param['is_relate_ali'] ?? $info->is_relate_ali;
		$updateData['is_equal_sup_id']=$param['is_equal_sup_id'] ?? $info->is_equal_sup_id;
		$updateData['is_equal_sup_name']=$param['is_equal_sup_name'] ?? $info->is_equal_sup_name;
		$updateData['relate_ali_name']=$param['relate_ali_name'] ?? $info->relate_ali_name;
		$updateData['is_abnormal']=$param['is_abnormal'] ?? $info->is_abnormal;
		$updateData['audit_status']=$param['audit_status'] ?? $info->audit_status;
		$updateData['days_sales_7']=$param['days_sales_7'] ?? $info->days_sales_7;
		$updateData['days_sales_15']=$param['days_sales_15'] ?? $info->days_sales_15;
		$updateData['days_sales_30']=$param['days_sales_30'] ?? $info->days_sales_30;
		$updateData['days_sales_60']=$param['days_sales_60'] ?? $info->days_sales_60;
		$updateData['days_sales_90']=$param['days_sales_90'] ?? $info->days_sales_90;
		$updateData['sku_sale7']=$param['sku_sale7'] ?? $info->sku_sale7;
		$updateData['state_type']=$param['state_type'] ?? $info->state_type;
		$updateData['is_pull_logis']=$param['is_pull_logis'] ?? $info->is_pull_logis;
		$updateData['original_start_qty']=$param['original_start_qty'] ?? $info->original_start_qty;
		$updateData['original_start_qty_unit']=$param['original_start_qty_unit'] ?? $info->original_start_qty_unit;
		$updateData['starting_qty']=$param['starting_qty'] ?? $info->starting_qty;
		$updateData['starting_qty_unit']=$param['starting_qty_unit'] ?? $info->starting_qty_unit;
		$updateData['ali_ratio_own']=$param['ali_ratio_own'] ?? $info->ali_ratio_own;
		$updateData['ali_ratio_out']=$param['ali_ratio_out'] ?? $info->ali_ratio_out;
		$updateData['is_invalid']=$param['is_invalid'] ?? $info->is_invalid;
		$updateData['is_from_multi']=$param['is_from_multi'] ?? $info->is_from_multi;
		$updateData['productismulti']=$param['productismulti'] ?? $info->productismulti;
		$updateData['producttype']=$param['producttype'] ?? $info->producttype;
		$updateData['days_sales_3']=$param['days_sales_3'] ?? $info->days_sales_3;
		$updateData['product_weight']=$param['product_weight'] ?? $info->product_weight;
		$updateData['coupon_rate']=$param['coupon_rate'] ?? $info->coupon_rate;
		$updateData['maintain_ticketed_point']=$param['maintain_ticketed_point'] ?? $info->maintain_ticketed_point;
		$updateData['audit_status_log']=$param['audit_status_log'] ?? $info->audit_status_log;
		$updateData['record_change_time']=$param['record_change_time'] ?? $info->record_change_time;
		$updateData['is_purchasing']=$param['is_purchasing'] ?? $info->is_purchasing;
		$updateData['new_flag']=$param['new_flag'] ?? $info->new_flag;
		$updateData['is_tongtu_purchase']=$param['is_tongtu_purchase'] ?? $info->is_tongtu_purchase;
		$updateData['is_new_purchase']=$param['is_new_purchase'] ?? $info->is_new_purchase;
		$updateData['is_overseas_first_order']=$param['is_overseas_first_order'] ?? $info->is_overseas_first_order;
		$updateData['is_old_purchase']=$param['is_old_purchase'] ?? $info->is_old_purchase;
		$updateData['is_gateway']=$param['is_gateway'] ?? $info->is_gateway;
		$updateData['inside_number']=$param['inside_number'] ?? $info->inside_number;
		$updateData['box_size']=$param['box_size'] ?? $info->box_size;
		$updateData['outer_box_volume']=$param['outer_box_volume'] ?? $info->outer_box_volume;
		$updateData['product_volume']=$param['product_volume'] ?? $info->product_volume;
		$updateData['sample_packaging_type']=$param['sample_packaging_type'] ?? $info->sample_packaging_type;
		$updateData['sample_package_size']=$param['sample_package_size'] ?? $info->sample_package_size;
		$updateData['sample_package_length']=$param['sample_package_length'] ?? $info->sample_package_length;
		$updateData['sample_package_width']=$param['sample_package_width'] ?? $info->sample_package_width;
		$updateData['sample_package_heigth']=$param['sample_package_heigth'] ?? $info->sample_package_heigth;
		$updateData['sample_package_weight']=$param['sample_package_weight'] ?? $info->sample_package_weight;
		$updateData['sku_message']=$param['sku_message'] ?? $info->sku_message;
		$updateData['product_brand']=$param['product_brand'] ?? $info->product_brand;
		$updateData['product_model']=$param['product_model'] ?? $info->product_model;
		$updateData['sku_state_type']=$param['sku_state_type'] ?? $info->sku_state_type;
		$updateData['is_customized']=$param['is_customized'] ?? $info->is_customized;
		$updateData['original_devliy']=$param['original_devliy'] ?? $info->original_devliy;
		$updateData['devliy']=$param['devliy'] ?? $info->devliy;
		$updateData['net_weight']=$param['net_weight'] ?? $info->net_weight;
		$updateData['rought_weight']=$param['rought_weight'] ?? $info->rought_weight;
		$updateData['development']=$param['development'] ?? $info->development;
		$updateData['is_logo']=$param['is_logo'] ?? $info->is_logo;
		$updateData['logo_images']=$param['logo_images'] ?? $info->logo_images;
		$updateData['no_logo_images']=$param['no_logo_images'] ?? $info->no_logo_images;
		$updateData['logo_images_thumb_url']=$param['logo_images_thumb_url'] ?? $info->logo_images_thumb_url;
		$updateData['no_logo_images_thumb_url']=$param['no_logo_images_thumb_url'] ?? $info->no_logo_images_thumb_url;
		$updateData['update_time']=$param['update_time'] ?? $info->update_time;
		$updateData['long_delivery']=$param['long_delivery'] ?? $info->long_delivery;
		$updateData['is_consign']=$param['is_consign'] ?? $info->is_consign;
		$updateData['is_shipping']=$param['is_shipping'] ?? $info->is_shipping;
		$updateData['unsale_reason']=$param['unsale_reason'] ?? $info->unsale_reason;
		$updateData['sku_change_data']=$param['sku_change_data'] ?? $info->sku_change_data;
		$info->update($updateData);
		$this->writeJson(Status::CODE_OK, $info, "更新数据成功");
	}


	/**
	 * @Api(name="getOne",path="/Api/PurProduct/getOne")
	 * @ApiDescription("获取一条数据")
	 * @Method(allow={GET,POST})
	 * @InjectParamsContext(key="param")
	 * @ApiSuccessParam(name="code",description="状态码")
	 * @ApiSuccessParam(name="result",description="api请求结果")
	 * @ApiSuccessParam(name="msg",description="api提示信息")
	 * @ApiSuccess({"code":200,"result":[],"msg":"获取成功"})
	 * @ApiFail({"code":400,"result":[],"msg":"获取失败"})
	 * @Param(name="id",alias="ID",description="ID",lengthMax="11",required="")
	 * @ApiSuccessParam(name="result.id",description="ID")
	 * @ApiSuccessParam(name="result.sku",description="产品SKU")
	 * @ApiSuccessParam(name="result.product_name",description="产品名称")
	 * @ApiSuccessParam(name="result.product_category_id",description="产品类目ID")
	 * @ApiSuccessParam(name="result.product_line_id",description="产品线ID")
	 * @ApiSuccessParam(name="result.product_status",description="产品状态")
	 * @ApiSuccessParam(name="result.product_img_url",description="图片路径")
	 * @ApiSuccessParam(name="result.product_thumb_url",description="缩略图图片路径")
	 * @ApiSuccessParam(name="result.uploadimgs",description="图片信息")
	 * @ApiSuccessParam(name="result.product_cn_link",description="采购中文地址")
	 * @ApiSuccessParam(name="result.product_en_link",description="采购英文地址")
	 * @ApiSuccessParam(name="result.create_id",description="开发人员")
	 * @ApiSuccessParam(name="result.create_user_name",description="开发人姓名")
	 * @ApiSuccessParam(name="result.create_time",description="开发时间")
	 * @ApiSuccessParam(name="result.product_cost",description="开发成本")
	 * @ApiSuccessParam(name="result.product_type",description="是否捆绑（1.不是,2.是）")
	 * @ApiSuccessParam(name="result.product_package_code",description="包装[sku]")
	 * @ApiSuccessParam(name="result.purchase_packaging",description="采购包装")
	 * @ApiSuccessParam(name="result.supply_status",description="货源状态(1.正常,2.停产,3.断货,10:停产找货中)")
	 * @ApiSuccessParam(name="result.supplier_code",description="默认供应商CODE")
	 * @ApiSuccessParam(name="result.supplier_name",description="默认供应商名称")
	 * @ApiSuccessParam(name="result.purchase_price",description="采购单价（供应商报价）")
	 * @ApiSuccessParam(name="result.sale_attribute",description="销售属性")
	 * @ApiSuccessParam(name="result.note",description="开发备注")
	 * @ApiSuccessParam(name="result.last_price",description="产品最新采购价")
	 * @ApiSuccessParam(name="result.avg_freight",description="平均运费成本")
	 * @ApiSuccessParam(name="result.avg_purchase_cost",description="平均采购成本")
	 * @ApiSuccessParam(name="result.is_drawback",description="是否可退税(0.否,1.可退税)")
	 * @ApiSuccessParam(name="result.tax_rate",description="出口退税税率（*100,存整数）")
	 * @ApiSuccessParam(name="result.ticketed_point",description="税点（*100,存整数,开票点）")
	 * @ApiSuccessParam(name="result.declare_cname",description="申报中文名")
	 * @ApiSuccessParam(name="result.declare_unit",description="申报单位")
	 * @ApiSuccessParam(name="result.export_model",description="出口申报型号")
	 * @ApiSuccessParam(name="result.export_cname",description="出口申报中文名")
	 * @ApiSuccessParam(name="result.export_ename",description="出口申报英文名(未使用)")
	 * @ApiSuccessParam(name="result.customs_code",description="出口海关编码")
	 * @ApiSuccessParam(name="result.is_sample",description="是否是样品（0.否,1.是）")
	 * @ApiSuccessParam(name="result.is_multi",description="是否多属性(0.单品,1.多属性单品,2.多属性组合产品)")
	 * @ApiSuccessParam(name="result.is_inspection",description="是否商检（1.不商检,2.商检）")
	 * @ApiSuccessParam(name="result.is_new",description="是否新品（0.否,1.是）")
	 * @ApiSuccessParam(name="result.is_boutique",description="是否精品（0.否,1.是）")
	 * @ApiSuccessParam(name="result.is_repackage",description="是否二次包装（0.否,1.是）")
	 * @ApiSuccessParam(name="result.is_weightdot",description="是否重点SKU（0.否,1.是）")
	 * @ApiSuccessParam(name="result.is_relate_ali",description="是否关联1688")
	 * @ApiSuccessParam(name="result.is_equal_sup_id",description="1688供应商ID是否一致（0.未验证,1.一致,2.不一致）")
	 * @ApiSuccessParam(name="result.is_equal_sup_name",description="1688供应商名称是否一致（0.未验证,1.一致,2.不一致）")
	 * @ApiSuccessParam(name="result.relate_ali_name",description="关联操作人")
	 * @ApiSuccessParam(name="result.is_abnormal",description="是否异常 1[否] 2[是]")
	 * @ApiSuccessParam(name="result.audit_status",description="审核状态 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]")
	 * @ApiSuccessParam(name="result.days_sales_7",description="7天销量")
	 * @ApiSuccessParam(name="result.days_sales_15",description="15天销量")
	 * @ApiSuccessParam(name="result.days_sales_30",description="30天销量")
	 * @ApiSuccessParam(name="result.days_sales_60",description="60天销量")
	 * @ApiSuccessParam(name="result.days_sales_90",description="90天销量")
	 * @ApiSuccessParam(name="result.sku_sale7",description="")
	 * @ApiSuccessParam(name="result.state_type",description="开发类型 1常规产品  2试卖产品  3亚马逊产品  4通途产品  5亚马逊服装  6国内转海外仓  9代销产品")
	 * @ApiSuccessParam(name="result.is_pull_logis",description="是否从物流获取商检信息")
	 * @ApiSuccessParam(name="result.original_start_qty",description="原始最小起订量")
	 * @ApiSuccessParam(name="result.original_start_qty_unit",description="原始最小起订量单位")
	 * @ApiSuccessParam(name="result.starting_qty",description="最小起订量")
	 * @ApiSuccessParam(name="result.starting_qty_unit",description="最小起订量单位")
	 * @ApiSuccessParam(name="result.ali_ratio_own",description="单位对应关系（内部）")
	 * @ApiSuccessParam(name="result.ali_ratio_out",description="单位对应关系（外部）")
	 * @ApiSuccessParam(name="result.is_invalid",description="标识SKU链接是否失效 0 标识有效果，1标识失效")
	 * @ApiSuccessParam(name="result.is_from_multi",description="是否从ERP 获取商品信息 （JAVA）")
	 * @ApiSuccessParam(name="result.productismulti",description="0: 普通 1: 多属性单品 2: 多属性组合")
	 * @ApiSuccessParam(name="result.producttype",description="1普通，2捆绑销售")
	 * @ApiSuccessParam(name="result.days_sales_3",description="3天销量")
	 * @ApiSuccessParam(name="result.product_weight",description="样品包装重量")
	 * @ApiSuccessParam(name="result.coupon_rate",description="票面税率")
	 * @ApiSuccessParam(name="result.maintain_ticketed_point",description="开票点是否维护过，0表示没有维护，1表示维护")
	 * @ApiSuccessParam(name="result.audit_status_log",description="SKU 审核状态,同步product_update_log 表 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]5[待财务审核]")
	 * @ApiSuccessParam(name="result.record_change_time",description="记录修改时间(供应商门户用)")
	 * @ApiSuccessParam(name="result.is_purchasing",description="是否需要代采 1表示否2表示是")
	 * @ApiSuccessParam(name="result.new_flag",description="1表示查询过 0表示未查询")
	 * @ApiSuccessParam(name="result.is_tongtu_purchase",description="1：表示在老采购系统下过单，2表示没有")
	 * @ApiSuccessParam(name="result.is_new_purchase",description="1：表示在采购系统下过单，2表示没有")
	 * @ApiSuccessParam(name="result.is_overseas_first_order",description="是否为海外首单 0表示否，1表示是")
	 * @ApiSuccessParam(name="result.is_old_purchase",description="1：表示在老采购系统下过单，2表示没有")
	 * @ApiSuccessParam(name="result.is_gateway",description="是否需要推送到门户系统 1表示是 0表示否")
	 * @ApiSuccessParam(name="result.inside_number",description="箱内数")
	 * @ApiSuccessParam(name="result.box_size",description="外箱尺寸")
	 * @ApiSuccessParam(name="result.outer_box_volume",description="外箱体积")
	 * @ApiSuccessParam(name="result.product_volume",description="产品体积")
	 * @ApiSuccessParam(name="result.sample_packaging_type",description="来样包装类型")
	 * @ApiSuccessParam(name="result.sample_package_size",description="样品包装尺寸")
	 * @ApiSuccessParam(name="result.sample_package_length",description="包装产品长度")
	 * @ApiSuccessParam(name="result.sample_package_width",description="包装产品宽度")
	 * @ApiSuccessParam(name="result.sample_package_heigth",description="包装产品高度")
	 * @ApiSuccessParam(name="result.sample_package_weight",description="样品包装重量")
	 * @ApiSuccessParam(name="result.sku_message",description="SKU商品参数")
	 * @ApiSuccessParam(name="result.product_brand",description="SKU品牌")
	 * @ApiSuccessParam(name="result.product_model",description="品牌型号")
	 * @ApiSuccessParam(name="result.sku_state_type",description="是否国内转海外 0表示否 6表示是")
	 * @ApiSuccessParam(name="result.is_customized",description="是否定制 1表示是 2表示否")
	 * @ApiSuccessParam(name="result.original_devliy",description="")
	 * @ApiSuccessParam(name="result.devliy",description="")
	 * @ApiSuccessParam(name="result.net_weight",description="净重")
	 * @ApiSuccessParam(name="result.rought_weight",description="毛重")
	 * @ApiSuccessParam(name="result.development",description="开发类型（和产品系统保持一致） 1:常规产品 2:试卖产品 3:FBA精品 4:代销产品, 默认0")
	 * @ApiSuccessParam(name="result.is_logo",description="是否有LOGO 0表示无 1表示有")
	 * @ApiSuccessParam(name="result.logo_images",description="有LOGO 图片")
	 * @ApiSuccessParam(name="result.no_logo_images",description="无LOGO 图片")
	 * @ApiSuccessParam(name="result.logo_images_thumb_url",description="有LOGO 图片压缩图片地址")
	 * @ApiSuccessParam(name="result.no_logo_images_thumb_url",description="有LOGO 图片压缩")
	 * @ApiSuccessParam(name="result.update_time",description="最近修改时间")
	 * @ApiSuccessParam(name="result.long_delivery",description="是否为超长交期 1表示 否，2表示是")
	 * @ApiSuccessParam(name="result.is_consign",description="是否支持分销：0否，1是")
	 * @ApiSuccessParam(name="result.is_shipping",description="是否包邮 1表示是 2表示否")
	 * @ApiSuccessParam(name="result.unsale_reason",description="停售原因")
	 * @ApiSuccessParam(name="result.sku_change_data",description="SKU变更类型，数据来源产品系统推送")
	 */
	public function getOne()
	{
		$param = ContextManager::getInstance()->get('param');
		$model = new PurProductModel();
		$info = $model->get(['id' => $param['id']]);
		if ($info) {
		    $this->writeJson(Status::CODE_OK, $info, "获取数据成功.");
		} else {
		    $this->writeJson(Status::CODE_BAD_REQUEST, [], '数据不存在');
		}
	}


	/**
	 * @Api(name="getList",path="/Api/PurProduct/getList")
	 * @ApiDescription("获取数据列表")
	 * @Method(allow={GET,POST})
	 * @InjectParamsContext(key="param")
	 * @ApiSuccessParam(name="code",description="状态码")
	 * @ApiSuccessParam(name="result",description="api请求结果")
	 * @ApiSuccessParam(name="msg",description="api提示信息")
	 * @ApiSuccess({"code":200,"result":[],"msg":"获取成功"})
	 * @ApiFail({"code":400,"result":[],"msg":"获取失败"})
	 * @Param(name="page", from={GET,POST}, alias="页数", optional="")
	 * @Param(name="pageSize", from={GET,POST}, alias="每页总数", optional="")
	 * @ApiSuccessParam(name="result[].id",description="ID")
	 * @ApiSuccessParam(name="result[].sku",description="产品SKU")
	 * @ApiSuccessParam(name="result[].product_name",description="产品名称")
	 * @ApiSuccessParam(name="result[].product_category_id",description="产品类目ID")
	 * @ApiSuccessParam(name="result[].product_line_id",description="产品线ID")
	 * @ApiSuccessParam(name="result[].product_status",description="产品状态")
	 * @ApiSuccessParam(name="result[].product_img_url",description="图片路径")
	 * @ApiSuccessParam(name="result[].product_thumb_url",description="缩略图图片路径")
	 * @ApiSuccessParam(name="result[].uploadimgs",description="图片信息")
	 * @ApiSuccessParam(name="result[].product_cn_link",description="采购中文地址")
	 * @ApiSuccessParam(name="result[].product_en_link",description="采购英文地址")
	 * @ApiSuccessParam(name="result[].create_id",description="开发人员")
	 * @ApiSuccessParam(name="result[].create_user_name",description="开发人姓名")
	 * @ApiSuccessParam(name="result[].create_time",description="开发时间")
	 * @ApiSuccessParam(name="result[].product_cost",description="开发成本")
	 * @ApiSuccessParam(name="result[].product_type",description="是否捆绑（1.不是,2.是）")
	 * @ApiSuccessParam(name="result[].product_package_code",description="包装[sku]")
	 * @ApiSuccessParam(name="result[].purchase_packaging",description="采购包装")
	 * @ApiSuccessParam(name="result[].supply_status",description="货源状态(1.正常,2.停产,3.断货,10:停产找货中)")
	 * @ApiSuccessParam(name="result[].supplier_code",description="默认供应商CODE")
	 * @ApiSuccessParam(name="result[].supplier_name",description="默认供应商名称")
	 * @ApiSuccessParam(name="result[].purchase_price",description="采购单价（供应商报价）")
	 * @ApiSuccessParam(name="result[].sale_attribute",description="销售属性")
	 * @ApiSuccessParam(name="result[].note",description="开发备注")
	 * @ApiSuccessParam(name="result[].last_price",description="产品最新采购价")
	 * @ApiSuccessParam(name="result[].avg_freight",description="平均运费成本")
	 * @ApiSuccessParam(name="result[].avg_purchase_cost",description="平均采购成本")
	 * @ApiSuccessParam(name="result[].is_drawback",description="是否可退税(0.否,1.可退税)")
	 * @ApiSuccessParam(name="result[].tax_rate",description="出口退税税率（*100,存整数）")
	 * @ApiSuccessParam(name="result[].ticketed_point",description="税点（*100,存整数,开票点）")
	 * @ApiSuccessParam(name="result[].declare_cname",description="申报中文名")
	 * @ApiSuccessParam(name="result[].declare_unit",description="申报单位")
	 * @ApiSuccessParam(name="result[].export_model",description="出口申报型号")
	 * @ApiSuccessParam(name="result[].export_cname",description="出口申报中文名")
	 * @ApiSuccessParam(name="result[].export_ename",description="出口申报英文名(未使用)")
	 * @ApiSuccessParam(name="result[].customs_code",description="出口海关编码")
	 * @ApiSuccessParam(name="result[].is_sample",description="是否是样品（0.否,1.是）")
	 * @ApiSuccessParam(name="result[].is_multi",description="是否多属性(0.单品,1.多属性单品,2.多属性组合产品)")
	 * @ApiSuccessParam(name="result[].is_inspection",description="是否商检（1.不商检,2.商检）")
	 * @ApiSuccessParam(name="result[].is_new",description="是否新品（0.否,1.是）")
	 * @ApiSuccessParam(name="result[].is_boutique",description="是否精品（0.否,1.是）")
	 * @ApiSuccessParam(name="result[].is_repackage",description="是否二次包装（0.否,1.是）")
	 * @ApiSuccessParam(name="result[].is_weightdot",description="是否重点SKU（0.否,1.是）")
	 * @ApiSuccessParam(name="result[].is_relate_ali",description="是否关联1688")
	 * @ApiSuccessParam(name="result[].is_equal_sup_id",description="1688供应商ID是否一致（0.未验证,1.一致,2.不一致）")
	 * @ApiSuccessParam(name="result[].is_equal_sup_name",description="1688供应商名称是否一致（0.未验证,1.一致,2.不一致）")
	 * @ApiSuccessParam(name="result[].relate_ali_name",description="关联操作人")
	 * @ApiSuccessParam(name="result[].is_abnormal",description="是否异常 1[否] 2[是]")
	 * @ApiSuccessParam(name="result[].audit_status",description="审核状态 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]")
	 * @ApiSuccessParam(name="result[].days_sales_7",description="7天销量")
	 * @ApiSuccessParam(name="result[].days_sales_15",description="15天销量")
	 * @ApiSuccessParam(name="result[].days_sales_30",description="30天销量")
	 * @ApiSuccessParam(name="result[].days_sales_60",description="60天销量")
	 * @ApiSuccessParam(name="result[].days_sales_90",description="90天销量")
	 * @ApiSuccessParam(name="result[].sku_sale7",description="")
	 * @ApiSuccessParam(name="result[].state_type",description="开发类型 1常规产品  2试卖产品  3亚马逊产品  4通途产品  5亚马逊服装  6国内转海外仓  9代销产品")
	 * @ApiSuccessParam(name="result[].is_pull_logis",description="是否从物流获取商检信息")
	 * @ApiSuccessParam(name="result[].original_start_qty",description="原始最小起订量")
	 * @ApiSuccessParam(name="result[].original_start_qty_unit",description="原始最小起订量单位")
	 * @ApiSuccessParam(name="result[].starting_qty",description="最小起订量")
	 * @ApiSuccessParam(name="result[].starting_qty_unit",description="最小起订量单位")
	 * @ApiSuccessParam(name="result[].ali_ratio_own",description="单位对应关系（内部）")
	 * @ApiSuccessParam(name="result[].ali_ratio_out",description="单位对应关系（外部）")
	 * @ApiSuccessParam(name="result[].is_invalid",description="标识SKU链接是否失效 0 标识有效果，1标识失效")
	 * @ApiSuccessParam(name="result[].is_from_multi",description="是否从ERP 获取商品信息 （JAVA）")
	 * @ApiSuccessParam(name="result[].productismulti",description="0: 普通 1: 多属性单品 2: 多属性组合")
	 * @ApiSuccessParam(name="result[].producttype",description="1普通，2捆绑销售")
	 * @ApiSuccessParam(name="result[].days_sales_3",description="3天销量")
	 * @ApiSuccessParam(name="result[].product_weight",description="样品包装重量")
	 * @ApiSuccessParam(name="result[].coupon_rate",description="票面税率")
	 * @ApiSuccessParam(name="result[].maintain_ticketed_point",description="开票点是否维护过，0表示没有维护，1表示维护")
	 * @ApiSuccessParam(name="result[].audit_status_log",description="SKU 审核状态,同步product_update_log 表 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]5[待财务审核]")
	 * @ApiSuccessParam(name="result[].record_change_time",description="记录修改时间(供应商门户用)")
	 * @ApiSuccessParam(name="result[].is_purchasing",description="是否需要代采 1表示否2表示是")
	 * @ApiSuccessParam(name="result[].new_flag",description="1表示查询过 0表示未查询")
	 * @ApiSuccessParam(name="result[].is_tongtu_purchase",description="1：表示在老采购系统下过单，2表示没有")
	 * @ApiSuccessParam(name="result[].is_new_purchase",description="1：表示在采购系统下过单，2表示没有")
	 * @ApiSuccessParam(name="result[].is_overseas_first_order",description="是否为海外首单 0表示否，1表示是")
	 * @ApiSuccessParam(name="result[].is_old_purchase",description="1：表示在老采购系统下过单，2表示没有")
	 * @ApiSuccessParam(name="result[].is_gateway",description="是否需要推送到门户系统 1表示是 0表示否")
	 * @ApiSuccessParam(name="result[].inside_number",description="箱内数")
	 * @ApiSuccessParam(name="result[].box_size",description="外箱尺寸")
	 * @ApiSuccessParam(name="result[].outer_box_volume",description="外箱体积")
	 * @ApiSuccessParam(name="result[].product_volume",description="产品体积")
	 * @ApiSuccessParam(name="result[].sample_packaging_type",description="来样包装类型")
	 * @ApiSuccessParam(name="result[].sample_package_size",description="样品包装尺寸")
	 * @ApiSuccessParam(name="result[].sample_package_length",description="包装产品长度")
	 * @ApiSuccessParam(name="result[].sample_package_width",description="包装产品宽度")
	 * @ApiSuccessParam(name="result[].sample_package_heigth",description="包装产品高度")
	 * @ApiSuccessParam(name="result[].sample_package_weight",description="样品包装重量")
	 * @ApiSuccessParam(name="result[].sku_message",description="SKU商品参数")
	 * @ApiSuccessParam(name="result[].product_brand",description="SKU品牌")
	 * @ApiSuccessParam(name="result[].product_model",description="品牌型号")
	 * @ApiSuccessParam(name="result[].sku_state_type",description="是否国内转海外 0表示否 6表示是")
	 * @ApiSuccessParam(name="result[].is_customized",description="是否定制 1表示是 2表示否")
	 * @ApiSuccessParam(name="result[].original_devliy",description="")
	 * @ApiSuccessParam(name="result[].devliy",description="")
	 * @ApiSuccessParam(name="result[].net_weight",description="净重")
	 * @ApiSuccessParam(name="result[].rought_weight",description="毛重")
	 * @ApiSuccessParam(name="result[].development",description="开发类型（和产品系统保持一致） 1:常规产品 2:试卖产品 3:FBA精品 4:代销产品, 默认0")
	 * @ApiSuccessParam(name="result[].is_logo",description="是否有LOGO 0表示无 1表示有")
	 * @ApiSuccessParam(name="result[].logo_images",description="有LOGO 图片")
	 * @ApiSuccessParam(name="result[].no_logo_images",description="无LOGO 图片")
	 * @ApiSuccessParam(name="result[].logo_images_thumb_url",description="有LOGO 图片压缩图片地址")
	 * @ApiSuccessParam(name="result[].no_logo_images_thumb_url",description="有LOGO 图片压缩")
	 * @ApiSuccessParam(name="result[].update_time",description="最近修改时间")
	 * @ApiSuccessParam(name="result[].long_delivery",description="是否为超长交期 1表示 否，2表示是")
	 * @ApiSuccessParam(name="result[].is_consign",description="是否支持分销：0否，1是")
	 * @ApiSuccessParam(name="result[].is_shipping",description="是否包邮 1表示是 2表示否")
	 * @ApiSuccessParam(name="result[].unsale_reason",description="停售原因")
	 * @ApiSuccessParam(name="result[].sku_change_data",description="SKU变更类型，数据来源产品系统推送")
	 */
	public function getList()
	{
		$param = ContextManager::getInstance()->get('param');
		$page = (int)($param['page'] ?? 1);
		$pageSize = (int)($param['pageSize'] ?? 20);
		$model = new PurProductModel();
		$data = $model->getList($page, $pageSize);
		$this->writeJson(Status::CODE_OK, $data, '获取列表成功');
	}


	/**
	 * @Api(name="delete",path="/Api/PurProduct/delete")
	 * @ApiDescription("删除数据")
	 * @Method(allow={GET,POST})
	 * @InjectParamsContext(key="param")
	 * @ApiSuccessParam(name="code",description="状态码")
	 * @ApiSuccessParam(name="result",description="api请求结果")
	 * @ApiSuccessParam(name="msg",description="api提示信息")
	 * @ApiSuccess({"code":200,"result":[],"msg":"新增成功"})
	 * @ApiFail({"code":400,"result":[],"msg":"新增失败"})
	 * @Param(name="id",alias="ID",description="ID",lengthMax="11",required="")
	 */
	public function delete()
	{
		$param = ContextManager::getInstance()->get('param');
		$model = new PurProductModel();
		$info = $model->get(['id' => $param['id']]);
		if (!$info) {
		    $this->writeJson(Status::CODE_OK, $info, "数据不存在.");
		}

		$info->destroy();
		$this->writeJson(Status::CODE_OK, [], "删除成功.");
	}
}

