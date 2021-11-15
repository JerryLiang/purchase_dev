<?php
/**
 *
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/10
 * Time: 15:27
 *
 * 暂存列表搜索的条件或者直接结果集，用于其他场景中直接使用结果集的操作
 * 比如列表搜索-导出。
 * 按照session_id
 */
class SearchExportCacheService
{
    /**
     * 一个session对应一个list， list下面是各个场景，session失效之后直接delete这个key完成
     * 所有清理操作
     *
     * @var string
     */
    const COLLECTION_SEARCH = 'search_cache_';

    const PURCHASE_FINANCIAL_AUDIT_LIST_SEARCH_EXPORT = 101;//财务审核列表

    const PURCHASE_TAX_ORDER_TACKING_LIST_SEARCH_EXPORT = 102;//含税订单跟踪列表

    const PURCHASE_SUGGEST_AUDIT_LIST_SEARCH_EXPORT = 103;//备货单审核列表

    const PURCHASE_RETURN_APPLY_LIST_SEARCH_EXPORT = 104;//入库后退货-申请明细列表

    const PURCHASE_RETURN_CONFIRM_LIST_SEARCH_EXPORT = 105;//入库后退货-采购确认列表

    const PURCHASE_SHIPMENT_PLAN_CANCEL_SEARCH_EXPORT = 106;//发运管理-计划部取消列表

    private $_ci;

    private $_redis;

    private $_scene;

    private $_scene_key;

    private $_collection;

    private $_blank_char = '_b*a*l_';

    /**
     * 构造
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->library('rediss');
        $this->_redis = $this->_ci->rediss;
        $this->_ci->load->helper('user');

    }

    /**
     *
     * @return string[][]
     */
    public static final function scene_config()
    {
        return [
            SearchExportCacheService::PURCHASE_FINANCIAL_AUDIT_LIST_SEARCH_EXPORT => [
                's_key'  => 'purchase_financial_audit_list_',
            ],
            SearchExportCacheService::PURCHASE_TAX_ORDER_TACKING_LIST_SEARCH_EXPORT => [
                's_key'  => 'purchase_tax_order_tacking_list_',
            ],

            SearchExportCacheService::PURCHASE_SUGGEST_AUDIT_LIST_SEARCH_EXPORT => [
                's_key'  => 'purchase_suggest_audit_list_',
            ],

            SearchExportCacheService::PURCHASE_RETURN_APPLY_LIST_SEARCH_EXPORT => [
                's_key'  => 'purchase_return_apply_list_',
            ],

            SearchExportCacheService::PURCHASE_RETURN_CONFIRM_LIST_SEARCH_EXPORT => [
                's_key'  => 'purchase_return_confirm_list_',
            ],

            SearchExportCacheService::PURCHASE_SHIPMENT_PLAN_CANCEL_SEARCH_EXPORT => [
                's_key'  => 'purchase_shipment_plan_cancel_',
            ],
        ];
    }

    /**
     * 设置订单场景
     *
     * @param unknown $name
     * @throws \InvalidArgumentException
     */
    public function setScene($name)
    {
        $config = static::scene_config()[$name] ?? [];
        if (!$config)
        {
            throw new \InvalidArgumentException(sprintf('未定义的搜索导出：%s', $name), 3001);
        }
        $this->_scene = $name;
        $user_id = getActiveUserId();
        if ($user_id)
        {
            $this->_collection = self::COLLECTION_SEARCH.$user_id;
            $this->_scene_key = $config['s_key'].$user_id;
        }
        return $this;
    }

    /**
     * 设置订单场景
     *
     * @param unknown $name
     *
     * @throws \InvalidArgumentException
     */
    public function setESScene($name)
    {
        $config = static::scene_config()[$name] ?? [];
        if (!$config) {
            throw new \InvalidArgumentException(sprintf('未定义的搜索导出：%s', $name), 3001);
        }
        $this->_scene = $name;
        $user_id = getActiveUserId();

        if ($user_id) {
            $this->_collection = self::COLLECTION_SEARCH . $user_id;
            $this->_scene_key  = $config['es_key'] . $user_id;
        }

        return $this;
    }

    /**
     * 设置val
     */
    public function set($val)
    {
        if (!$this->_scene)
        {
            throw new \RuntimeException(sprintf('必须先设置搜索导出类型'), 500);
        }
        $user_id = getActiveUserId();

        if ($user_id)
        {
            $ttl = 600;//过期时间
            $val = str_replace(["\n", "\r\n"], ' ', addslashes($val));
            $val = str_replace(' ', $this->_blank_char, $val);
            $this->_redis->command(sprintf('hset %s %s %s', $this->_collection, $this->_scene_key, $val));
            $this->_redis->command('expire '.$this->_collection.' '.($ttl));
            return true;
        }

        return false;
    }

    /**
     *
     * @throws \RuntimeException
     * @return mixed
     */
    public function get()
    {
        if (!$this->_scene)
        {
            throw new \RuntimeException(sprintf('必须先设置搜索导出类型'), 500);
        }
//        pr(sprintf('hget %s %s', $this->_collection, $this->_scene_key));exit;
        $val =  stripslashes($this->_redis->command(sprintf('hget %s %s', $this->_collection, $this->_scene_key)));
        $val = str_replace($this->_blank_char, ' ', $val);
        return $val;
    }

}