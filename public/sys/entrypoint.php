<?php

require 'MicroFramework.php';

try {
    global $settings, $page;

    // Initialize system
    init();

    // Render the actual content page
    $settings['class']   = 'page-' . implode('-', $page->parts);
    foreach($page->render() as $key => $html)
        $settings[$key] = $html;

    // Select master template
    $master = '_master/' . $settings['style'] . '-' . ($page->page == 'index' ? 'front' : 'page') . '.phtml';
    if (!file_exists($master))
        $master = '_master/' . $settings['style'] . '.phtml';

    $result = Template::run($master, $settings);

    // Send back result
    if ($settings['cache'] && ($page->type == TEMPLATE_MD || $page->type == TEMPLATE_HTML))
    {
        // HTML or Markdown documents get one hour cache time
        header('Pragma: cache');
        header('Cache-Control: public, max-age=3600');
        header('Expires: ' . date('r', time() + 3600));
    }

    echo $result;
}
catch (Exception $ex) {
    http_response_code($ex->getCode() ?: 500);
    echo '<html><head><meta charset="utf-8"></head><body>';
    echo '<h1>An Error Occurred</h1>';
    echo $ex->getMessage();
    echo '.</body></html>';
}
