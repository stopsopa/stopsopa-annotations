<?php

namespace Stopsopa\Annotations\Cache;
use Exception;

class AnnotationFileCache extends AbstractAnnotationCache {
    protected $dir;
    public function __construct($dir) {
        $this->dir = $dir;
        if (!file_exists($dir)) 
            throw new Exception("Directory '$dir' for ".get_class()." doesn't exist");
        
        if (!is_dir($dir))  
            throw new Exception("'$dir' is not directory");
        
        if (!is_writable($dir))  
            throw new Exception("'$dir' is not writtable");
    }
    public function clear() {
        $this->deleteEntireDir($this->dir, true);
        return $this;
    }
    protected function _encodeName($name) {
        $sha = sha1($name);
        return substr($sha, 0, 3).DIRECTORY_SEPARATOR.substr($sha, 3, 3).DIRECTORY_SEPARATOR.substr($sha, 6);
    }
    protected function _getFile($name) {
        return $this->dir.DIRECTORY_SEPARATOR.$this->_encodeName($name);
    }
    protected function _mkdir($dir) {
        mkdir($dir, 0777, true);
        return $this;
    }
    public function set($key = null, $data) {
        $file = $this->_getFile($key);
        
        $this->_mkdir(dirname($file));                
       
        file_put_contents($file, json_encode($data));
        
        return $this;
    }
    public function get($key = null) {
        $file = $this->_getFile($key);
        
        if (file_exists($file)) 
            return json_decode(file_get_contents($file), true);
        
        return null;
    }
    public static function deleteEntireDir($directory, $empty=FALSE) {   
        // [http://lixlpixel.org/recursive_function/php/recursive_directory_delete/] g(delete directory recursively php)
        if (substr($directory,-1) == '/') 
            $directory = substr($directory,0,-1);
        if (!file_exists($directory) || !is_dir($directory))
            return FALSE;
        elseif (!is_readable($directory))
            return FALSE;
        else {
            $handle = opendir($directory);
            while (FALSE !== ($item = readdir($handle))) {
                if($item != '.' && $item != '..') {
                    $path = $directory.'/'.$item;
                    if (is_dir($path))   
                        self::deleteEntireDir($path);
                    else                        
                        unlink($path);
                }
            }
            closedir($handle);
            if($empty == FALSE) {
                if(!rmdir($directory)) 
                    return FALSE;                
            }
            return TRUE;
        }
     }
}