<?php namespace Sintattica\Atk\DataTypes;

use Sintattica\Atk\Utils\StringParser;
use Sintattica\Atk\Utils\String;

/**
 * The 'string' datatype.
 * Useful for performing various small operations on strings fluently.
 *
 * @deprecated Scheduled for removal.
 * @author Boy Baukema <boy@achievo.org>
 * @package atk
 * @subpackage datatypes
 */
class StringDataType extends DataType
{
    /**
     * @var string The internal value of the current string object
     */
    protected $m_string = "";

    /*     * *************** BASICS **************** */

    /**
     * The 'string' datatype for easy manipulation of strings.
     *
     * @param string $string
     */
    public function __construct($string)
    {
        $this->m_string = $string;
    }

    /*     * *************** OPERATIONS **************** */

    /**
     * Replace search value(s) with replace value(s).
     *
     * @param string|array $search What to search on
     * @param string|array $replace What to replace
     * @return String The current string object
     */
    public function replace($search, $replace)
    {
        $this->m_string = str_replace($search, $replace, $this->m_string);
        return $this;
    }

    /**
     * Parse data into a string with the atkStringParser
     *
     * @param array $data The data to parse into the string
     * @return String The current (modified) string object
     */
    public function parse($data)
    {
        $sp = new StringParser($this->m_string);
        $this->m_string = $sp->parse($data);
        return $this;
    }

    /*     * *************** GETTERS **************** */

    /**
     * Get the current string.
     *
     * @return string The current string
     */
    public function getString()
    {
        return $this->m_string;
    }

    /**
     * To string. Returns the string representation for this object
     * which is ofcourse the internal string.
     *
     * @return string internal string
     */
    public function __toString()
    {
        return $this->m_string;
    }

}


