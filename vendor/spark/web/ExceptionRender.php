<?php
namespace spark\web;

class ExceptionRender {

    /**
     * 异常处理
     */
    public static function render($e, $extraArr = [], $forcePlain = false) {
        try {
            if ($forcePlain) {
                return self::parsePlain($e);
            } else {
                return self::parseRich($e, $extraArr);
            }
        } catch (\Throwable $ee) {
            return $ee->__toString() . "\n\n" . $e->__toString();
        }
    }

    /**
     * PHP_SAPI === 'cli' || self::isAjax()
     * @param $e
     */
    protected static function parsePlain($e) {
        return self::exceptionText($e);
    }

    /**
     * Get a single line of text representing the exception:
     * Error [ Code ]: Message ~ File [ Line ]
     * @param   object  Exception
     * @return  string
     */
    protected static function exceptionText($e) {
        return sprintf('%s [ %s ]: %s ~ %s [ %d ]', get_class($e), $e->getCode(), strip_tags($e->getMessage()),
            self::debugPath($e->getFile()), $e->getLine());
    }

    protected static function debugPath($file) {
        if (strpos($file, VENDOR_PATH) === 0) {
            $file = 'vendor' . substr($file, strlen(VENDOR_PATH));
        } elseif (strpos($file, APP_PATH) === 0) {
            $file = substr($file, strlen(APP_PATH) + 1);
        }
        return $file;
    }

    /**
     * html
     * @param $e
     */
    protected static function parseRich(\Throwable $e, $extraArr = []) {
        //header('Content-Type: text/html; charset=utf-8', true, 500);
        //header('HTTP/1.1 500 Internal Swoole Error');
        $severity_name = 'exception';
        if ($e instanceof \ErrorException) {
            list($severity_name, $exit) = self::getErrorType($e);
        } elseif ($e instanceof \Error) {
            $severity_name = 'Error';
        }
        $code = $e->getCode();
        if ($code) {
            $severity_name .= ":{$code}";
        }
        $code = $severity_name;
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $trace = $e->getTrace();
        $error_id = uniqid('error');// Unique error identifier
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= $message ?></title>
        <style type="text/css">
            #error_div {
                background: #ddd;
                font-size: 1em;
                font-family: sans-serif;
                text-align: left;
                color: #111;
            }

            #error_div h1, #error_div h2 {
                margin: 0;
                padding: 1em;
                font-size: 1em;
                font-weight: normal;
                background: #911;
                color: #fff;
            }

            #error_div h1 a, #error_div h2 a {
                color: #fff;
            }

            #error_div h2 {
                background: #222;
            }

            #error_div h3 {
                margin: 0;
                padding: 0.4em 0 0;
                font-size: 1em;
                font-weight: normal;
            }

            #error_div p {
                margin: 0;
                padding: 0.2em 0;
            }

            #error_div a {
                color: #1b323b;
            }

            #error_div pre {
                overflow: auto;
                white-space: pre-wrap;
            }

            #error_div table {
                width: 100%;
                display: block;
                margin: 0 0 0.4em;
                padding: 0;
                border-collapse: collapse;
                background: #fff;
            }

            #error_div table td {
                border: solid 1px #ddd;
                text-align: left;
                vertical-align: top;
                padding: 0.4em;
            }

            #error_div div.content {
                padding: 0.4em 1em 1em;
                overflow: hidden;
            }

            #error_div pre.source {
                margin: 0 0 1em;
                padding: 0.4em;
                background: #fff;
                border: dotted 1px #b7c680;
                line-height: 1.2em;
            }

            #error_div pre.source span.line {
                display: block;
            }

            #error_div pre.source span.highlight {
                background: #f0eb96;
            }

            #error_div pre.source span.line span.number {
                color: #666;
            }

            #error_div ol.trace {
                display: block;
                margin: 0 0 0 2em;
                padding: 0;
                list-style: decimal;
            }

            #error_div ol.trace li {
                margin: 0;
                padding: 0;
            }

            .js .collapsed {
                display: none;
            }
        </style>
        <script type="text/javascript">
            document.documentElement.className = 'js';
            function koggle(elem) {
                elem = document.getElementById(elem);
                if (elem.style && elem.style['display'])
                // Only works with the "style" attr
                    var disp = elem.style['display'];
                else if (elem.currentStyle)
                // For MSIE, naturally
                    var disp = elem.currentStyle['display'];
                else if (window.getComputedStyle)
                // For most other browsers
                    var disp = document.defaultView.getComputedStyle(elem, null).getPropertyValue('display');
                // Toggle the state of the "display" style
                elem.style.display = disp == 'block' ? 'none' : 'block';
                return false;
            }
        </script>
        <div id="error_div">
            <h1><span class="type"><?= get_class($e) ?> [ <?= $code ?> ]:</span>
                <span class="message"><?= $message ?></span></h1>
            <div id="<?= $error_id ?>" class="content">
                <p><span class="file">
<?= self::debugPath($e->getFile()) ?>[<?= $e->getLine() ?>]
</span></p>
                <?= self::debugSource($e->getFile(), $e->getLine()) ?>
                <ol class="trace">
                    <?php foreach (self::trace($trace) as $i => $step): ?>
                        <li>
                            <p>

<span class="file">
<?php
if ($step['file']):
    $source_id = "{$error_id}source{$i}";
    ?>
    <a href="#<?= $source_id ?>"
       onclick="return koggle('<?= $source_id ?>')">
		<?= self::debugPath($step['file']) ?> [ <?= $step['line'] ?> ]</a>
<?php else: ?>
    {PHP internal call}
<?php endif; ?>
</span>&raquo;

                                <?= $step['function'] ?>(
                                <?php
                                if ($step['args']):
                                    $args_id = $error_id . 'args' . $i;
                                    ?>
                                    <a href="#<?php echo $args_id ?>"
                                       onclick="return koggle('<?php echo $args_id ?>')"><?= 'arguments' ?></a>
                                <?php endif ?>)
                            </p>
                            <?php if (isset($args_id)): ?>
                                <div id="<?php echo $args_id ?>" class="collapsed">
                                    <table cellspacing="0">
                                        <?php foreach ($step['args'] as $name => $arg): ?>
                                            <tr>
                                                <td><code><?php echo $name ?></code></td>
                                                <td>
                                                    <pre><?php echo self::dump($arg) ?></pre>
                                                </td>
                                            </tr>
                                        <?php endforeach ?>
                                    </table>
                                </div>
                            <?php endif ?>
                            <?php
                            if (isset($source_id)):
                                ?>
                                <pre id="<?php echo $source_id ?>"
                                     class="source collapsed"><code><?php echo $step['source'] ?></code></pre>
                            <?php endif ?>
                        </li>
                        <?php
                        unset($args_id, $source_id);
                        ?>
                        <?php
                    endforeach
                    ?>
                </ol>
            </div>
            <h2><a href="#<?php echo $env_id = $error_id . 'environment' ?>"
                   onclick="return koggle('<?php echo $env_id ?>')"><?php echo 'Environment' ?></a></h2>
            <div id="<?php echo $env_id ?>" class="content collapsed">
                <?php $included = get_included_files() ?>
                <h3><a href="#<?php echo $env_id = $error_id . 'environment_included' ?>"
                       onclick="return koggle('<?php echo $env_id ?>')"><?php echo 'Included files'; ?></a>
                    (<?php echo count($included) ?>)</h3>
                <div id="<?php echo $env_id ?>" class="collapsed">
                    <table cellspacing="0">
                        <?php foreach ($included as $file): ?>
                            <tr>
                                <td><code><?php echo self::debugPath($file) ?></code></td>
                            </tr>
                        <?php endforeach ?>
                    </table>
                </div>
                <?php $included = get_loaded_extensions() ?>
                <h3><a href="#<?php echo $env_id = $error_id . 'environment_loaded' ?>"
                       onclick="return koggle('<?php
                       echo $env_id ?>')"><?php
                        echo 'Loaded extensions' ?></a> (<?php
                    echo count($included) ?>)</h3>
                <div id="<?php echo $env_id ?>" class="collapsed">
                    <table cellspacing="0">
                        <?php foreach ($included as $file): ?>
                            <tr>
                                <td><code><?= self::debugPath($file) ?></code></td>
                            </tr>
                        <?php endforeach ?>
                    </table>
                </div>

                <?php
                foreach (['_SESSION', '_GET', '_POST', '_FILES', '_COOKIE', '_SERVER'] as $var):
                    if (empty($GLOBALS[ $var ]) or !is_array($GLOBALS[ $var ])) {
                        continue;
                    }
                    ?>
                    <h3><a href="#<?= $env_id = $error_id . 'environment' . strtolower($var) ?>"
                           onclick="return koggle('<?= $env_id ?>')">$<?= $var ?></a></h3>
                    <div id="<?php echo $env_id ?>" class="collapsed">
                        <table cellspacing="0">
                            <?php foreach ($GLOBALS[ $var ] as $key => $value): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td>
                                        <pre><?= self::dump($value) ?></pre>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </table>
                    </div>
                    <?php
                endforeach; ?>
                <?php foreach ($extraArr as $var => $objs): ?>

                    <h3><a href="#<?= $env_id = $error_id . '' . strtolower($var) ?>"
                           onclick="return koggle('<?= $env_id ?>')">$<?= $var ?></a></h3>
                    <div id="<?php echo $env_id ?>" class="collapsed">
                        <table cellspacing="0">
                            <?php foreach ($objs as $key => $value): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td>
                                        <pre><?= self::dump($value) ?></pre>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    protected static function getErrorType(\ErrorException $e) {
        $exit = false;
        $severity = $e->getSeverity();
        switch ($severity) {
            case E_ERROR:
                $type = 'Fatal Error';
                $exit = true;
                break;
            case E_USER_ERROR:
                $type = 'User Error';
                $exit = true;
                break;
            case E_PARSE:
                $type = 'Parse Error';
                $exit = true;
                break;
            case E_WARNING:
                $type = 'Warning';
                break;
            case E_USER_WARNING:
                $type = 'User Warning';
                break;
            case E_USER_NOTICE:
                $type = 'User Notice';
                break;
            case E_NOTICE:
                $type = 'Notice';
                break;
            case E_STRICT:
                $type = 'Notice';
                break;
            case E_RECOVERABLE_ERROR:
                $type = 'Recoverable Error';
                break;
            default:
                $type = 'Unknown Error';
                $exit = true;
        }
        return [$type, $exit];
    }

    /**
     * Returns an HTML string, highlighting a specific line of a file, with some number of lines padded above and below.
     * Highlights the current line of the current file.
     * @param string $file
     * @param int    $line_number
     * @param int    $padding
     * @return bool|string  FALSE: file is unreadable, String: source of file
     */
    protected static function debugSource($file, $line_number, $padding = 5) {
        if (!$file or !is_readable($file)) {
            return false;
        }
        $file = fopen($file, 'r');
        $line = 0;
        $range = [
            'start' => $line_number - $padding,
            'end'   => $line_number + $padding,
        ];
        $format = '% ' . strlen($range['end']) . 'd';
        $source = '';
        while (($row = fgets($file)) !== false) {
            if (++$line > $range['end']) {
                break;
            }
            if ($line >= $range['start']) {
                // Make the row safe for output
                $row = htmlspecialchars($row, ENT_NOQUOTES, 'UTF-8');
                // Trim whitespace and sanitize the row
                $row = '<span class="number">' . sprintf($format, $line) . '</span> ' . $row;
                if ($line === $line_number) {
                    // Apply highlighting to this row
                    $row = '<span class="line highlight">' . $row . '</span>';
                } else {
                    $row = '<span class="line">' . $row . '</span>';
                }
                // Add to the captured source
                $source .= $row;
            }
        }
        // Close the file
        fclose($file);
        return '<pre class="source"><code>' . $source . '</code></pre>';
    }

    /**
     * Returns an array of HTML strings that represent each step in the backtrace.
     * // Displays the entire current backtrace
     * @param   string  path to debug
     * @return  string
     */
    static function trace(array $trace = null) {
        if ($trace === null) {
            // Start a new trace
            $trace = debug_backtrace();
        }
        // Non-standard function calls
        $statements = [
            'include',
            'include_once',
            'require',
            'require_once',
        ];
        $output = [];
        foreach ($trace as $step) {
            if (!isset($step['function'])) {
                // Invalid trace step
                continue;
            }
            if (isset($step['file']) and isset($step['line'])) {
                // Include the source of this step
                $source = self::debugSource($step['file'], $step['line']);
            }
            if (isset($step['file'])) {
                $file = $step['file'];
                if (isset($step['line'])) {
                    $line = $step['line'];
                }
            }
            // function()
            $function = $step['function'];
            if (in_array($step['function'], $statements)) {
                if (empty($step['args'])) {
                    // No arguments
                    $args = [];
                } else {
                    // Sanitize the file path
                    $args = [
                        $step['args'][0],
                    ];
                }
            } elseif (isset($step['args'])) {
                if (!function_exists($step['function']) or strpos($step['function'], '{closure}') !== false) {
                    // Introspection on closures or language constructs in a stack trace is impossible
                    $params = null;
                } else {
                    if (isset($step['class'])) {
                        if (method_exists($step['class'], $step['function'])) {
                            $reflection = new \ReflectionMethod($step['class'], $step['function']);
                        } else {
                            $reflection = new \ReflectionMethod($step['class'], '__call');
                        }
                    } else {
                        $reflection = new \ReflectionFunction($step['function']);
                    }
                    // Get the function parameters
                    $params = $reflection->getParameters();
                }
                $args = [];
                foreach ($step['args'] as $i => $arg) {
                    if (isset($params[ $i ])) {
                        // Assign the argument by the parameter name
                        $args[ $params[ $i ]->name ] = $arg;
                    } else {
                        // Assign the argument by number
                        $args[ $i ] = $arg;
                    }
                }
            }
            if (isset($step['class'])) {
                // Class->method() or Class::method()
                $function = $step['class'] . $step['type'] . $step['function'];
            }
            $output[] = [
                'function' => $function,
                'args'     => isset($args) ? $args : null,
                'file'     => isset($file) ? $file : null,
                'line'     => isset($line) ? $line : null,
                'source'   => isset($source) ? $source : null,
            ];
            unset($function, $args, $file, $line, $source);
        }
        return $output;
    }

    /**
     * Quick debugging of any variable. Any number of parameters can be set.
     * @return  string
     */
    static function dump() {
        if (func_num_args() === 0) {
            return;
        }
        $params = func_get_args(); // Get params
        $output = [];
        foreach ($params as $var) {
            $output[] = '<pre>(' . gettype($var) . ') ' . htmlspecialchars(print_r($var, true)) . '</pre>';
        }
        return implode("\n", $output);
    }
}
