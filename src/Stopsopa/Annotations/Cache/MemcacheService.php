<?php

namespace Stopsopa\Annotations\Cache;
use Memcache;
/**
 * Stopsopa\Annotations\Cache\MemcacheService
 */
class MemcacheService {
    /**
     * @var Memcache 
     */
    protected static $m;
    protected static function getInstance() {
        static::$m or (static::$m = new Memcache());          
        return static::$m;
    }
    public static function addServer($host, $port = 11211) {
        static::getInstance()->addServer($host, $port);
    }  
    public static function flush() {
        static::getInstance()->flush();
    }  
}