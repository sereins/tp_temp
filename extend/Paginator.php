<?php

// 分页操作
class Paginator
{

    public $page = 1;

    public $limit = 20;


    /**
     * 初始化
     *
     * @param $params
     */
    public function __construct($params)
    {
        if (isset($params['page'])) $this->page = $params['page'];
        if (isset($params['limit'])) $this->limit = $params['limit'];
    }

    /**
     * 执行分页(外部可以单独调用)
     *
     * @param $query
     * @return array
     */
    public function pages($query): array
    {
        $pageSize = $query->count();
        $list = $query->page($this->page)->limit($this->limit)->select()->toArray();

        return [
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $pageSize,
            'total_page' => ceil($pageSize / $this->limit),
            'data' => $list
        ];
    }
}