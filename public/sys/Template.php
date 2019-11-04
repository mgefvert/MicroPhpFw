<?php

class Template extends Elements
{
    private $_filename;

    public function __construct($filename)
    {
        $this->_filename = $filename;
    }

    public static function realfile($filename)
    {
        $realfile = realpath(root() . '/' . $filename);
        if (!insideRoot($realfile))
            throw new Exception("Template {$filename} does not exist");

        return $realfile;
    }

    public function parse()
    {
        $filename = self::realfile($this->_filename);
        ob_start();
        global $settings, $page;
        include $filename;
        return ob_get_clean();
    }

    public static function backup($template)
    {
        $realfile = self::realfile($template);
        $bakfile = __DIR__ . '/../../backups/' . preg_replace('/[^\w]/', '-', $template) . '.' . date('YmdHis');

        $data = file_get_contents($realfile);
        file_put_contents($bakfile, $data);
    }

    public static function load($template)
    {
        return file_get_contents(self::realfile($template));
    }

    public static function run($filename = null, array $parameters = null)
    {
        $tmpl = new Template($filename);
        if (!is_null($parameters))
            foreach($parameters as $k => $v)
                $tmpl->$k = $v;

        return $tmpl->parse();
    }

    public static function save($template, $page)
    {
        $realfile = self::realfile($template);
        file_put_contents($realfile, $page);
    }
}
