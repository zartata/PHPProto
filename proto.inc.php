<?php
/**
 * php-proto : Prototype programming interface.
 *
 * @author          Francis Lacroix <f@quotient.ca>
 * @package         php-proto
 * @version         1.0
 * @copyright       All rights reserved (c) Override Logic Technologies 2011-
 * @license         http://www.gnu.org/licenses/lgpl-3.0.txt
 */

require_once __DIR__.'/proto.class.php';

/**
 * Procedural interface for PHPProto::__construct() and PHPProto::__proto__().
 * 
 * @see PHPProto::__construct()
 * @see PHPProto::__proto__();
 * 
 * @param mixed $main If a Closure if passed, a new prototype object will be
 * registered with the closure as the main function. If a PHPProto object is
 * passed, it will be set as the prototype to this() (effectively calling
 * this()->__proto__($main). Any other type yields a warning and return null.
 * @param string $type The type of prototype to set, only applies when $main
 * is a Closure.
 * @return mixed Prototype interface instance, if $main was a Closure. NULL
 * otherwise.
 */
function proto($main = null, $type = null) {
    if ($main instanceof Closure) {
        $obj = new PHPProto($main, null);
        if (isset($type)) $obj->__type__($type);
        return $obj;
    } else if ($main instanceof PHPProto) {
        if (this() === null) {
            trigger_error('Trying to inherit from the global scope', E_USER_WARNING);    
        } else {
            this()->__proto__($main);
        }
    } else {
        trigger_error('Invalid argument type passed to proto()', E_USER_NOTICE);
    }
}

/**
 * Procedural interface for PHPProto::this().
 * 
 * @see PHPProto::this()
 * 
 * @return PHPProto Current prototype object.
 */
function this() {
    return PHPProto::this();
}

/**
 * Procedural interface for PHPProto::parent().
 * 
 * @see PHPProto::parent()
 * 
 * @return PHPProto Current prototype object's parent.
 */
function parent() {
    return this()->parent();
}

/**
 * Procedural interface for PHPProto::__oftype__().
 * 
 * @see PHPProto::__oftype__()
 * 
 * @param string $type Type to check.
 * @param mixed $obj Object to check.
 * @return bool TRUE if of a certain type.
 */
function oftype($type, $obj) {
    if (!$obj instanceof PHPProto) return false;
    return $obj->__oftype__($type);
}

/**
 * Procedural interface for PHPProto::__typeof__().
 * 
 * @see PHPProto::__typeof__()
 * 
 * @param PHPProto $obj Object to check.
 * @return array Array of types for the object.
 */
function typeof(PHPProto $obj) {
    return $obj->__typeof__();
}

/**
 * Check if a value is a prototype.
 * 
 * @param mixed $value Value to check.
 * @return TRUE if it's a prototype, FALSE otherwise.
 */
function is_proto($value) {
    return (is_object($value) && $value instanceof PHPProto);
}
