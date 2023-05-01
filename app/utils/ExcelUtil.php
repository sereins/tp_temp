<?php

namespace app\utils;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelUtil
{
    /** @var null 全局静态实例 */
    private static $_instance = null;

    /** @var string 文件名(导出使用) */
    private $filename = '';

    /** @var null|Spreadsheet  执行句柄 */
    private $sheetHandle = null;

    /** @var null|Worksheet 活动工作表 */
    private $workSheet = null;

    /** @var int 工作表索引 */
    private $sheetIndex = 0;

    /** @var int 行 */
    private $line = 1;

    /**
     * 当前字段绑定那一列的坐标;属性(导出配置)
     * [
     * 'name'=> ['col'=>'A','type'=>'str'],
     * 'sex'=> ['col'=>'B','type'=>'int']
     * ]
     * @var array
     */
    private $headMap = [];

    /**
     * 获取静态实例
     *
     * @return ExcelUtil
     */
    public static function GetInstance(): ExcelUtil
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 设置文件名
     *
     * @param $filename
     * @return void
     */
    public function filename($filename): ExcelUtil
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * 设置一个工作表
     *
     * @param $title
     * @param int $page
     * @return $this
     * @throws Exception
     */
    public function setPage($title, int $page = 0): ExcelUtil
    {
        $handle = new Spreadsheet();

        # page大于0创建一个新的工作表
        if ($page > 0) $handle->createSheet($page);

        // 获取活动的工作表
        $this->workSheet = $handle->setActiveSheetIndex($page);

        // 设置工作表
        $this->workSheet->setTitle($title);

        // 初始化到第一行
        $this->line = 1;

        $this->sheetHandle = $handle;

        return $this;
    }

    /**
     * 设置头
     *
     * @param $heads
     * @return ExcelUtil
     * @throws Exception
     */
    public function setHead($heads): ExcelUtil
    {
        // 默认列表
        $this->workSheet->getDefaultColumnDimension()->setWidth(12);
        // 默认行高
        $this->workSheet->getDefaultRowDimension()->setRowHeight(12);

        foreach ($heads as $index => $item) {
            // 那一列
            $col = $this->_intToChar($index);

            // 配置头
            $this->headMap[$item['field']]['col'] = $col;
            $this->headMap[$item['field']]['type'] = $item['type'];

            # 具体那个坐标
            $site = $col . $this->line;

            // 是否有备注
            if (!empty($item['note'])) {
                $this->workSheet->getComment($site)->getText()->createText($item['note']);
            }

            // 全局垂直居中
            $this->sheetHandle->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            // 全局水平居中
            $this->sheetHandle->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            // 全局自动换行
            $this->sheetHandle->getDefaultStyle()->getAlignment()->setWrapText(true);

            // 设置值
            $this->workSheet->setCellValue($site, $item['title']);
        };

        return $this;
    }

    /**
     * 设置数据
     *
     * @param $data
     * @return $this
     */
    public function setData($data): ExcelUtil
    {
        # 表头占了一行所有 + 1；
        $line = $this->line + 1;
        foreach ($data as $item) {
            foreach ($item as $key => $value) {
                $fieldsInfo = $this->headMap[$key];
                if (is_null($fieldsInfo)) continue;
                $site = $fieldsInfo['col'] . $line;
                $this->workSheet->setCellValue($site, $value);
            }
            $line++;
        }
        return $this;
    }

    /**
     * 执行下载
     *
     * @param string $type
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function down(string $type = 'Xlsx')
    {
        // 获取具体的io类型
        $writer = IOFactory::createWriter($this->sheetHandle, ucfirst($type));

        // 设置浏览器表头
        $this->browserHeader($type);

        $writer->save('php://output');
    }

    /**
     * 加载文件，并创建一个执行的句柄
     *
     * @param $filename
     * @return $this
     */
    public function loadFile($filename): ExcelUtil
    {
        $this->sheetHandle = IOFactory::load($filename);

        return $this;
    }

    /**
     * 获取数据
     *
     * @param $head
     * @param int $row 从第几行开始
     * @return array
     * @throws Exception
     */
    public function getData($head, int $row = 0)
    {
        // 获取活动的工作博
        $workSheet = $this->sheetHandle->setActiveSheetIndex($this->sheetIndex);

        // 数据
        $data = $workSheet->toArray();

        // 表头
        $colsMap = $this->_getHeadMap($data[$row], $head);

        // 总行数
        $rows = $workSheet->getHighestRow();

        $result = [];
        for ($i = $row + 1; $i < $rows; $i++) {
            $currentRow = $data[$i]; # 每一行的数据
            foreach ($currentRow as $index => $value) {
                $key = $colsMap[$index]['field']; # 当前key
                $result[$i][$key] = $value;
            }
        }
        return array_values($result);
    }

    /**
     * 每一列对应的字段以及类型
     *
     * @param $data
     * @param $head
     * @return array
     */
    private function _getHeadMap($data, $head)
    {
        $map = array_column($head, null, 'title');

        $res = [];
        foreach ($data as $index => $value) {
            $array['field'] = $map[$value]['field'];
            $array['type'] = $map[$value]['type'];
            $res[$index] = $array;
        }
        return $res;
    }


    /**
     * 这只工作表
     *
     * @param $index
     * @return void
     */
    public function setSheetIndex($index)
    {
        $this->sheetIndex = $index;
    }


    /**
     * 响应头设置
     *
     * @throws \Exception
     */
    public function browserHeader($fileType)
    {
        //文件名称校验
        if (!$this->filename) {
            throw new \Exception('文件名不存在');
        }

        //Excel文件类型校验
        $type = ['Excel2007', 'Xlsx', 'Excel5', 'xls'];
        if (!in_array($fileType, $type)) {
            throw new \Exception('支持的文件类型');
        }

        $fileName = urlencode($this->filename);

        header('Cache-Control: max-age=0');
        header('Access-Control-Expose-Headers: Content-Disposition');
        if ($fileType == 'Excel2007' || $fileType == 'Xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename=' . $fileName . '.xlsx');
        } else { //Excel5
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename=' . $fileName . '.xls');
        }
    }

    /**
     * 数字转excel列坐标
     *
     * @param int $index
     * @return string
     */
    private function _intToChar(int $index = 0)
    {
        $str = '';
        $page = floor($index / 26);
        if ($page > 0) {
            $str .= $this->_intToChar(((int)$page - 1));
        }
        return $str . chr(($index % 26) + 65);
    }


    /**
     * 统一字段的格式
     *
     * @param $field
     * @param $title
     * @param string $type 类型
     * @param string $align
     * @param string $note 备注
     * @param int $colspan 列
     * @param int $rowspan 行
     * @return array
     */
    public function field(
        $field, $title, string $type = 'str', string $align = 'centre',
        string $note = '', int $colspan = 0, int $rowspan = 0): array
    {
        return [
            'field' => $field,
            "title" => $title,
            "type" => $type,
            'align' => $align,
            'note' => $note,
            "colspan" => $colspan,
            "rowspan" => $rowspan
        ];
    }
}
