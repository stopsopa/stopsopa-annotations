<?php

namespace Stopsopa\Annotations\Cache;
use Exception;
use Stopsopa\Annotations\Cache\MemcacheService;

/**
 * Stopsopa\Annotations\Cache\AnnotationMemcachedCache
 */
class AnnotationMemcachedCache extends AbstractAnnotationCache {
    protected $hash;
    public function __construct($hash) {
        $this->hash = $hash;
        $that = $this;
//        register_shutdown_function(function () use ($that) {
//            $that->save();            
//        });
    }
    public function save() {
        if ($this->save) {
            $this->save = false;
            MemcacheService::getInstance()->set($this->hash, $this);            
        }
        return $this;
    }    

    public function __destruct() {
        return $this->save();
    }
}