<?php

/**
 * ArrBDDSpec class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.doophp.com/arr-bdd
 * @copyright Copyright &copy; 2011 Leng Sheng Hong
 * @license http://www.doophp.com/license
 * @since 0.13
 */

/**
 * Write specs in a class instead of procedure style. To be used with PHP application frameworks.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @since 0.13
 */
class ArrBDDSpec{    
    /**
     * Section name for scenario(s) in this class. To be used when saving unflatten results.
     * @var string
     */
    public $section;
    
    /**
     * Specifications. Write specs in prepare()
     * @var array 
     */
    public $specs;    
    
    /**
     * Prepare the specs
     * <code>
     * public function prepare(){
     *  $this->specs["The 'Hello world' string"] = array(
     *      'subject' => function(){
     *          return 'Hello world';
     *      },
     *      "SHOULD be 11 characters long" => function($hello){
     *          return (strlen($hello)===11);
     *      },
     *      "AND start with 'Hello'" => function($hello){
     *          return (strpos($hello, 'Hello')===0);
     *      }
     *  );
     * }
     * </code>
     */
    public function prepare(){}
    
    /**
     * Returns the section name
     * @return string 
     */
    public function getSectionName(){
        return $this->section;
    }
    
}
