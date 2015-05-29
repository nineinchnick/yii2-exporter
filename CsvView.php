<?php
/**
 * CsvView class file.
 *
 * @author    Jan Was <janek.jan@gmail.com>
 * @copyright Copyright &copy; 2013-2013 Jan Was
 */

namespace nineinchnick\exporter;

/**
 * CsvView allows output a large dataset in a CSV format by using \yii\db\DataReader.
 * Because it inherits from the GridView widget, same column configuration is allowed.
 *
 * Tips:
 * - to include a line number or id column, add it to the column definition
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class CsvView extends ExporterView
{
    /**
     * @var string mimetype sent in http headers
     */
    public $mimetype = 'text/csv';
    /**
     * @var string if not null, newline characters will be replaced with this, useful when output file will be
     *      processed by simple CSV parsers; try not to use same characters as in the $delimiter property
     */
    public $replaceNewlines = ', ';
    /**
     * @var string field delimiter (one character only)
     */
    public $delimiter = ';';
    /**
     * @var string field enclosure (one character only)
     */
    public $enclosure = '"';

    /**
     * @var resource stdout or php://output, requried by fputcsv function
     */
    protected $_fp;

    /**
     * Renders the view.
     * This is the main entry of the whole view rendering.
     * Child classes should mainly override {@link renderContent} method.
     */
    public function run()
    {
        $this->_fp = fopen('php://output', 'w');
        if (!$this->_fp) {
            return;
        }
        $this->renderContent();
        fclose($this->_fp);
    }

    public function renderHeader()
    {
        fputcsv($this->_fp, $this->getHeader(), $this->delimiter, $this->enclosure);
    }

    public function renderBody()
    {
        /** @var \yii\data\ActiveDataProvider $dataProvider */
        $dataProvider = $this->dataProvider;
        if (($dataProvider->pagination !== false
                && $dataProvider->pagination->limit < 1000)
        ) {
            $dataReader = null;
        } else {
            //! @todo there could be a dataReader for CSqlDataProvider and some sort of iteratable container
            //for CArrayDataProvider to use next()
            $dataReader = $this->getDataReader();
        }

        $row = 0;
        if ($dataReader !== null) {
            while ($data = $dataReader->read()) {
                $data = $this->prepareRow($row, $data);
                fputcsv($this->_fp, $this->renderRow($row++, $data), $this->delimiter, $this->enclosure);
            }
        } else {
            foreach ($dataProvider->getModels() as $data) {
                fputcsv($this->_fp, $this->renderRow($row++, $data), $this->delimiter, $this->enclosure);
            }
        }
    }

    /**
     * @param integer $row the row number (zero-based).
     * @param array $data result of CDbDataReader.read()
     * @return array processed values ready for output
     */
    public function renderRow($row, $data)
    {
        $values = parent::renderRow($row, $data);
        foreach ($values as $key => $value) {
            if ($this->replaceNewlines !== null) {
                $values[$key] = str_replace("\n", $this->replaceNewlines, $value);
            }
        }

        return $values;
    }
}
