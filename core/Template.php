<?php
/**
 * Provides templating functionality by replacing placeholder with content.
 *
 * ## Usage
 *
 * Templates are defined in .tpl files and live in *\/app\/templates*. Templates consist of HTML and
 * *placeholders* that are defined by double curly braces, e.g. {{ page_title }}
 *
 * ### Template file: *\app\templates\default.tpl*
 * ~~~ php
 *   <html>
 *     <head>
 *       <title>{{ page_title }}</title>
 *     </head>
 *     <body>
 *       <h1>{{ heading }}</h1>
 *       <p>This page has been viewed {{ hits }} times.</p>
 *     </body>
 *   </html>
 * ~~~
 *
 * Templates are used by instantiating the Template class and passing the template URL to the
 * constructor. The bind method is used to map the placeholders to content.
 *
 * ### PHP file: *index.php*
 *
 * ~~~ php
 *   use Sleepy\Template;
 *
 *   $page = Template('templates/default.tpl');
 *   $page->bind('page_title', 'Sleepy Mustache');
 *   $page->bind('heading', 'Hello world!');
 *   $page->show(); // Display the compiled template
 * ~~~
 *
 * #### Components
 *
 * Components are design to be reusable templates. They can be attached to other templates by using
 * the *#include* directive. Good examples are *header.tpl* or *slideshow.tpl*.
 *
 * ### PHP file: *\app\templates\components\header.tpl*
 *
 * ~~~ php
 *   <html>
 *     <head>
 *       <title>{{ page_title }}</title>
 *     </head>
 *     <body>
 * ~~~
 *
 * ### PHP file: *\app\templates\components\footer.tpl*
 *
 * ~~~ php
 *     </body>
 *   </html>
 * ~~~
 *
 * ### Template file: *\app\templates\default.tpl*
 * ~~~ php
 *   {{#include components/header.tpl }}
 *       <h1>{{ heading }}</h1>
 *       <p>This page has been viewed {{ hits }} times.</p>
 *   {{#include components/footer.tpl }}
 * ~~~
 *
 * ### Binding Arrays
 *
 * Many times you need to bind an array of data to a template. For example, a slideshow or list of
 * users. In this case, we use the #each directives to loop thru the array.
 *
 * ### Template file: *\app\templates\users.tpl*
 *
 * ~~~ php
 *   {#each u in users}
 *     <div>
 *       <h3>{{ u.name }}</h3>
 *       <p>{{ u.description }}</p>
 *     </div>
 *   {\each}
 * ~~~
 *
 * ## Changelog
 *
 * ### Version 2.0a
 * * Converted to PSR-4
 *
 * ### Version 1.10.1
 * * Updated documentation
 *
 * ### Version 1.10
 * * Add rudimentary if statement blocks
 *
 * ### Version 1.9
 * * Add Action for individual Template Starts per $template name
 *
 * ### Version 1.8
 * * Allow Template::bind() to take an array to bind multiple values at once
 *
 * ### Version 1.7
 * * Updated private prefix (_) for consistency
 * * Updated documentation
 *
 * ### Version 1.6
 * * No longer dependant on Hooks Module
 *
 * @category Core
 * @package  Sleepy
 * @author   Jaime A. Rodriguez <hi.i.am.jaime@gmail.com>
 * @license  http://opensource.org/licenses/MIT; MIT
 * @link     https://sleepymustache.com
 */

namespace Sleepy\Core;

class Template
{
    /**
     * The extension for template files
     *
     * @var string The default template extension
     */
    public $extension = '.tpl';

    /**
     * The template directory
     *
     * @var string
     */
    public $directory;

    /**
     * The template file
     *
     * @var string
     */
    protected $file;

    /**
     * The data bound to the template
     *
     * @var mixed[]
     */
    protected $data = array();

    /**
     * The constructor
     *
     * @param string $template The name of the template
     * @param string $basedir  The base directory for template files
     *
     * @return void
     */
    public function __construct($template='', $basedir='')
    {
        if (class_exists('\Sleepy\Core\Hook')) {
            Hook::addAction('template_start');
            Hook::addAction('template_start' . $template);
        }

        // If they didn't pass a basedir then try the default
        if ($basedir == '') {
            if (!defined('DIRBASE')) {
                define('DIRBASE', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'app');
            }

            $this->directory = DIRBASE . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        } else {
            $this->directory = $basedir;
        }

        if (!empty($template)) {
            $this->setTemplate($template);
        }
    }

    /**
     * Does the template exist?
     *
     * @param string $file Name of template
     *
     * @return bool         True if template exists
     */
    private function _checkTemplate($file)
    {
        if (empty($file)) {
            throw new \Exception('Template file has not been set.');
        }

        // Check that the directory is set correctly
        if (!file_exists($this->directory)) {
            throw new \Exception("Template directory '{$this->directory}' does not exists.");
        }

        // Check if the template exists in the directory
        if (!file_exists($this->directory . $file . $this->extension)) {
            throw new \Exception("Template '{$this->directory}{$file}{$this->extension}' does not exist.");
        }

        return true;
    }

    /**
     * Given a path, the function returns a piece of $arr. For example
     * 'name.first' will return $arr['name']['first']
     *
     * @param array  $arr  An array to search using the $path
     * @param string $path A path representing the dimensions of the array
     *
     * @return mixed A sub-array or string
     */
    private function _assignArrayByPath($arr, $path)
    {

        $keys = explode('.', $path);

        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $arr)) {
                    $arr = $arr[$key];
                } else {
                    return false;
                }
            }
        }

        return $arr;
    }

    /**
     * Renders the template
     *
     * @param string  $template The template to render
     * @param mixed[] $data     The data bound to the template
     *
     * @return string           The rendered template
     */
    private function _render($template, $data)
    {
        $template = $this->_renderInclude($template);
        $template = $this->_renderEach($template, $data);
        $template = $this->_renderIf($template, $data);

        if (class_exists('\Sleepy\Core\Hook')) {
            $template = Hook::addFilter('prerender_template', $template);
        }

        $template = $this->_renderPlaceholder($template, $data);

        return $template;
    }

    /**
     * Render the if blocks
     *
     * @param string  $template The template string
     * @param mixed[] $data     The data
     *
     * @return void
     *
     * @todo Not very robust, remove eval at a later date
     * @todo Add Else blcok
     * @todo Add tests
     */
    private function _renderIf($template, $data)
    {
        // Process the #if blocks
        if (preg_match_all('/{{\s?#if.+?}}(?:(?>[^{}]+)|(?R))*{{\s?\/if\s?}}/ism', $template, $ifs)) {

            // For every #if
            foreach ($ifs[0] as $value) {
                // Reset rendered data
                $rendered = '';

                // break statement into 3 pieces (val1) (operator) (val2)
                preg_match('/{{\s?#if\s?(?<val1>.*?)\s(?<oper>.*?)\s(?<val2>.*?)\s?}}/', $value, $tokens);

                // Replace placeholders
                if (isset($this->data[$tokens[1]])) {
                    $tokens[1] = &$this->data[$tokens[1]];
                } else {
                    $tokens[1] = trim(trim($tokens[1], '"'), "'");
                }

                // Replace placeholders
                if (isset($this->data[$tokens[3]])) {
                    $tokens[3] = &$this->data[$tokens[3]];
                } else {
                    $tokens[3] = trim(trim($tokens[3], '"'), "'");
                }

                // Evaluate if Statement
                $truthy = eval("return \$tokens[1] $tokens[2] \$tokens[3];");

                if ($truthy) {
                    // replace with the if statements
                    $new_template = preg_replace('/{{\s?#if.*?}}/s', '', $value, 1);
                    $new_template = preg_replace('/{{\s?\/if\s?}}$/s', '', $new_template, 1);
                } else {
                    $new_template = str_replace($value, '', $value);
                }

                $rendered = $rendered . $this->_render($new_template, $data);
                $template = str_replace($value, $rendered, $template);
            }
        }

        return $template;
    }

    /**
     * Render the includes
     *
     * @param string $template The template string
     *
     * @return void
     */
    private function _renderInclude($template)
    {
        // Process the includes
        if (preg_match('/{{\s*#include\s.*}}/', $template, $include)) {
            $index = trim(str_replace('{{', '', str_replace('}}', '', $include[0])));

            if (file_exists(
                $this->directory
                . str_replace('#include ', '', $index)
                . $this->extension
            )) {
                ob_start();
                include $this->directory
                    . str_replace('#include ', '', $index)
                    . $this->extension;
            } else {
                ob_clean(); // clear buffer in $this->show();
                throw new \Exception(
                    $this->directory
                    . str_replace('#include ', '', $index)
                    . $this->extension
                    . ' doesn\'t exist. Cannot include file.'
                );
            }

            $template = $this->_renderInclude(
                str_replace($include[0], ob_get_clean(), $template)
            );
        }

        return $template;
    }

    /**
     * Render the each blocks
     *
     * @param string  $template
     * @param mixed[] $data
     *
     * @return void
     */
    private function _renderEach($template, $data)
    {
        // Process the #each blocks
        if (preg_match_all(
            '/{{\s?#each.+?}}(?:(?>[^{}]+)|(?R))*{{\s?\/each\s?}}/ism',
            $template,
            $loops
        )) {
            // For every #each
            foreach ($loops[0] as $value) {
                // Reset rendered data
                $rendered = '';

                // Stores the values of <for> and <in> into $forin
                preg_match(
                    '/{{\s?#each\s(?<for>\w+) in (?<in>.*?)\s?}}/',
                    $value,
                    $forin
                );

                $forin['in'] = strtolower($forin['in']);

                // Removes the each loop
                $new_template = preg_replace('/{{\s?#each.*?}}/s', '', $value, 1);
                $new_template = preg_replace(
                    '/{{\s?\/each\s?}}$/s',
                    '',
                    $new_template,
                    1
                );

                // get the array based on the <in>
                $in = $this->_assignArrayByPath($data, $forin['in']);

                // for each changelog
                if (is_array($in) && is_array($in[0])) {

                    // Allow hooks to edit the data
                    if (class_exists('\Sleepy\Core\Hook')) {
                        $in = Hook::addFilter('template_each_array', array($in));
                    }

                    $iterator = 0;

                    foreach ($in as $newdata) {
                        $iterator++;

                        if (class_exists('\Sleepy\Core\Hook')) {
                            $newdata = Hook::addFilter('template_each', array($newdata));
                            $newdata = Hook::addFilter('template_each_' . $forin['for'], array($newdata));
                        }

                        $newdata['iterator'] = $iterator;
                        $newdata['zebra'] = ($iterator % 2) ? 'odd' : 'even';

                        // Make the $newdata match the <for>
                        $newdata[$forin['for']] =  $newdata;

                        // render the new template
                        $rendered = $rendered . $this->_render($new_template, $newdata);
                    }
                } else {
                    // render the new template
                    $rendered = $rendered . $this->_render($new_template, $data);
                }

                $template = str_replace($value, $rendered, $template);
            }
        }

        return $template;
    }

    /**
     * Render the placeholders
     *
     * @param  string  $template
     * @param  mixed[] $data
     * @return void
     */
    private function _renderPlaceholder($template, $data)
    {
        // Find all the single placeholders
        preg_match_all('/{{\s?(.*?)(\s.*?)?\s?}}/', $template, $matches);

        // For each replace with a value
        foreach (array_unique($matches[0]) as $index => $placeholder) {
            $key = strtolower($matches[1][$index]);

            $arguments = array(
                $this->_assignArrayByPath($data, $key)
            );

            # We trim so that there are no extra blank arguments
            $arguments = array_merge($arguments, explode(' ', trim($matches[2][$index])));

            $boundData = $arguments;

            if (class_exists('\Sleepy\Core\Hook')) {
                $boundData = Hook::addFilter('render_placeholder_' . strtolower($key), $boundData);
            }

            // Some filters might take arrays and return only a single value, if
            // hooks are disabled, lets return only this single value
            if (is_array($boundData)) {
                $boundData = $boundData[0];
            }

            $template = str_replace($placeholder, $boundData, $template);
        }

        return $template;
    }

    /**
     * Parses the template after it's been setup
     *
     * @return string The rendered template
     */
    private function _parseTemplate()
    {
        $this->_checkTemplate($this->_file);

        // Render template file
        ob_start();
        include $this->directory . $this->_file . $this->extension;
        $template = $this->_render(ob_get_clean(), $this->data);

        if (class_exists('\Sleepy\Core\Hook')) {
            $template = Hook::addFilter('render_template_' . $this->_file, $template);
            $template = Hook::addFilter('render_template', $template);
        }

        return $template;
    }

    /**
     * Sets the template to use.
     *
     * @param string $file The Filename
     *
     * @return void
     */
    public function setTemplate($file)
    {
        if ($this->_checkTemplate($file)) {
            $this->_file = $file;
        }
    }

    /**
     * Binds data to the template placeholders
     *
     * @param mixed $placeholder The template placeholder
     * @param mixed $value       The value that replaced the placeholder
     *
     * @return void
     */
    public function bind($placeholder, $value='')
    {
        if (!is_array($placeholder)) {
            $placeholder = array(
                trim(strtolower($placeholder)) => $value
            );
        }

        foreach($placeholder as $key => $value) {
            $key = trim(strtolower($key));

            if (!is_array($value)) {
                if (class_exists('\Sleepy\Core\Hook')) {
                    $value = Hook::addFilter('bind_placeholder_' . trim($key), $value);
                }
            }

            $this->data[$key] = $value;
        }
    }

    /**
     * Starts a buffer that will bind data to the template placeholders. The
     * buffer will capture anything you output until $this->bindStop()
     *
     * @return void
     */
    public function bindStart()
    {
        ob_start();
    }

    /**
     * Stops the buffer that binds data to the template placeholders
     *
     * @param  string $placeholder   The template placeholder
     * @return void
     */
    public function bindStop($placeholder)
    {
        $content = ob_get_clean();

        if (class_exists('\Sleepy\Core\Hook')) {
            $content = Hook::addFilter('bind_placeholder_' . $placeholder, $content);
        }

        $this->data[trim(strtolower($placeholder))] = $content;
    }

    /**
     * Gets the data for a placeholder
     *
     * @param string $placeholder The placeholder
     *
     * @return mixed The data stored in the placeholder
     */
    public function get($key)
    {
        $value = $this->data[$key];

        if (class_exists('\Sleepy\Core\Hook')) {
            Hook::addFilter('template_get_' . $key, $value);
        }

        return $value;
    }

    /**
     * Shows the rendered template
     *
     * @return void
     */
    public function show()
    {
        echo $this->_parseTemplate();
    }

    /**
     * Retrieves the rendered template
     *
     * @return void
     */
    public function retrieve()
    {
        return $this->_parseTemplate();
    }
}