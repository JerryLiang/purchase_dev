<?php

namespace App\Model\Api;

use EasySwoole\ORM\AbstractModel;

/**
 * PurProductModel
 * Class PurProductModel
 * Create With ClassGeneration
 * @property int $id // ID
 * @property string $sku // 产品SKU
 * @property string $product_name // 产品名称
 * @property int $product_category_id // 产品类目ID
 * @property int $product_line_id // 产品线ID
 * @property int $product_status // 产品状态
 * @property string $product_img_url // 图片路径
 * @property string $product_thumb_url // 缩略图图片路径
 * @property string $uploadimgs // 图片信息
 * @property string $product_cn_link // 采购中文地址
 * @property string $product_en_link // 采购英文地址
 * @property int $create_id // 开发人员
 * @property string $create_user_name // 开发人姓名
 * @property mixed $create_time // 开发时间
 * @property float $product_cost // 开发成本
 * @property int $product_type // 是否捆绑（1.不是,2.是）
 * @property string $product_package_code // 包装[sku]
 * @property string $purchase_packaging // 采购包装
 * @property int $supply_status // 货源状态(1.正常,2.停产,3.断货,10:停产找货中)
 * @property string $supplier_code // 默认供应商CODE
 * @property string $supplier_name // 默认供应商名称
 * @property float $purchase_price // 采购单价（供应商报价）
 * @property string $sale_attribute // 销售属性
 * @property string $note // 开发备注
 * @property float $last_price // 产品最新采购价
 * @property float $avg_freight // 平均运费成本
 * @property float $avg_purchase_cost // 平均采购成本
 * @property int $is_drawback // 是否可退税(0.否,1.可退税)
 * @property int $tax_rate // 出口退税税率（*100,存整数）
 * @property float $ticketed_point // 税点（*100,存整数,开票点）
 * @property string $declare_cname // 申报中文名
 * @property string $declare_unit // 申报单位
 * @property string $export_model // 出口申报型号
 * @property string $export_cname // 出口申报中文名
 * @property string $export_ename // 出口申报英文名(未使用)
 * @property string $customs_code // 出口海关编码
 * @property int $is_sample // 是否是样品（0.否,1.是）
 * @property int $is_multi // 是否多属性(0.单品,1.多属性单品,2.多属性组合产品)
 * @property int $is_inspection // 是否商检（1.不商检,2.商检）
 * @property int $is_new // 是否新品（0.否,1.是）
 * @property int $is_boutique // 是否精品（0.否,1.是）
 * @property int $is_repackage // 是否二次包装（0.否,1.是）
 * @property int $is_weightdot // 是否重点SKU（0.否,1.是）
 * @property int $is_relate_ali // 是否关联1688
 * @property int $is_equal_sup_id // 1688供应商ID是否一致（0.未验证,1.一致,2.不一致）
 * @property int $is_equal_sup_name // 1688供应商名称是否一致（0.未验证,1.一致,2.不一致）
 * @property string $relate_ali_name // 关联操作人
 * @property int $is_abnormal // 是否异常 1[否] 2[是]
 * @property int $audit_status // 审核状态 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]
 * @property int $days_sales_7 // 7天销量
 * @property int $days_sales_15 // 15天销量
 * @property int $days_sales_30 // 30天销量
 * @property int $days_sales_60 // 60天销量
 * @property int $days_sales_90 // 90天销量
 * @property string $sku_sale7 //
 * @property int $state_type // 开发类型 1常规产品  2试卖产品  3亚马逊产品  4通途产品  5亚马逊服装  6国内转海外仓  9代销产品
 * @property int $is_pull_logis // 是否从物流获取商检信息
 * @property int $original_start_qty // 原始最小起订量
 * @property string $original_start_qty_unit // 原始最小起订量单位
 * @property int $starting_qty // 最小起订量
 * @property string $starting_qty_unit // 最小起订量单位
 * @property int $ali_ratio_own // 单位对应关系（内部）
 * @property int $ali_ratio_out // 单位对应关系（外部）
 * @property int $is_invalid // 标识SKU链接是否失效 0 标识有效果，1标识失效
 * @property int $is_from_multi // 是否从ERP 获取商品信息 （JAVA）
 * @property int $productismulti // 0: 普通 1: 多属性单品 2: 多属性组合
 * @property int $producttype // 1普通，2捆绑销售
 * @property int $days_sales_3 // 3天销量
 * @property float $product_weight // 样品包装重量
 * @property float $coupon_rate // 票面税率
 * @property int $maintain_ticketed_point // 开票点是否维护过，0表示没有维护，1表示维护
 * @property int $audit_status_log // SKU 审核状态,同步product_update_log 表 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]5[待财务审核]
 * @property mixed $record_change_time // 记录修改时间(供应商门户用)
 * @property int $is_purchasing // 是否需要代采 1表示否2表示是
 * @property int $new_flag // 1表示查询过 0表示未查询
 * @property int $is_tongtu_purchase // 1：表示在老采购系统下过单，2表示没有
 * @property int $is_new_purchase // 1：表示在采购系统下过单，2表示没有
 * @property int $is_overseas_first_order // 是否为海外首单 0表示否，1表示是
 * @property int $is_old_purchase // 1：表示在老采购系统下过单，2表示没有
 * @property int $is_gateway // 是否需要推送到门户系统 1表示是 0表示否
 * @property int $inside_number // 箱内数
 * @property string $box_size // 外箱尺寸
 * @property float $outer_box_volume // 外箱体积
 * @property float $product_volume // 产品体积
 * @property string $sample_packaging_type // 来样包装类型
 * @property string $sample_package_size // 样品包装尺寸
 * @property float $sample_package_length // 包装产品长度
 * @property float $sample_package_width // 包装产品宽度
 * @property float $sample_package_heigth // 包装产品高度
 * @property float $sample_package_weight // 样品包装重量
 * @property string $sku_message // SKU商品参数
 * @property string $product_brand // SKU品牌
 * @property string $product_model // 品牌型号
 * @property int $sku_state_type // 是否国内转海外 0表示否 6表示是
 * @property int $is_customized // 是否定制 1表示是 2表示否
 * @property float $original_devliy //
 * @property float $devliy //
 * @property float $net_weight // 净重
 * @property float $rought_weight // 毛重
 * @property int $development // 开发类型（和产品系统保持一致） 1:常规产品 2:试卖产品 3:FBA精品 4:代销产品, 默认0
 * @property int $is_logo // 是否有LOGO 0表示无 1表示有
 * @property string $logo_images // 有LOGO 图片
 * @property string $no_logo_images // 无LOGO 图片
 * @property string $logo_images_thumb_url // 有LOGO 图片压缩图片地址
 * @property string $no_logo_images_thumb_url // 有LOGO 图片压缩
 * @property mixed $update_time // 最近修改时间
 * @property int $long_delivery // 是否为超长交期 1表示 否，2表示是
 * @property int $is_consign // 是否支持分销：0否，1是
 * @property int $is_shipping // 是否包邮 1表示是 2表示否
 * @property string $unsale_reason // 停售原因
 * @property string $sku_change_data // SKU变更类型，数据来源产品系统推送
 */
class PurProductModel extends AbstractModel
{
	protected $tableName = 'pur_product';


	public function getList(int $page = 1, int $pageSize = 10, string $field = '*'): array
	{
		$list = $this
		    ->withTotalCount()
			->order($this->schemaInfo()->getPkFiledName(), 'DESC')
		    ->field($field)
		    ->page($page, $pageSize)
		    ->all();
		$total = $this->lastQueryResult()->getTotalCount();
		$data = [
		    'page'=>$page,
		    'pageSize'=>$pageSize,
		    'list'=>$list,
		    'total'=>$total,
		    'pageCount'=>ceil($total / $pageSize)
		];
		return $data;
	}
}

