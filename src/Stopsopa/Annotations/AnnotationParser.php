<?php

namespace Stopsopa\Annotations;
use ReflectionClass;
use Stopsopa\Annotations\AnnotationException;
use Stopsopa\Annotations\Cache\AbstractAnnotationCache;


/**
 * Stopsopa\Annotations\AnnotationParser
 */
class AnnotationParser {
    /**
     * @var AbstractAnnotationCache 
     */
    protected $cache;
    public function setCache(AbstractAnnotationCache $cache) {
        $this->cache = $cache;
    }
    /**
     * @param string|ReflectionClass|Object $class
     * @throws AnnotationException
     */
    public function getAnnotations($class) {
        
        if (is_string($class)) {               
            if ($this->cachenamespaceclass === $class) 
                return $this->cacheclass; 
            
            if ($data = $this->_getFromCache($class)) // sprawdzę tutaj aby nie tworzyć bez potrzeby ReflectionClass
                return $data;
            
            if (class_exists($class)) 
                $class = new ReflectionClass($class);                            
            else 
                throw new AnnotationException("Class '$class' doesn't exist, can't create ReflectionClass");            
        }
        elseif (is_object($class) && !$class instanceof ReflectionClass) {
            $class = new ReflectionClass($class); 
        }
        
        if (!$name = $class->getName()) 
            throw new AnnotationException("Unknown classname in ReflectionClass");

        if ($this->cachenamespaceclass === $name)
            return $this->cacheclass;             
        
        if ($data = $this->_getFromCache($name)) 
            return $data;
        
        return $this->_saveInCache($name, $this->_getAnnotations($class));                 
    }
    protected function _parseOneAnno($anno) {
        
        if ($this->_trimComment($anno)) 
            return $this->_parseTrimmed($anno);
        
        return false;
    }
    protected function _trimComment(&$a) {
        
        if (!is_string($a)) 
            return false;
        
        $b = trim($a);
        if (preg_match('#^/\*\*\s.*\*+/$#s', $b)) {
            $a = preg_replace('#^/\*\*\s(.*?)\*+/$#s', '$1', $b); 
            $a = explode("\n", $a);
            foreach ($a as &$e) {
                if (preg_match('#^\s*\*#', $e)) {
                    $e = preg_replace('#^\s*\*(.*)$#', '$1', $e);
                }
            }
            $a = implode("\n", $a);
            return true;
        }
        return false;
    }

    protected $used;
    protected function _getAnnotations(ReflectionClass $reflecton) { 
        
        $this->used = $this->getUsedClassList($reflecton);
        $a = array(
            'used'       => $this->used,
            'main'       => $this->_parseOneAnno($reflecton->getDocComment()),
            'methods'    => array(),
            'properties' => array()
        );    

        /* @var $m ReflectionMethod */
        foreach ($reflecton->getMethods() as $m) 
            $a['methods'][$m->getName()] = $this->_parseOneAnno($m->getDocComment());

        /* @var $p ReflectionProperty */
        foreach ($reflecton->getProperties() as $p) 
            $a['properties'][$p->getName()] = $this->_parseOneAnno($p->getDocComment()); 
        
        return $a;
    }
    protected $c;
    protected $i;
    protected $str;
    /**
     * Parsuje całą annotację od początku
     * - zaczyna od tego znaku
     * - zatrzymuje się na ?????????????
     */
    protected function  _parseTrimmed(&$str) {
        $this->str = $str;
        $this->c = mb_strlen($str);
        $this->i = 0;
        
        $c = '';
        
        while (is_string($s = $this->_getChar())) {
            
            if ($this->_isFirstCharInLineAndAt($s)) 
                break;
                        
            $c .= $s;            
            ++$this->i;
        }
        
        $t = array();
        while (is_string($this->_getChar())) {
            $a = $this->_parse($a = array());
            
            if (count($a)) 
                $t[] = $a;  
            
            ++$this->i;
        }
        
        return array(
            'comment' => $c,
            'anno'    => $t
        );      
    }

    protected function _d($c = '') {
        niechginiee('<'.$this->_f($this->_getChar()).'>'. $this->_f(mb_substr($this->str, $this->i, 60)).'<');
    }
    protected function _f($k) {
        return str_replace("\r",'\r',str_replace("\n",'\n',$k));
    }

    /**
     * Szuka początku annotacji (od nowej linii) i odpala czytanie od znaku @ (z pominięciem tego znaku)
     * - zaczyna od tego znaku
     * - zatrzymuje się na nie przetworzonym znaku 
     */
    protected function &_parse(&$a) {
        while (is_string($s = $this->_getChar())) {
            if ($this->_isFirstCharInLineAndAt($s)) { 
                
                ++$this->i;
                $an = $this->_getAnnoName();
                $a  = $this->_parseAfterAnno();
                
                if (isset($a['d'])) { 
                    $a['t'] = 'l';
                }
                else { // jeśli nie posiada array 'd' to dorzucam jeszcze komentarz i 
                    isset($a['c']) || ($a['c'] = '');
                    isset($a['isknowntype']) || ($a['isknowntype'] = false);                                                    
                }
                
                $a['n'] = $an;
                $a['t'] = 't';
                
                if ($n = $this->resolveNamespace($an))
                    $a['knowntype'] = $n;        
                $a['isknowntype'] = isset($a['knowntype']);    
                
                return $a;
            }
            ++$this->i;
        }
        return $a;
    }
    /**
     * Parsuje to co jest bezpośrednio po nazwie annotacji
     * - zaczyna od tego znaku
     */
    protected function _parseAfterAnno() {        
        while (is_string($s = $this->_getChar())) {
            
            if ($this->_isDelimiter($s)) 
                return array('d' => array(), 'l' => array());
            
            if ($this->_isSpace($s)) {
                ++$this->i;
                continue;
            }
            
            if ($s === '(' || $s === '{') {
                return $this->_parseBrackets();            
            }
            
            return $this->_parseTypeAndComment();
        }
        
    }

    /**
     * Parsuje resztę linii jako typ i komentarz
     * - zaczyna od tego znaku który nie jest spacją ani znakiem '('
     */
    protected function _parseTypeAndComment() {
        $r = array('rawtype' => null, 'c' => '');
        
        $tp = '';
        $i = false;
        while (is_string($s = $this->_getChar())) {
            
            if ($this->_isWhiteChar($s)) 
                break;            
            
            $i = true;
            
            $tp .= $s;
            ++$this->i;            
        }
        // tutaj podejmę decyzję gdzie podpiąć typ:
        // teraz dorzucę bezmyślnie do 'rawtype'
        // takze trzeba zaznaczyć 'isknowntype' => false,
        $i and ($r['rawtype'] = $tp);
        
        $ltp = trim(mb_strtolower($tp));
        switch ($ltp) {
            case 'integer':
            case 'flaot':            
            case 'boolean':
            case 'null':
            case 'object':
            case 'array':
            case 'string':
            case 'resource':
            case 'callback':
                $r['knowntype'] = $ltp;
                break;
            case 'clb':
                $r['knowntype'] = 'callback';
                break;                
            case 'res':
                $r['knowntype'] = 'resource';
                break;    
            case 'str':
                $r['knowntype'] = 'string';
                break;    
            case 'obj':
                $r['knowntype'] = 'object';
                break;    
            case 'bool':
                $r['knowntype'] = 'boolean';
                break;    
            case 'int':
                $r['knowntype'] = 'integer';
                break;
        }             
        
        if (!isset($r['knowntype']) && $n = $this->resolveNamespace($tp))
            $r['knowntype'] = $n;        
        
        $r['isknowntype'] = isset($r['knowntype']);        
        
        $c = '';        
        while (is_string($s = $this->_getChar())) { 
            if ($this->_isFirstCharInLineAndAt($s)) { 
                --$this->i;
                break;
            }
            
            $c .= $s;
            ++$this->i;
        }
        $r['c'] = ltrim($c);
        
        return $r;
    }
    public function resolveNamespace($namespace) {          
        
        if (!$this->used) 
            throw new AnnotationException("First use method AnnotationParser->getAnnotations() to fill cache");
        
        if (array_key_exists($namespace, $this->used)) 
            return $this->used[$namespace];
        
        $namespace = '\\'.ltrim($namespace, '\\');
        
        if (in_array($namespace, $this->used)) 
            return $namespace;
        
        if (class_exists($namespace)) 
            return $namespace;
        
        return false;
    }
    /**
     * Zwraca listę klas które zostały użyte przy tej klasie za pomoca use
     * Całkiem to ciężkie, konieczne będzie zastosowanie tutaj cache
     */
    public function getUsedClassList(ReflectionClass $reflection) {
        $c = explode("\n", file_get_contents($reflection->getFileName()));

        $prev = false;
        foreach ($c as $k => &$a) {
            $a = trim($a);

            if (preg_match('#^use\s+#i', $a)) {
                $prev = $k;
                continue;
            }
            if ($prev !== false) {       
                if (trim($a) && preg_match('#^as\s+#i', $a)) {
                    $c[$prev] .= ' '.$a;
                    $a = '';
                    $prev = false;
                }
                continue;
            } 
        }

        $list = array();
        $start = false;
        foreach ($c as &$a) {    
            $k = strtolower($a);

            if (preg_match('#^namespace\s+#i', $k)) 
                $start = (preg_replace('#^namespace\s+([^\s;]+).*$#i', '$1', $a) == $reflection->getNamespaceName());    

            if ($start && preg_match('#^use\s+#i', $k)) {
                preg_match('#^use\s+([^\s]+)(?:\s+as\s+([^\s]+))?\s*;$#i', $a, $matches);
                if (isset($matches[2])) {
                    $list[$matches[2]] = '\\'.ltrim($matches[1], '\\');
                }
                else {
                    $key = (strpos($matches[1], '\\') === false) ? $matches[1] : preg_replace('#^.*\\\\([^\\\\]+)$#i', '$1', $matches[1]);                        
                    $list[$key] = '\\'.ltrim($matches[1], '\\');            
                }        
            }
        }
        return $list;
    }



    /**
     * Parsuje zawartość między parzystymi nawiasami
     * - zaczyna od tego znaku który powinien być zkamiem '(' lub '{'
     */
    protected function _parseBrackets() {
        $d = array();
        $l = array();
        
        $bracket = $this->_getChar();
        ++$this->i;
        
        while (is_string($s = $this->_getChar())) {
            if ($this->_isWhiteChar($s) || $this->_isDelimiter($s)) {
                ++$this->i;
                continue;
            }
            
            if ($s === '(' || $s === '{') {
                $dd = $this->_parseBrackets(); 
                $dd['t'] = 's';                
                $l[] = $dd;
                continue;
            }
            
            if ($s === ')') {
                if ($bracket === '(') {
                    ++$this->i;
                    return array('d' => $d, 'l' => $l);
                }
                else {
                    throw new Exception("Wrong closing bracket: ".$this->_errorChar());                        
                }
            }
            if ($s === '}') {
                if ($bracket === '{') {
                    ++$this->i;
                    return array('d' => $d, 'l' => $l);
                }
                else {
                    throw new Exception("Wrong closing bracket: ".$this->_errorChar());                        
                }
            }
            
            $key = $this->_parseKey();
            
            $next = $this->_getChar(0, true);
            $this->i = $this->offsettmp;
            
            switch ($next) {
                case '@':
                    $l[] = $this->_parseInnerAnno();
                    break;
                case ',':
                case '}':
                case ')':
                    $l[] = $key;
                    break;
                case ':':
                case '=':                 
                    
                    $next = $this->_getChar(1, true); // looking for another not white char
                    $this->i = $this->offsettmp;   
                    
                    if ($this->_getChar() == '@') {
                        $d[$key] = $this->_parseInnerAnno();
                    }
                    else {
                        $d[$key] = $this->_parseKey();                        
                    }
            }            
        } 
        ++$this->i;
        
        return array(
            'd' => $d, 
            'l' => $l
        );
    }
    /**
     * Szuka początku annotacji (od nowej linii) i odpala czytanie od znaku @ (z pominięciem tego znaku)
     * - zaczyna od tego znaku
     * - zatrzymuje się na nie przetworzonym znaku 
     */
    protected function &_parseInnerAnno() {
        $a = array();
        while (is_string($s = $this->_getChar())) {
            if ($s === '@') {                
                ++$this->i;
                $an = $this->_getAnnoName();
                $a  = $this->_parseAfterAnno();
                
                $a['t'] = 'l';                
                $a['n'] = $an;
                
                if ($n = $this->resolveNamespace($an))
                    $a['knowntype'] = $n;   
                
                $a['isknowntype'] = isset($a['knowntype']);        

                $a['t'] = 't';
                return $a;
            }
            ++$this->i;
        }
        return $a;
    }

    protected function _errorChar() {
        $e = 60;
        if ($this->i > $e) {
            $k = mb_substr($this->str, ($this->i - $e), $e);
        }
        else {
            $k = mb_substr($this->str, 0, $this->i);            
        }
        return $k."-->".$this->_getChar()."<--".mb_substr($this->str, $this->i+1);
    }

    protected $cachei;
    protected $cachec;
    protected $thisIsFirstNotWhiteChar = false;
    protected $canBeFirstNWC = true;
    protected function _isFirstCharInLineAndAt(&$s) {
        return $s === '@' && $this->_isFirstCharInLine();
    }

    protected function _isFirstCharInLine() {
        $this->_getChar();
        return $this->thisIsFirstNotWhiteChar;
    }
    protected $offsettmp;
    protected function _getChar($o = null, $nextNotWhiteChar = false) {
        
        if ($o || $nextNotWhiteChar) {
            $this->offsettmp = $offset = $this->i + $o;            
            
            if ( ($offset > $this->c) || ($offset < 0) )
                return null;
            
            $s = mb_substr($this->str, $offset, 1);
            
            if ($nextNotWhiteChar && $this->_isWhiteChar($s)) 
                $s = $this->_getChar($o+1, true);
            
            return $s;
        }
        
        if ($this->i > $this->c)
            return false;
        
        if ($this->cachei !== $this->i) {
            $this->cachei = $this->i;
            $this->cachec = mb_substr($this->str, $this->i, 1);
            
            if ($this->thisIsFirstNotWhiteChar) {
                $this->thisIsFirstNotWhiteChar = false;
                $this->canBeFirstNWC = false;
            }
            
            if ($this->canBeFirstNWC && !$this->_isWhiteChar($this->cachec)) {
                $this->thisIsFirstNotWhiteChar = true;
                $this->canBeFirstNWC = false;
            }
            
            if ($this->_isNewLine($this->cachec)) {
                $this->thisIsFirstNotWhiteChar = false;
                $this->canBeFirstNWC = true;
            }            
            
        }
        
        return $this->cachec;        
    }
    /**
     * Czyta nazwę annotacji
     * - przesuwa się do następnego znaku   
     * - zatrzymuje się na nie przetworzonym znaku   
     */
    protected function _getAnnoName() {                
        $s = '';
        while (is_string($c = $this->_getChar())) {
            
            if ($this->_isForbiddenChar($c)) 
                break;
            
            $s .= $c;
            ++$this->i;
        }
        return $s;
    }
    /**
     * Parsuje klucz w postaci:
     * jakasnazwa   // nie mylić z jakas nazwa  - spacja w środku nie przejdzie w tym przypadku
     * "jakas \" nazwa" // tutaj dopuszczalne są spacje
     * 'jakas \' nazwa" // tutaj dopuszczalne są spacje
     * - zaczyna od tego znaku który może być dowolnym znakiem i ten znak jest sprawdzany czy jest to " lub ' jeśli nie to nie wpuszcza znaków w stringu {(}),:=
     * - kończy na następnym nie przetworzonym znaku
     */
    protected function _parseKey() {
        $s = '';
        $char = $this->_getChar();  
        if ($char === '{' || $char === '(') {            
            $s = $this->_parseBrackets(); 
            $s['t'] = 's';
            return $s;
        }
        if ($char === '"' || $char === "'") {
            
            $last = $char;
            ++$this->i;
            
            while (is_string($c = $this->_getChar())) {
                if ($c === '\\') {
                    if ($last === '\\') {
                        $s .= $c;
                        $last = null;
                    }
                    else {
                        $last = $c;                        
                    }
                }
                else {
                    if ($c === $char) {
                        if ($last === '\\') {
                            $s .= $c;
                            $last = $c;
                        }
                        else {                            
                            ++$this->i;
                            return $s;
                        }
                    }
                    else {                        
                        if ($last === '\\') 
                            $s .= '\\';                            
                        
                        $s .= $c; 
                        $last = $c;
                    }
                }
                ++$this->i;
            }
            
        }
        else {
            
            while (is_string($c = $this->_getChar())) {
                
                if ($this->_isForbiddenChar($c)) {
                    $ss = mb_strtolower($s);
                    if (is_numeric($ss)) {
                        if (strpos($ss, '.') === false) 
                            return (int)$ss; 
                        
                        return (float)$ss;
                    }
                    switch ($ss) {
                        case 'true':
                            return true;
                        case 'false':
                            return false;
                        case 'null':
                            return null;
                    }
                    return $s;
                }
                
                $s .= $c;
                ++$this->i;
            }
            
        }
        return $s;
    }    
    protected function _isNewLine(&$s) {
        return ($s === "\n") || ($s === "\r");
    }
    protected function _isSpace(&$s) {
        return ($s === ' ') || ($s === "\t");
    }
    protected function _isWhiteChar(&$s) {
        return $this->_isSpace($s) || $this->_isNewLine($s);
    }
    protected function _isDelimiter(&$s) {
        return ($s === '=') || ($s === ':') || ($s === ',');
    }
    protected function _isForbiddenChar(&$s) { 
        return $this->_isDelimiter($s) || ($s === '{') || ($s === '(') || ($s === ')') || ($s === '}') || $this->_isWhiteChar($s) || ($s === '@');
    }     
    /**
     * Cache do późniejszej implementacji
     */
    protected $cacheclass;
    protected $cachenamespaceclass;
    protected function _getFromCache($namespace) {
        
        if ($this->cache) 
            return $this->cache->get($namespace);           
        
        return null;
    }
    protected function &_saveInCache($namespace, &$data) {
        $this->cachenamespaceclass = $namespace;
        $this->cacheclass          = $data; 
        
        if ($this->cache) 
            $this->cache->set($namespace, $data);        
        
        return $data;
    }
}