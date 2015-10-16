<?php namespace Sintattica\Atk\DataTypes;


/**
 * The 'array' datatype.
 * Useful for performing various small operations on arrays fluently.
 *
 * @deprecated Scheduled for removal.
 * @author Patrick van der Velden <patrick@achievo.org>
 * @package atk
 * @subpackage datatypes
 */
class ArrayDataType extends DataType
{
    /**
     * @var array The internal value of the current array object
     */
    protected $m_array = array();

    /*     * *************** BASICS **************** */

    /**
     * The 'array' datatype for easy manipulation of arrays.
     *
     * @param array $array
     */
    public function __construct($array)
    {
        $this->m_array = $array;
    }

    /*     * *************** OPERATIONS **************** */

    /**
     * (Multidimensional) atkArray replace function
     * If $replace is null, remove instead of replace it
     *
     * @param mixed $search
     * @param mixed $replace
     * @param bool $recursive
     */
    function replace($search, $replace, $recursive = true)
    {
        $this->_replace($this->m_array, $search, $replace, $recursive);
    }

    /**
     * Private (Multidimensional) array replace function
     * If $replace is null, remove instead of replace it
     *
     * @param array $array
     * @param mixed $search
     * @param mixed $replace
     * @param bool $recursive
     */
    private function _replace(&$array, $search, $replace, $recursive = true)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value) && $recursive) {
                $this->_replace($value, $search, $replace);
            } elseif ($value == $search) {
                if ($replace != null) {
                    $array[$key] = $replace;
                } else {
                    unset($array[$key]);
                }
            }
        }
    }

    /*     * *************** GETTERS **************** */

    /**
     * Get the current array.
     *
     * @return array The current array
     */
    public function getArray()
    {
        return $this->m_array;
    }

}


