<?php
/**
 * XlsView class file.
 *
 * @author Jan Was <janek.jan@gmail.com>
 * @copyright Copyright &copy; 2013-2013 Jan Was
 */

namespace nineinchnick\exporter;

class XlsView extends ExporterView
{
    /**
     * @var string worksheet name, self-explanatory
     */
    public $worksheetName = null;
	/**
	 * @var string mimetype sent in http headers
	 */
	public $mimetype = 'application/excel';

    protected $_typeMap;

	public function init()
	{
		parent::init();
        
        if ($this->worksheetName === null) {
            $this->worksheetName = basename($this->filename, '.xls');
        }
        if (!(($formatter=$this->getFormatter()) instanceof ExcelFormatter)) {
            $formatter = new ExcelFormatter;
            $this->setFormatter($formatter);
        }
        $this->_typeMap = $formatter->typeMap;
	}

	/**
	 * @param integer $row the row number (zero-based).
	 * @param array $data result of CDbDataReader.read()
     * @param boolean $isActiveDataProvider true if the dataProvider property is an instance of CActiveDataProvider
	 * @return array processed values ready for output
	 */
	public function renderRow($row, $data, $isActiveDataProvider)
	{
		$values = array();

		foreach($this->columns as $column) {
            
            if (isset($column->type) && !is_array($column->type) && isset($this->_typeMap[$column->type]) && ($this->_typeMap[$column->type]['format'] !== null)) {
                $type = $this->_typeMap[$column->type]['type'];
                $style = $column->type.'Format';
                /*if ($column instanceof CDataColumn) {
                    if ($column->value !== null) {
                        $value = $column->evaluateExpression($column->value, array('data'=>$this->_model, 'row'=>$row));
                    } elseif ($column->name !== null) {
                        $value = CHtml::value($this->_model, $column->name);
                    }
                    $value = $this->formatExcel($value, $column->type);
                } else {
                    $value = $column->getDataCellContent($row, $this->_model);
                }*/
            } else {
                $type = 'String';
                $style = null;
            }
            $value = $column->getDataCellContent($row);
            
			if ($this->stripTags)
				$value = strip_tags($value);
			if ($this->encoding !== null)
				$value = iconv('UTF-8', $this->encoding, $value);

            //trim all whitespaces including non breaking - \xc2\xa0
            $v = trim($this->encodeText($value), " \t\n\r\0\x0b\xc2\xa0");
            if ($v === '') {
                $type = 'String';
                $style = null;
            }
			$values[] = '<Cell'.($style!==null ? ' ss:StyleID="'.$style.'"' : '').'><Data ss:Type="'.$type.'">' . $v . '</Data></Cell>';
		}
		return $values;
	}

    public function renderPreambleStyles()
    {
        $styles = <<<XML
        <Style ss:ID="Heading" ss:Name="Heading">
            <Font ss:Size="11" ss:Bold="1" ss:Color="#eeeeee"/>
            <Interior ss:Pattern="Solid" ss:Color="#222222"/>
        </Style>
        <Style ss:ID="Filters">
            <Font ss:Italic="1" ss:Color="#aa0000"/>
            <Interior ss:Pattern="Solid" ss:Color="#eeeeee"/>
        </Style>

XML;
        foreach($this->_typeMap as $type=>$typeData) {
            if ($typeData['format'] === null) continue;
            $styles .= <<<XML
        <Style ss:ID="{$type}Format">
            <NumberFormat ss:Format="{$typeData['format']}"/>
        </Style>

XML;
        }
        return $styles;
    }

    public function renderPreamble()
    {
        $name = $this->encodeText($this->worksheetName);
        $styles = $this->renderPreambleStyles();
        echo <<<XML
<?xml version="1.0"?>
<Workbook
    xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:c="urn:schemas-microsoft-com:office:component:spreadsheet">
    <Styles>
$styles
    </Styles>
    <Worksheet ss:Name="{$name}">
        <Table>

XML;
    }

    public function renderColumnHeaders()
    {
		echo '<Row ss:StyleID="Heading">';
		foreach($this->getHeader() as $header) {
            $header = preg_replace('/<br\s*\/?>/i', " ", $header);
            echo '<Cell><Data ss:Type="String">' . $this->encodeText($header) . '</Data></Cell>';
		}
        echo '</Row>';
    }

	public function renderHeader()
	{
        $this->renderPreamble();
        //$this->renderFilter();
        $this->renderColumnHeaders();
	}

	public function renderBody()
	{
        $isActiveDataProvider = $this->dataProvider instanceof CActiveDataProvider;
        if (!$isActiveDataProvider || ($this->dataProvider->pagination !== false && $this->dataProvider->pagination->limit < 1000)) {
            $dataReader = null;
            $finder = null;
        } else {
            //! @todo there could be a dataReader for CSqlDataProvider and some sort of iteratable container for CArrayDataProvider to use next()
            list($dataReader, $finder) = $this->getDataReader();
        }

		$row = 0;
        if ($dataReader !== null) {
            while ($data = $dataReader->read()) {
                $data = $this->prepareRow($row, $data, $finder, $isActiveDataProvider);
                echo '<Row>'.implode('', $this->renderRow($row++, $data, $isActiveDataProvider)).'</Row>'."\n";
            }
        } else {
            foreach ($this->dataProvider->data as $data) {
                echo '<Row>'.implode('', $this->renderRow($row++, $data, $isActiveDataProvider)).'</Row>'."\n";
            }
        }
        if ($finder!==null)
            $finder->destroyJoinTree();
	}

	public function renderFooter()
	{
        echo <<<XML
        </Table>
        <c:WorksheetOptions>
            <c:DisplayCustomHeaders/>
        </c:WorksheetOptions>
    </Worksheet>
</Workbook>
XML;
	}
    
    protected function encodeText($text) {
        // this may not have any sense, encode what we just decoded
        // but input may contain HTML and some of the entities are (not) valid in XML
        // in order to keep formatting as it was, this is the trick to do it
        //FIXME maybe a better way? there is a better way, probably...
        if(is_array($text)) {
            $text = implode(';', $text);
        }
        return htmlentities(html_entity_decode($text), ENT_XML1 | ENT_NOQUOTES);
    }
}
