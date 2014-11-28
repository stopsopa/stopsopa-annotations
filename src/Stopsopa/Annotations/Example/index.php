<?php

use Stopsopa\Annotations\AnnotationParser;
use Stopsopa\Annotations\Cache\AnnotationApcCache;
use Stopsopa\Annotations\Cache\AnnotationFileCache;
use Stopsopa\Annotations\Cache\MemcacheService;
use Stopsopa\Annotations\Cache\AnnotationMemcachedCache;
use Stopsopa\Annotations\Example\TestClass;

error_reporting(E_ALL);
ini_set('display_errors',1);
header('Content-type: text/plain; charset=utf-8');

// creating main object of parser
$parser = new AnnotationParser();

/**
 * Below you have examples of using cache systems based on:
 * Files,     
 * Memcached, (Recomended)
 * Apc        (Not recomended in new versions of PHP)
 */

// apc cache example ==== vvv
//    apc_clear_cache();apc_clear_cache('user');pc_clear_cache('opcode');die('clear');
    
//    $salt = 'kdjdjdjk'; // project salt
//    $key  = 'stopsopaannotationcache';
//    $apccache = md5($salt).'-'.$key;
//    /* @var $data Test */
//    $cache = apc_fetch($apccache) ?: new AnnotationApcCache($apccache); 
////    $cache->clear();
//    $parser->setCache($cache);
// apc cache example ==== ^^^

// file cache example ==== vvv
//    $cache = new AnnotationFileCache(dirname(__FILE__).'/cachedir');
////    $cache->clear();
//    $parser->setCache($cache);
// file cache example ==== ^^^

// memcached ====== vvv
    $salt = 'kdjdjdjk'; // project salt
    $key  = 'stopsopaannotationcache';
    $mkey = md5($salt).'-'.$key;
    MemcacheService::addServer('localhost', 11211); 
    
    $cache = MemcacheService::getInstance()->get($mkey);
    
    if (!$cache) 
        MemcacheService::getInstance()->set($mkey, $cache = new AnnotationMemcachedCache($mkey));    
    
//    $cache->clear();
    $parser->setCache($cache);
// memcached ====== ^^^



// parser annotations from class and return as an structured array
print_r($parser->getAnnotations(new TestClass()));
