<?php

namespace nineinchnick\exporter;

/**
 * ExcelFormatter extends CFormatter to use formats recognized by Microsoft Excel and other compatible spreadsheet
 * software.
 *
 * In most cases original formatting is disabled.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class ExcelFormatter extends \yii\i18n\Formatter
{
    /**
     * @var string the format string to be used to format a date using PHP date() function. Defaults to 'Y/m/d'.
     */
    public $dateFormat = 'c';
    /**
     * @var string the format string to be used to format a time using PHP date() function. Defaults to 'h:i:s A'.
     */
    public $timeFormat = 'h:i:s';
    /**
     * @var string the format string to be used to format a date and time using PHP date() function. Defaults to 'Y/m/d
     *      h:i:s A'.
     */
    public $datetimeFormat = 'c';
    /**
     * @var array the format used to format a number with PHP number_format() function.
     * Three elements may be specified: "decimals", "decimalSeparator" and "thousandSeparator".
     * They correspond to the number of digits after the decimal point, the character displayed as the decimal point
     * and the thousands separator character.
     */
    public $numberFormat = ['decimals' => '2', 'decimalSeparator' => '.', 'thousandSeparator' => ','];
    /**
     * @var array the text to be displayed when formatting a boolean value. The first element corresponds
     * to the text display for false, the second element for true. Defaults to <code>array('No', 'Yes')</code>.
     */
    public $booleanFormat = ['0', '1'];

    public $typeMap = [
        'integer'  => ['type' => 'Integer', 'format' => '\#\ \#\#0'],
        'number'   => ['type' => 'Number', 'format' => '\#\ \#\#0,00'],
        'date'     => ['type' => 'DateTime', 'format' => 'yyyy\-mm\-dd'],
        'time'     => ['type' => 'DateTime', 'format' => 'hh:mm:ss'],
        'datetime' => ['type' => 'DateTime', 'format' => 'yyyy\-mm\-dd\ hh:mm:ss'],
        'boolean'  => ['type' => 'Boolean', 'format' => null],
    ];

    /**
     * Formats the value as a HTML-encoded plain text.
     * @param mixed $value the value to be formatted
     * @return string the formatted result
     */
    public function asText($value)
    {
        return $value;
    }

    /**
     * Formats the value as a HTML-encoded plain text and converts newlines with HTML &lt;br /&gt; or
     * &lt;p&gt;&lt;/p&gt; tags.
     * @param mixed $value                   the value to be formatted
     * @param boolean $paragraphs            whether newlines should be converted to HTML &lt;p&gt;&lt;/p&gt; tags,
     *                                       false by default meaning that HTML &lt;br /&gt; tags will be used
     * @param boolean $removeEmptyParagraphs whether empty paragraphs should be removed, defaults to true;
     *                                       makes sense only when $paragraphs parameter is true
     * @return string the formatted result
     */
    public function asNtext($value, $paragraphs = false, $removeEmptyParagraphs = true)
    {
        return $value;
    }

    /**
     * Formats the value as a mailto link.
     * @param mixed $value the value to be formatted
     * @return string the formatted result
     */
    public function asEmail($value)
    {
        return $value;
    }

    /**
     * Formats the value as an image tag.
     * @param mixed $value the value to be formatted
     * @return string the formatted result
     */
    public function asImage($value)
    {
        return $value;
    }

    /**
     * Formats the value as a hyperlink.
     * @param mixed $value the value to be formatted
     * @return string the formatted result
     */
    public function asUrl($value)
    {
        return $value;
    }
}
