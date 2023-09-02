# DEPRECATED
Created in 2014 - quite old now and not maintained.

C'mon, I was young. Don't judge me ;P

<h1>stopsopa/annotations</h1>

Standalone, lightweight annotation parser for php with build in cache system based on:

  - filesystem
  - Memcached
  - Apc (Deprecated technology)


***



## Instalation

Library is available in composer through packagist.org (https://packagist.org/packages/stopsopa/annotations)

    {
        "require" : {
            "stopsopa/annotations": "dev-master"
        }
    }

## Using: 


    use Stopsopa\Annotations\AnnotationParser;
    use Stopsopa\Annotations\Example\TestClass; 

    // creating main object of parser
    $parser = new AnnotationParser(); 
    print_r($parser->getAnnotations(new TestClass()));

## Cache

### Filesystem cache

    use Stopsopa\Annotations\AnnotationParser;
    use Stopsopa\Annotations\Cache\AnnotationFileCache;
    use Stopsopa\Annotations\Example\TestClass; 

    // creating main object of parser
    $parser = new AnnotationParser(); 
    
    $cache = new AnnotationFileCache(dirname(__FILE__).'/cachedir');
    //    $cache->clear();  // you can clear all cache 
    $parser->setCache($cache);

    print_r($parser->getAnnotations(new TestClass()));

### Apc cache

    use Stopsopa\Annotations\AnnotationParser;
    use Stopsopa\Annotations\Cache\AnnotationApcCache;
    use Stopsopa\Annotations\Example\TestClass; 

    // creating main object of parser
    $parser = new AnnotationParser(); 
    
    $salt = 'kdjdjdjk'; // project salt
    $key  = 'stopsopaannotationcache';
    $apccache = md5($salt).'-'.$key;
    /* @var $data Test */
    $cache = apc_fetch($apccache) ?: new AnnotationApcCache($apccache); 
    //    $cache->clear(); // you can clear all cache 
    $parser->setCache($cache);

    print_r($parser->getAnnotations(new TestClass()));


### Memcached cache

    use Stopsopa\Annotations\AnnotationParser;
    use Stopsopa\Annotations\Cache\MemcacheService;
    use Stopsopa\Annotations\Cache\AnnotationMemcachedCache;
    use Stopsopa\Annotations\Example\TestClass; 

    // creating main object of parser
    $parser = new AnnotationParser(); 
    
    $salt = 'kdjdjdjk'; // project salt
    $key  = 'stopsopaannotationcache';
    $mkey = md5($salt).'-'.$key;
    MemcacheService::addServer('localhost', 11211); 
    
    $cache = MemcacheService::getInstance()->get($mkey);
    
    if (!$cache) 
        MemcacheService::getInstance()->set($mkey, $cache = new AnnotationMemcachedCache($mkey));    
    
    //    $cache->clear(); // you can clear all cache 
    $parser->setCache($cache);

    print_r($parser->getAnnotations(new TestClass()));



### License

The MIT License (MIT)
Copyright (c) 2014 Szymon Działowski
Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

