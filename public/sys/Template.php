<?php

class Template extends Elements
{
    private $_filename;

    public function __construct($filename)
    {
        $this->_filename = $filename;
    }

    public function parse()
    {
        $filename = realpath(root() . '/' . $this->_filename);
        if (!insideRoot($filename))
            throw new Exception("Template {$this->_filename} does not exist");

        ob_start();
        global $settings, $page;
        include $filename;
        return ob_get_clean();
    }

    public static function run($filename = null, array $parameters = null)
    {
        $tmpl = new Template($filename);
        if (!is_null($parameters))
            foreach($parameters as $k => $v)
                $tmpl->$k = $v;

        return $tmpl->parse();
    }
}
