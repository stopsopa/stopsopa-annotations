<?php

namespace Stopsopa\Annotations\Cache;
use Exception;

/**
 * Stopsopa\Annotations\Cache\AnnotationMemcachedCache
 */
class AnnotationMemcachedCache extends AbstractAnnotationCache {
    protected $hash;
    public function __construct($hash) {
        $this->hash = $hash;
    }
    public function __destruct() {
        if ($this->save) {
            $this->save = false;
            MemcacheService::getInstance()->set($this->hash, $this);            
        }
    }
}