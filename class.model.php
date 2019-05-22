<?php
namespace Sleepy;

require_once('class.hooks.php');

use \Sleepy\Hook;

/**
 * Models the data for a View to consume
 */
class Model {
  /**
   * Getter for all properties
   * 
   * @TODO add filter
   */
  public function __get($property) {
    if (property_exists($this, $property)) {     
      //return Hook::addFilter("model_get_" + $property, $this->$property);
      return $this->property;
    }
  }

  /**
   * Setter for all properties
   * 
   * @TODO add filter
   */
  public function __set($property, $value) {
    //$this->$property = Hook::addFilter("model_set_" . $property, $value);
    $this->$property = $value;
  }
}