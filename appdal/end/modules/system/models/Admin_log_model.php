<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/15
 * Time: 14:17
 */
class Admin_log_model extends Purchase_model
{
    protected $table_name = "admin_log";

    /**
     * 记录操作日志
     * @author liwuxue
     * @date 2019/2/15 14:18
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function write_log($post)
    {
        $data = [
            "route" => isset($post['route']) && is_string($post['route']) ? rtrim(trim($post['route']), "/") : "",
            "description" => isset($post['description']) ? $post['description'] : "",
            "ip" => isset($post['ip']) && is_string($post['ip']) ? ip2long(trim($post['ip'])) : "",
            "user_name" => isset($post['user_name']) ? $post['user_name'] : "",
            "user_role" => isset($post['user_role']) ? $post['user_role'] : "",
            "created_at" => time(),
        ];
        if (empty($data['route'])) {
//            throw new Exception("empty route");
        }
        if (empty($data['user_name'])) {
//            throw new Exception("empty user_name");
        }
        return $this->purchase_db->insert($this->table_name, $data);
    }

    /**
     * 读取操作日志
     * @author liwuxue
     * @date 2019/2/15 14:18
     * @param $get
     * @return mixed
     * @throws Exception
     */
    public function get_list($get)
    {
        $qb = $this->purchase_db;
        if (isset($get['user_name']) && is_string($get['user_name']) && !empty(trim($get['user_name']))) {
            //用户名模糊查询
            $qb->like("user_name", trim($get['user_name']));
        }
        //分页
        $page = isset($get['page']) ? (int)$get['page'] : 1;
        $page < 1 && $page = 1;
        $page_size = query_limit_range(isset($get['page_size']) ? (int)$get['page_size']:0);
        $count_qb =clone $qb;
        $rows = $qb->select("id,user_name,user_role,description,created_at,route,ip")
            ->limit($page_size, ($page - 1) * $page_size)
            ->order_by("id desc")
            ->get($this->table_name)
            ->result_array();
        if (!empty($rows)) {
            foreach ($rows as &$row) {
                $row['ip'] = long2ip($row['ip']);
                $row['created_at'] = date('Y-m-d H:i:s',$row['created_at']);
            }
        }
        $data = [];
        $data['key'] = ["编号", "操作者", "操作者角色", "操作详情", "操作时间", "操作地址", "IP地址"];
        $data['values'] = $rows;
        //统计
        $total_row = $count_qb->select("count(id) as num")->get($this->table_name)->row_array();
        $total = isset($total_row['num']) ? (int)$total_row['num'] : 0;
        $data['paging_data'] = [
            "total" => $total,
            "offset" => $page,
            "limit" => $page_size,
            "pages" => ceil($total / $page_size),
        ];
        //下拉框
        $rows = $this->purchase_db->select("distinct(user_name) as user_name")->get($this->table_name)->result_array();
        $users=  is_array($rows) ? array_column($rows, "user_name", "user_name") : [];
        $data['drop_down_box']['user_name'] = $users;
        return $data;
    }

    /**
     * 日志概况
     * @author liwuxue
     * @date 2019/2/15 15:45
     * @param
     * @return mixed
     * @throws Exception
     */
    public function get_general_situation()
    {
        $data = [];
        //总访问量
        $row = $this->purchase_db->select("count(id) as num")->get($this->table_name)->row_array();
        $data["total"] = isset($row['num']) ? (int)$row['num'] : 0;
        //统计天数
        $row = $this->purchase_db->where("created_at >", 0)->order_by("id asc")->limit(1)->get($this->table_name)->row_array();
        $data['day'] = 0;
        if (!empty($row) && $row['created_at'] <= time()) {
            $first_time = strtotime(date("Y-m-d 00:00:00", $row['created_at']));
            $today_end = strtotime(date("Y-m-d 00:00:00", strtotime("+1 day")));
            $data['day'] = round(($today_end- $first_time) / (3600 * 24), 2);
        }
        //今年访问量
        $data["this_year"] = $this->count_by_start_time(strtotime(date("Y-01-01 00:00:00")));
        //本月访问量
        $data["this_month"] = $this->count_by_start_time(strtotime(date("Y-m-01 00:00:00")));
        //今日访问量
        $data["today"] = $this->count_by_start_time(strtotime(date("Y-m-d 00:00:00")));
        //每日平均值
        $data['day_avg'] = $data['day'] > 0 ? round($data['total'] / $data['day'], 2) : 0;
        return $data;
    }

    /**
     * 从某一个时间戳开始到现在的数据量
     * @author liwuxue
     * @date 2019/2/15 16:07
     * @param $time
     * @return int
     */
    public function count_by_start_time($time)
    {
        $row = $this->purchase_db->select("count(id) as num")->where("created_at >=", $time)->get($this->table_name)->row_array();
        return isset($row['num']) ? (int)$row['num'] : 0;
    }


}