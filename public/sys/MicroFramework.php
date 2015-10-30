<?php

define('TEMPLATE_EMPTY', 0);
define('TEMPLATE_HTML', 1);
define('TEMPLATE_PHTML', 2);
define('TEMPLATE_MD', 3);

session_start();

if (!isset($_SESSION['xsrf']))
    $_SESSION['xsrf'] = rand(1000, 0x7FFFFFFF);

function html($str)
{
    return htmlspecialchars($str, ENT_QUOTES);
}

function init()
{
    global $page, $settings;

    $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
    $page = new Page($path);

    // Use parameters to change queries
    $settings = parse_ini_file('../settings.ini');
}

function insideRoot($filename)
{
    $root = root();
    return strcmp(substr($filename, 0, strlen($root)), $root) == 0;
}

function pr($value)
{
    return '<pre>' . print_r($value, true) . '</pre>';
}

function root()
{
    $result = realpath(__DIR__ . '/../pages/');

    if ($result && file_exists($result))
        return $result;

    throw new Exception('Invalid root folder.', 500);
}

class Elements
{
    protected $_elem = array();

    public function __get($k)     { return isset($this->_elem[$k]) ? $this->_elem[$k] : null; }
    public function __set($k, $v) { $this->_elem[$k] = $v; }
    public function __isset($k)   { return isset($this->_elem[$k]); }
    public function elements()    { return $this->_elem; }
}

class Page
{
    public $page;
    public $parts;
    public $template;
    public $type;

    public function __construct($path)
    {
        if (($n = strpos($path, '?')) !== false)
            $path = substr($path, 0, $n);

        if (strtolower(substr($path, -5)) == '.html')
            $path = substr($path, 0, -5);

        // Filter away unknown characters, all we allow is letters, digits, hyphen and slash.
        $path = preg_replace("/[^a-z0-9\-\/]/", '', strtolower($path));

        // Explode and inspect components, removing '/index' if necessary
        $this->parts = array_values(array_filter(explode('/', $path)));
        if (count($this->parts) >= 2 && end($this->parts) == 'index')
            array_pop($this->parts);
        if (empty($this->parts))
            array_push($this->parts, 'index');
        $path = implode('/', $this->parts);

        $this->page = $path;

        // We have something to try with now.
        $this->template = $this->findTemplate($path);
        if ($this->template !== null)
            return;

        // Not found? Try adding '/index' for subpages
        $this->template = $this->findTemplate($path . '/index');
        if ($this->template !== null)
            return;

        $this->type = TEMPLATE_EMPTY;
    }

    public function findTemplate($page)
    {
        if ($this->templateExists($page . '.html'))
        {
            $this->type = TEMPLATE_HTML;
            return $page . '.html';
        }

        if ($this->templateExists($page . '.phtml'))
        {
            $this->type = TEMPLATE_PHTML;
            return $page . '.phtml';
        }

        if ($this->templateExists($page . '.md'))
        {
            $this->type = TEMPLATE_MD;
            return $page . '.md';
        }

        return null;
    }

    public function render()
    {
        switch($this->type)
        {
            case TEMPLATE_EMPTY:
                $result = null;
                break;

            case TEMPLATE_HTML:
                $result = file_get_contents(root() . '/' . $this->template);
                return $this->split($result, 'content');

            case TEMPLATE_PHTML:
                $result = Template::run($this->template);
                return $this->split($result, 'content');

            case TEMPLATE_MD:
                $source = file_get_contents(root() . '/' . $this->template);
                $result = $this->split($source, 'content');
                foreach(array_keys($result) as $key)
                    $result[$key] = \Michelf\Markdown::defaultTransform($result[$key]);
                return $result;
        }

        throw new Exception('Template not found');
    }

    public function split($html, $defaultsection)
    {
        $section = $defaultsection;
        $result = [$section => ''];

        $rows = explode("\n", $html);
        foreach($rows as $row)
        {
            $s = trim($row);
            if (substr($s, 0, 1) == '@' && substr($s, -1) == '{')
            {
                $section = trim(substr($s, 1, -2));
                if (!isset($result[$section]))
                    $result[$section] = '';
            }
            else if ($s == '}')
                $section = $defaultsection;
            else
                $result[$section] .= $row . "\n";
        }

        return $result;
    }

    public function templateExists($filename)
    {
        $path = realpath(root() . '/' . $filename);
        return $path && insideRoot($path) && file_exists($path);
    }
}

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

function class_autoload($className)
{
    $className = preg_replace('/[^A-Za-z\\\\]/', '', $className);
    $className = str_replace('\\', '/', $className);
    if (substr($className, 0, 1) == '/')
        $className = substr($className, 1);

    require_once $className . '.php';
}

if (!spl_autoload_register('class_autoload'))
    die('Fatal error: Unable to register class autoloader');
