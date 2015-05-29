<?php
/**
 * JsonView class file.
 *
 * @author    Jan Was <janek.jan@gmail.com>
 * @copyright Copyright &copy; 2013-2013 Jan Was
 */

namespace nineinchnick\exporter;

/**
 * JsonView allows output a large dataset in a JSON format by using CDbDataReader on a CDbCommand.
 * Because it inherits from the CGridView widget, same column configuration is allowed.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class JsonView extends ExporterView
{
    /**
     * @var string mimetype sent in http headers
     */
    public $mimetype = 'application/json';
    /**
     * @var boolean should html tags be stripped from output values, disable for really big exports to improve
     *      efficiency
     */
    public $stripTags = false;

    public function renderHeader()
    {
        echo "[\n " . json_encode($this->getHeader());
    }

    public function renderBody()
    {
        /** @var \yii\data\ActiveDataProvider $dataProvider */
        $dataProvider = $this->dataProvider;
        if (($dataProvider->pagination !== false && $dataProvider->pagination->limit < 1000)
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
                echo ",\n " . json_encode($this->renderRow($row++, $data));
            }
        } else {
            foreach ($dataProvider->getModels() as $data) {
                echo ",\n " . json_encode($this->renderRow($row++, $data));
            }
        }
    }

    public function renderFooter()
    {
        echo "\n]";
    }
}
