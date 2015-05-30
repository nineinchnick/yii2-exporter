<?php
/**
 * ExporterView class file.
 *
 * @author    Jan Was <janek.jan@gmail.com>
 * @copyright Copyright &copy; 2013-2013 Jan Was
 */

namespace nineinchnick\exporter;

use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\data\Sort;
use yii\db\ActiveQuery;
use yii\db\DataReader;
use yii\grid\Column;
use yii\grid\DataColumn;
use yii\grid\GridView;

/**
 * ExporterView is a base class for grid views rendering data using streaming, row by row by using yii\db\DataReader.
 * This allows to output a large data set. Because it inherits from the GridView widget,
 * same column configuration is allowed.
 *
 * Tips:
 * - to include a line number or id column, add it to the column definition
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
abstract class ExporterView extends GridView
{
    /**
     * @var boolean should invisible columns be included anyway, useful to export all possible data without creating
     *      extra column configuration
     */
    public $includeInvisible = true;
    /**
     * @var boolean if true, all buffers are flushed and disabled before any output
     */
    public $disableBuffering = true;
    /**
     * @var boolean if true, no http headers will be sent, useful to capture output for further processing
     */
    public $disableHttpHeaders = false;
    /**
     * @var string filename sent in http headers. Defaults to null (means it won't set 'Content-Disposition:
     *      attachment' HTTP header)
     */
    public $filename;
    /**
     * @var string fileExt file extension., if value is empty|null then class assumes $filename contains it, otherwise
     *      export date will be placed between filename and file extension
     */
    public $fileExt;
    /**
     * @var string mimetype sent in http headers
     */
    public $mimetype = 'text/plain';
    /**
     * @var string output encoding, if null defaults to UTF-8
     */
    public $encoding;
    /**
     * @var boolean should html tags be stripped from output values, disable for really big exports to improve
     *      efficiency
     */
    public $stripTags = true;
    /**
     * @var boolean should html entities be decoded from output values
     */
    public $decodeHtmlEntities = true;
    /**
     * @var \yii\db\ActiveRecord model used to fill with current row and pass to row renderer
     */
    protected $_model;

    /**
     * Renders the view.
     * This is the main entry of the whole view rendering.
     * Child classes should mainly override {@link renderContent} method.
     */
    public function run()
    {
        $this->renderContent();
    }

    /**
     * Renders the main content of the view.
     * The content is divided into sections, such as summary, items, pager.
     * Each section is rendered by a method named as "renderXyz", where "Xyz" is the section name.
     * The rendering results will replace the corresponding placeholders in {@link template}.
     */
    public function renderContent()
    {
        if ($this->disableBuffering) {
            while (ob_get_level()) {
                ob_end_clean();
            }
        }
        if (!$this->disableHttpHeaders) {
            if ($this->mimetype) {
                header('Content-Type: ' . $this->mimetype . '; charset='
                    . ($this->encoding === null ? 'utf-8' : $this->encoding));
            }

            if ($this->filename !== null && strlen($this->filename) > 0) {
                $filename = $this->filename;

                if ($this->fileExt !== null && strlen($this->fileExt) > 0) {
                    $filename .= date('_U_Ymd.') . $this->fileExt;
                }

                header('Content-Disposition: attachment; filename="' . $filename . '"');
            }

            header('Pragma: no-cache');
            header('Expires: 0');
        }

        $this->renderItems();
        if (!$this->disableHttpHeaders) {
            \Yii::$app->end();
        }
    }

    /**
     * @inheritdoc
     * This method is made public to allow calling without full init from a CLI controller.
     */
    public function initColumns()
    {
        if (empty($this->columns)) {
            $this->guessColumns();
        }
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $column = $this->createDataColumn($column);
            } else {
                $column = \Yii::createObject(array_merge([
                    'class' => $this->dataColumnClass ? : DataColumn::className(),
                    'grid' => $this,
                ], $column));
            }
            // note: includeInvisible option
            if (!$this->includeInvisible && !$column->visible) {
                unset($this->columns[$i]);
                continue;
            } else {
                $column->visible = true;
            }
            $this->columns[$i] = $column;
        }
    }

    /**
     * This function tries to guess the columns to show from the given data
     * if [[columns]] are not explicitly specified.
     */
    protected function guessColumns()
    {
        if (!$this->dataProvider instanceof ActiveDataProvider) {
            throw new InvalidConfigException('ExporterView supports detecting columns from ActiveDataProvider only. Set the `columns` property.');
        }
        /** @var \yii\db\ActiveRecord $model */
        $model = new $this->dataProvider->query->modelClass;
        $this->columns = $model->attributes();
    }

    /**
     * This is based on CActiveDataProvider::fetchData().
     * @todo afterFind may not be called properly in some cases
     * @todo check effects of enabled offset/limit (baseLimited in CJoinElement::find in CActiveFinder.php)
     * @return DataReader
     */
    public function getDataReader()
    {
        /** @var ActiveDataProvider $dataProvider */
        $dataProvider = $this->dataProvider;
        /** @var ActiveQuery $query */
        $query = $dataProvider->query;

        /** @var Pagination $pagination */
        if (($pagination = $dataProvider->getPagination()) !== false) {
            $pagination->totalCount = $dataProvider->getTotalCount();
            $query->limit = $pagination->getLimit();
            $query->offset = $pagination->getOffset();
        }

        /** @var Sort $sort */
        if (($sort = $dataProvider->getSort()) !== false) {
            $query->orderBy = $sort->getOrders();
        }

        return $query->createCommand()->query();
    }

    /**
     * @param integer $row the row number (zero-based).
     * @param array $data  result of CDbDataReader.read() or an item from the CDataProvider.data array
     * @return mixed a model or array
     */
    protected function prepareRow($row, $data)
    {
        /** @var ActiveDataProvider $dataProvider */
        $dataProvider = $this->dataProvider;
        /** @var ActiveQuery $query */
        $query = $dataProvider->query;
        $models = $query->populate([$data]);
        $dataProvider->setmodels($models);

        return reset($models);
    }

    /**
     * @param integer $row the row number (zero-based).
     * @param mixed $data  an item from the DataProvider.models array
     * @return array processed values ready for output
     */
    public function renderRow($row, $data)
    {
        $values = [];

        /** @var Column $column */
        foreach ($this->columns as $column) {
            $r = new \ReflectionMethod($column, 'renderDataCellContent');
            $r->setAccessible(true);
            $value = $r->invoke($column, $data, $data->getPrimaryKey(), $row);
            //$value = $column->renderDataCellContent($data, $data->primaryKey, $row);
            if ($this->stripTags) {
                $value = strip_tags($value);
            }
            if ($this->decodeHtmlEntities) {
                $value = html_entity_decode($value);
            }
            if ($this->encoding !== null) {
                $value = iconv('UTF-8', $this->encoding, $value);
            }
            $values[] = $value;
        }

        return $values;
    }

    /**
     * Renders the data items for the grid view.
     */
    public function renderItems()
    {
        $this->renderHeader();
        $this->renderBody();
        $this->renderFooter();
    }

    protected function getHeader()
    {
        $headers = [];
        foreach ($this->columns as $column) {
            $r = new \ReflectionMethod($column, 'renderHeaderCellContent');
            $r->setAccessible(true);
            $header = $r->invoke($column);
            if ($this->encoding !== null) {
                $header = iconv('UTF-8', $this->encoding, $header);
            }
            $headers[] = $header;
        }

        return $headers;
    }

    public function renderHeader()
    {
    }

    public function renderBody()
    {
    }

    public function renderFooter()
    {
    }
}
