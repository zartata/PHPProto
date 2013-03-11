<?php
/**
 * php-proto : Prototype programming interface.
 *
 * @author          Francis Lacroix <flacroix897 at gmail.com>
 * @package         PHPProto
 * @version         1.0
 * @license         http://www.gnu.org/licenses/lgpl-3.0.txt
 */

/**
 * Prototype class.
 * 
 * This class provides an interface for prototype-based programming. Refer to the README
 * file to know how to use it.
 * 
 * @todo Error checking
 * @todo Automatic scope resolution
 * 
 * @category        Core
 * @version         1.0
 */
class PHPProto {

    /**
     * Automatically execute a prototype function if a child is referenced
     * and the parent hasn't been executed yet.
     * @var bool
     */
    static public $autoload = true;
    
    /**
     * Current prototypes stack.
     * @var PHPProto[]
     */
    static protected $_current = array();
    
    /**
     * Variables stack.
     * @var array
     */
    protected $_vars = array();
    
    /**
     * Main function (called on __invoke()).
     * @var Closure
     */
    protected $_main;
    
    /**
     * Parent object.
     * @var PHPProto
     */
    protected $_parent;
    
    /**
     * Flag whether the function has been loaded.
     * @var bool
     */
    protected $_loaded = false;

    /**
     * Prototype inheritance
     * @var PHPProto
     */
    protected $_proto = array();
    
    /**
     * Type stack.
     * @var array
     */
    protected $_type = array();
    
    /**
     * Get the current object.
     * 
     * @return PHPProto Prototype object.
     */
    static public function this() {
        $obj = end(static::$_current);
        return $obj ?: null;
    }

    /**
     * Determine the prototype object owning the variable.
     * 
     * @param string $var Variable name.
     * @return PHPProto Prototype object owning the variable.
     */
    protected function _get_symbol_owner($var) {
        if (!$this->_loaded) $this();
        if (isset($this->_vars[$var])) return $this;
        
        foreach ($this->_proto as $proto) {
            if (isset($proto->$var)) return $proto;
        }
        
        return $this;
    }

    /**
     * Constructor.
     * 
     * @param mixed $main A closure to use as the main function, called on __invoke(),
     * @param PHPProto $parent The parent object.
     * @return void
     */
    public function __construct(Closure $main = null, PHPProto $parent = null) {
        $this->_parent = isset($parent) ? $parent : static::this();
        $this->_main = $main;
    }
    
    /**
     * Clone the object.
     * 
     * @return void
     */
    public function __clone() {
        foreach ($this->_vars as $var => $value) {
            if ($value instanceof static) {
                $value = clone $value;
                $ref = new ReflectionProperty(get_class($value), '_parent');
                $ref->setAccessible(true);
                $ref->setValue($value, $this);
                $ref->setAccessible(false);
                $this->_vars[$var] = $value;
            }
        }
    }
    
    /**
     * Set a function or variable.
     * 
     * @param string $var Symbol name.
     * @param Closure $value Prototype object or a value.
     * @return void
     */
    public function __set($var, $value) {
        $owner = $this->_get_symbol_owner($var);
        if ($owner !== $this) {
            $owner->$var = $value;
        }
        
        if ($value instanceof Closure) {
            $this->_vars[$var] = new static($value, $this);
        } else if ($value instanceof static) {
            $this->_vars[$var] = $value;     
        } else {
            $this->_vars[$var] = $value;
        }
    }
    
    /**
     * Get a function or variable.
     * 
     * @param string $var Symbol name.
     * @return mixed Either a Prototype object or a value, or NULL.
     */
    public function __get($var) {
        $owner = $this->_get_symbol_owner($var);
        if ($owner !== $this) {
            return $owner->$var;
        }
                
        return isset($this->_vars[$var]) ? $this->_vars[$var] : null;
    }
    
    /**
     * Check if a function or variable is set.
     * 
     * @param string $var Symbol name.
     * @return bool TRUE if set, FALSE otherwise.
     */
    public function __isset($var) {
        $owner = $this->_get_symbol_owner($var);
        if ($owner !== $this) {
            return true;
        }
                        
        return isset($this->_vars[$var]);
    }
    
    /**
     * Unset a function or variable.
     * 
     * @param string $var Symbol name.
     * @return void
     */
    public function __unset($var) {
        $owner = $this->_get_symbol_owner($var);
        if ($owner !== $this) {
            unset($owner->$var);
        }
                        
        unset($this->_vars[$var]);
    }

    /**
     * Call a prototype.
     * 
     * @param string $name Function name.
     * @param array $args Function arguments.
     * @return mixed Function return value.
     */
    public function __call($name, $args) {
        $owner = $this->_get_symbol_owner($name);
        if ($owner !== $this) {
            $return = call_user_func_array(array($owner, $name), $args);
            return $return;
        }
        
        $func = isset($this->_vars[$name]) ? $this->_vars[$name] : null;
        if (isset($func)) {
            return call_user_func_array($func, $args);
        } else if (static::$autoload && !$this->_loaded) {
            $this();
            return $this->__call($name, $args);
        }
    } 

    /**
     * Invoke the main function.
     * 
     * @param mixed ... Variable function parameters.
     * @return mixed Function return value.
     */
    public function __invoke() {
        $this->_loaded = true;
        static::$_current[] = $this;
        
        if (!isset($this->_main)) return;
        
        $return = call_user_func_array($this->_main, func_get_args());
        array_pop(static::$_current);
        return $return;
    }
    
    /**
     * Inherit a prototype object.
     * 
     * @param PHPProto $obj Prototype object.
     * @param bool $share Share the base object (when TRUE, do not clone).
     * @return void
     */
    public function __proto__(PHPProto $obj, $share = false) {
        if (!$share) $obj = clone $obj;
        
        array_unshift($this->_proto, $obj);
        
        // Inherit all the types
        $types = $obj->__typeof__();
        foreach ($types as $type) $this->__type__($type);
    }

    /**
     * Set a type for the current object.
     * 
     * @param string $type The type to set.
     * @return void
     */
    public function __type__($type) {
        if (!$this->__oftype__($type)) $this->_type[] = $type;
    }
    
    /**
     * Check if the object is of a certain type.
     * 
     * @param string $type Type to check.
     * @return bool TRUE if of type, FALSE otherwise.
     */
    public function __oftype__($type) {
        return in_array($type, $this->_type);
    }
    
    /**
     * Get the types of the current object.
     * 
     * @return array Array of types.
     */
    public function __typeof__() {
        return $this->_type;
    }
    
    /**
     * Get the prototype parent.
     * 
     * @return PHPProto Prototype instance.
     */
    public function parent() {
        return $this->_parent;
    }
    
}
