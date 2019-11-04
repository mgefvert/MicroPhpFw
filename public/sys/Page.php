<?php

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
                return $this->parseSections($result, 'content');

            case TEMPLATE_PHTML:
                $result = Template::run($this->template);
                return $this->parseSections($result, 'content');

            case TEMPLATE_MD:
                $source = file_get_contents(root() . '/' . $this->template);
                $result = $this->parseSections($source, 'content');
                foreach(array_keys($result) as $key)
                    $result[$key] = \Michelf\MarkdownExtra::defaultTransform($result[$key]);
                return $result;
        }

        throw new Exception('Template not found');
    }

    public function parseSections($html, $defaultsection)
    {
        global $settings;

        $section = $defaultsection;
        $result = [$section => ''];

        $rows = explode("\n", $html);
        foreach($rows as $row)
        {
            $s = trim($row);

            if (preg_match('|^@(\w+)\s*\((.*)\)$|', $s, $matches))
            {
                $settings[$matches[1]] = trim($matches[2]);
            }
            else if (preg_match('|^@(\w+)\s*\{|', $s, $matches))
            {
                $section = $matches[1];
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
