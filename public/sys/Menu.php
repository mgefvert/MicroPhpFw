<?php

class MenuRenderOptions
{
    public $query;
    public $urls = array();

    public function __construct($pageParts)
    {
        for ($i=1; $i<=count($pageParts); $i++)
            $this->urls[] = '/' . implode('/', array_slice($pageParts, 0, $i));
    }
}

class MenuItem
{
    public $title;
    public $url;
    public $children = array();
    public $active;

    public function __construct($title)
    {
        $this->title = $title;
    }

    public function render(MenuRenderOptions $options)
    {
        $result = '';

        if ($this->title[0] == '@')
        {
            $title = '<em>' . html(substr($this->title, 1)) . '</em>';
            $result .= '</ul><ul>';
        }
        else
            $title = html($this->title);

        $active = in_array($this->url, $options->urls) ? ' class="active"' : '';
        $children = !empty($this->children) ? $this->renderChildren($options) : '';

        $result .= "<li><a$active href='{$this->url}{$options->query}'>$title</a>$children</li>";

        return $result;
    }

    public function renderChildren(MenuRenderOptions $options)
    {
        if (empty($this->children))
            return null;

        $result = '<ul>';
        foreach($this->children as $child)
            $result .= $child->render($options);
        $result .= '</ul>';

        return $result;
    }

    public function set($title, $url)
    {
        $list = array_filter(explode('/', $title));

        $item = $this;
        foreach($list as $n)
        {
            if (!isset($item->children[$n]))
                $item->children[$n] = new MenuItem($n);

            $item = $item->children[$n];
        }

        $item->url = $url;
    }
}

class MenuItems
{
    private $_menu;
    private $_options;

    public function __construct(array $items)
    {
        global $page;

        $this->_options = new MenuRenderOptions($page->parts);

        $this->_menu = new MenuItem('#top');
        foreach($items as $title => $url)
            $this->_menu->set($title, $this->processUrl($url));
    }

    private function processUrl($url)
    {
        // If empty value, ensure it's null
        if (!trim($url))
            return '#';

        // If it's a real link, don't touch it up
        if (strpos($url, '//') !== false)
            return $url;

        // Page, ensure it meets standards
        if (substr($url, 0, 1) != '/')
            $url = '/' . $url;

        return $url;
    }

    public function render()
    {
        return $this->_menu->renderChildren($this->_options);
    }

    public function __toString()
    {
        return $this->render();
    }
}

class Menu
{
    private static $__menu;

    private static function load()
    {
        if (self::$__menu == null)
            self::$__menu = parse_ini_file('../menu.ini', true);
    }

    public static function main()
    {
        self::load();
        return new MenuItems(isset(self::$__menu['main']) ? self::$__menu['main'] : array());
    }

    public static function submenu()
    {
        global $page;

        self::load();
        for ($i=1; $i<=count($page->parts); $i++)
        {
            $url = implode('/', array_slice($page->parts, 0, $i));
            if (isset(self::$__menu[$url]))
                return new MenuItems(self::$__menu[$url]);
        }

        return new MenuItems([]);
    }

}
