<?php
/**
 * php-proto : Prototype programming interface.
 *
 * @author          Francis Lacroix <flacroix897 at gmail.com>
 * @package         PHPProto
 * @version         1.0
 * @license         http://www.gnu.org/licenses/lgpl-3.0.txt
 */

include __DIR__.'/proto.inc.php';

/**
 * Prototype example.
 * 
 * @category        Sample
 * @version         1.0
 */

/**
 * Our "engine" object. This object is required by all the other objects.
 */
$engine = proto(function($cyl){
    this()->force = 0;
    this()->cyl = $cyl;
    
    this()->gas = function(){
        parent()->force++;
        echo 'Engine force is now '.parent()->force."\n";
    };
    
    return this();
}, 'engine');

/**
 * Our base "vehicle" object. This object requires an "engine" object, and can
 * only give it gas. It doesn't do anything else.
 */
$vehicle = proto(function($engine){
    this()->engine = $engine;

    this()->gas = function(){
        parent()->engine->gas();
    };
    
    return this();
}, 'vehicle');

/**
 * This is a derived "car" object of the "vehicle" type. It allows you to "roll" if
 * enough force is given to its "engine" object component.
 */
$car = proto(function($engine) use($vehicle){
    proto( $vehicle($engine) );
    
    this()->roll = function(){
        if (parent()->engine->force < 1) {
            echo "Not enough force to roll, give it some gas.\n";
        } else {
            echo "Rolling...\n";
        }
    };
    
    return this();
}, 'car');

/**
 * This is a derived "plane" object of the "vehicle" type. It allows you to "fly" if
 * enough force is given to its "engine" object component.
 */
$plane = proto(function($engine) use($vehicle){
    proto( $vehicle($engine) );
    
    this()->fly = function(){
        if (parent()->engine->force < 2) {
            echo "Not enough force to fly, give it some gas.\n";
        } else {
            echo "Flying...\n";
        }
    };
    
    return this();
}, 'plane');

/**
 * This is a derived "amphibious" object of the "car" and "plane" type. It is also a derived
 * of the "vehicle" type because of "car" and "plane". This allows multiple inheritance. This
 * object can "roll" and "fly" without having to implement any logic.
 */
$amphibious = proto(function($engine) use($car, $plane){
    proto( $car($engine) );
    proto( $plane($engine) );

    return this();
}, 'amphibious');

// We create an engine
$mEngine    = clone $engine(8);

// We create a car, give it gas and make it roll
$mCar       = clone $car($mEngine);
$mCar->gas();
$mCar->roll();

// We create a plane, give it gas and make it fly
$mPlane     = clone $plane($mEngine);
$mPlane->gas();
$mPlane->gas();
$mPlane->fly();

// We create an amphibious vehicle, transfer the plane engine into it, make it roll, then fly
$mAmphib    = clone $amphibious($mPlane->engine);
$mAmphib->roll();
$mAmphib->fly();
var_dump(typeof($mAmphib));

// The car is broken, we cannot make it roll anymore.
$mCar->roll = function(){
    echo "This car is broken, sorry!\n";  
};
$mCar->roll();

// We have to get a new car, but we can reuse the engine
$nCar       = clone $car($mCar->engine);
$nCar->roll();
