<?php
/**
 * Models the data for a View to consume
 *
 * ## Usage
 *
 * ~~~ php
 *   $m = new Model();
 *   $m->name = "John Doe";
 * ~~~
 *
 * ## Changelog
 *
 * ### Version 2.0a
 * * Converted to PSR-4
 *
 * ### Version 1.1
 * * Add filters to the getter and setters
 * * filters change if Model is extended
 *
 * ### Version 1.0
 * * Initial release
 *
 * php version 7.0.0
 *
 * @category MVC
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */

namespace Sleepy\MVC;

use Sleepy\Core\Hook;

/**
 * The Model Class
 *
 * All models must extend the Model class
 *
 * @category MVC
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */
class Model implements \Iterator
{
    /**
     * The pointer for the Iterator
     *
     * @var integer
     */
    private $_position = 0;

    /**
     * The Models data
     *
     * @var array
     */
    private $_data = array();

    /**
     * Sets the $_position to the beginning
     *
     * @return void
     */
    public function rewind()
    {
        $this->_position = 0;
    }

    /**
     * Returns the current element
     *
     * @return void
     */
    public function current()
    {
        $keys = array_keys($this->_data);
        return $this->_data[$keys[$this->_position]];
    }

    /**
     * Gets the key for the current position
     *
     * @return string The key of the currently selected item
     */
    public function key()
    {
        $keys = array_keys($this->_data);
        return $keys[$this->_position];
    }

    /**
     * Iteratates the $_position
     *
     * @return void
     */
    public function next()
    {
        ++$this->_position;
    }

    /**
     * Is the current element valid?
     *
     * @return boolean
     */
    public function valid()
    {
        $keys = array_keys($this->_data);
        return isset($keys[$this->_position]);
    }

    /**
     * Return the name of the Class
     *
     * @return void
     */
    private function _cleanClass()
    {
        $className = get_class($this);

        if ($pos = strrpos($className, '\\')) {
            return substr($className, $pos + 1);
        }

        return $className;
    }

    /**
     * Contructor
     *
     * @param array $props an array of properties to streamline adding them
     */
    public function __construct($props = [])
    {
        if (class_exists('\Sleepy\Core\Hook')) {
            Hook::addAction($this->_cleanClass() . '_preprocess');
        }
        
        // Clean up the propertys
        foreach (array_keys(get_class_vars(get_class($this))) as $var) {
            if ($var === '_position') continue;
            if ($var === '_data') continue;
            $this->__set($var, $this->$var);
            unset($this->$var);
        }   

        foreach ($props as $property => $value) {
            if (class_exists('\Sleepy\Core\Hook')) {
                $this->_data[$property] = Hook::addFilter(
                    $this->_cleanClass() . '_set_' . $property,
                    $value
                );

                $this->_data[$property] = Hook::addFilter(
                    $this->_cleanClass() . '_set_property',
                    $value
                );
            } else {
                $this->_data[$property] = $value;
            }
        }
    }

    /**
     * When the Model is destructed
     */
    public function __destruct()
    {
        if (class_exists('\Sleepy\Core\Hook')) {
            Hook::addAction($this->_cleanClass() . '_postprocess');
        }
    }

    /**
     * Getter for all properties
     *
     * @param string $property The property to retrieve
     *
     * @return mixed The value stored in the $property
     */
    public function __get($property)
    {
        if (isset($this->_data[$property])) {
            if (class_exists('\Sleepy\Core\Hook')) {
                $output = Hook::addFilter(
                    $this->_cleanClass() . '_get_' . $property,
                    $this->_data[$property]
                );
                $output = Hook::addFilter(
                    $this->_cleanClass() . '_get_property',
                    $output
                );

                return $output;

            } else {
                return $this->_data[$property];
            }
        }
    }

    /**
     * Setter for all properties
     *
     * @param string $property The property name
     * @param mixed  $value    The value to store
     *
     * @return void
     */
    public function __set($property, $value)
    {
        if (class_exists('\Sleepy\Core\Hook')) {
            if (is_array($value)) {
                // Work around
                $value = array($value);
            }
            $this->_data[$property] = Hook::addFilter(
                $this->_cleanClass() . '_set_' . $property,
                $value
            );
            $this->_data[$property] = Hook::addFilter(
                $this->_cleanClass() . '_set_property',
                $value
            );
        } else {
            $this->_data[$property] = $value;
        }
    }

    /**
     * Output for var_dump should be from $this->data
     *
     * @return array
     */
    public function __debugInfo()
    {
        return $this->_data;
    }

    /**
     * When used as a string, output JSON of $this->data
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson($this->_data);
    }

    /**
     * When we invoke the object as a function
     *
     * @return void
     */
    public function __invoke()
    {
        return (object) $this->_data;
    }

    /**
     * Return the data as JSON
     *
     * @return void
     */
    public function toJson()
    {
        return json_encode($this->_data);
    }
}