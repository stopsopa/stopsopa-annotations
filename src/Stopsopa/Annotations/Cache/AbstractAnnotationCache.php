<?php

namespace Stopsopa\Annotations\Cache;

/**
 * Stopsopa\Annotations\Cache\AbstractAnnotationCache
 */
abstract class AbstractAnnotationCache { 
    protected $data;
    protected $save;
    public function clear() {
        $this->set(null, array());
        $this->save = true;
        return $this;
    }
    public function set($key = null, $data) {
        $this->save = true;
        
        if (is_null($key)) {
            $this->data = $data;
            return $this;            
        }        
        
        if (!$this->data) 
            $this->data = array();
       
        $this->data[$key] = $data;
        
        return $this;
    }
    public function get($key = null) {
        
        if (!$this->data) 
            $this->data = array();
        
        if (is_null($key))
            return $this->data;
        
        if (!array_key_exists($key, $this->data)) 
            return null;
        
        return $this->data[$key];                
    }
}