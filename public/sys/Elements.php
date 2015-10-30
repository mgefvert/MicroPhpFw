<?php

class Elements
{
    protected $_elem = array();

    public function __get($k)     { return isset($this->_elem[$k]) ? $this->_elem[$k] : null; }
    public function __set($k, $v) { $this->_elem[$k] = $v; }
    public function __isset($k)   { return isset($this->_elem[$k]); }
    public function elements()    { return $this->_elem; }
}
