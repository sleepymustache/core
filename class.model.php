<?php
namespace Sleepy;

require_once('class.hooks.php');

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
 * ### Version 1.1
 * * Add filters to the getter and setters
 * * filters change if Model is extended
 *
 * ### Version 1.0
 * * Initial release
 *
 * @date May 17, 2019
 * @author Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @version 1.1
 * @license  http://opensource.org/licenses/MIT
 */
class Model implements \Iterator {
    private $position = 0;
    private $data = array();

    public function rewind() {
        $this->position = 0;
    }

    public function current() {
        $keys = array_keys($this->data);
        return $this->data[$keys[$this->position]];
    }

    public function key() {
        $keys = array_keys($this->data);
        return $keys[$this->position];
    }

    public function next() {
        ++$this->position;
    }

    public function valid() {
        $keys = array_keys($this->data);
        return isset($keys[$this->position]);
    }

    /**
     * Return the name of the Class
     *
     * @return void
     */
    private function clean_class() {
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
    public function __construct($props = []) {
        $this->position = 0;

        if (class_exists('\Sleepy\Hook')) {
            Hook::addAction($this->clean_class() . '_preprocess');
        }

        foreach($props as $property => $value) {
             if (class_exists('\Sleepy\Hook')) {
                $this->data[$property] = Hook::addFilter($this->clean_class() . '_set_' . $property, $value);
                $this->data[$property] = Hook::addFilter($this->clean_class() . '_set_property', $value);
            } else {
                $this->data[$property] = $value;
            }
        }
    }

    /**
     * When the Model is destructed
     */
    public function __destruct() {
        if (class_exists('\Sleepy\Hook')) {
            Hook::addAction($this->clean_class() . '_postprocess');
        }
    }

    /**
     * Getter for all properties
     */
    public function __get($property) {
        if (isset($this->data[$property])) {
            if (class_exists('\Sleepy\Hook')) {
                $output = Hook::addFilter($this->clean_class() . '_get_' . $property, $this->data[$property]);
                $output = Hook::addFilter($this->clean_class() . '_get_property', $output);
                return $output;

            } else {
                return $this->data[$property];
            }
        }
    }

    /**
     * Setter for all properties
     */
    public function __set($property, $value) {
        if (class_exists('\Sleepy\Hook')) {
            $this->data[$property] = Hook::addFilter($this->clean_class() . '_set_' . $property, $value);
            $this->data[$property] = Hook::addFilter($this->clean_class() . '_set_property', $value);
        } else {
            $this->data[$property] = $value;
        }
    }

    /**
     * Output for var_dump should be from $this->data
     *
     * @return array
     */
    public function __debugInfo() {
        return $this->data;
    }

    /**
     * When used as a string, output JSON of $this->data
     *
     * @return string
     */
    public function __toString() {
        return $this->toJson($this->data);
    }

    /**
     * When we invoke the object as a function
     *
     * @return void
     */
    public function __invoke() {
        return (object) $this->data;
    }

    /**
     * Return the data as JSON
     *
     * @return void
     */
    public function toJson() {
        return json_encode($this->data);
    }
}