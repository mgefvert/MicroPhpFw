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
