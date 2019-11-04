<?php

class EditorResult
{
    public $success;
    public $message;
    public $html;
    public $title;
    public $okButton;
    public $cancelButton;
    public $repeat;
    public $action;

    public static function action($action)
    {
        $result = new EditorResult();
        $result->success = 1;
        $result->action  = $action;
        return $result;
    }

    public static function fail($message)
    {
        $result = new EditorResult();
        $result->success = 0;
        $result->message = $message;
        return $result;
    }

    public static function page($title, $okButton, $cancelButton, $html)
    {
        $result = new EditorResult();
        $result->success      = 1;
        $result->title        = $title;
        $result->okButton     = $okButton;
        $result->cancelButton = $cancelButton;
        $result->html         = $html;
        return $result;
    }
}

class Editor
{
    private static $_users = [];

    public static function handle($template)
    {
        self::$_users = parse_ini_file(__DIR__ . '/../auth.ini');

        $mode = strtolower(a($_REQUEST, 'mode'));

        // First things first, authenticate if we're doing that.
        if ($mode == 'auth') {
            return self::authenticate();
        }

        // Secondly, if we're not authenticated, send back a login form.
        $id = a($_SESSION, 'id');
        if (!$id || a(self::$_users, $id) === null) {
            return EditorResult::page('Please login', 'Login', 'Cancel',
                Template::run('_editor/auth.phtml', ['repeat' => $_SERVER['QUERY_STRING']]));
        }

        // Now, access to all other methods...
        switch($mode)
        {
            case 'edit':
                return self::edit($template);
            case 'save':
                return self::save($template);
        }

        return EditorResult::fail('Unknown option.');
    }

    public static function authenticate()
    {
        $username = a($_REQUEST, 'u');
        $password = a($_REQUEST, 'p');

        $encrypted = a(self::$_users, $username);
        if (!$username || !$password || !$encrypted)
            return EditorResult::fail('Invalid username or password.');

        if (!password_verify($password, $encrypted))
            return EditorResult::fail('Invalid username or password.');

        session_regenerate_id(true);
        $_SESSION['id'] = $username;

        $result = EditorResult::action('repeat');
        $result->repeat = a($_REQUEST, 'repeat');
        return $result;
    }

    public static function edit($template)
    {
        $data = Template::load($template);
        return EditorResult::page('Edit page', 'Save', 'Cancel',
            Template::run('_editor/edit.phtml', ['page' => $data]));
    }

    public static function save($template)
    {
        Template::backup($template);
        Template::save($template, $_REQUEST['page']);

        return EditorResult::action('reload');
    }
}
