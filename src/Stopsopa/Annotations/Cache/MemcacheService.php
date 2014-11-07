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
    protected static function _init() {
        if (!static::$m) 
            static::$m = new Memcache();        
    }
    public static function addServer($host, $port = 11211) {
        static::_init();
        static::$m->addServer($host, $port);
    }    
    /**
     * @return Memcache
     */
    public static function getMemcache() {
        return static::$m;
    }
}