<?php
/**
 * Swoole IDE helper
 * ===
 * ```shell
 * php ide_helper_dump.php
 * ```
 * Add `swoole.php` to your ide include path.
 */
define('OUTPUT_FILE', __DIR__ . '/swoole.php');

function getFuncDef(array $funcs, $version) {
    $all = '';
    foreach ($funcs as $k => $v) {
        $comment = '';
        $vp = [];
        $params = $v->getParameters();
        if ($params) {
            $comment = "/**\n";
            foreach ($params as $k1 => $v1) {
                if ($v1->isOptional()) {
                    $comment .= "* @param $" . $v1->name . "[optional]\n";
                    $vp[] = '$' . $v1->name . '=null';
                } else {
                    $comment .= "* @param $" . $v1->name . "[required]\n";
                    $vp[] = '$' . $v1->name;
                }
            }
            $comment .= "*/\n";
        }
        $comment .= sprintf("function %s(%s){}\n\n", $k, join(',', $vp));
        $all .= $comment;
    }
    return $all;
}

function getMethodsDef(array $methods, $version) {
    $all = '';
    $sp4 = str_repeat(' ', 4);
    foreach ($methods as $k => $v) {

        $comment = '';
        $vp = [];

        $params = $v->getParameters();
        if ($params) {
            $comment = "$sp4/**\n";
            foreach ($params as $k1 => $v1) {
                if ($v1->isOptional()) {
                    $comment .= "$sp4* @param $" . $v1->name . "[optional]\n";
                    $vp[] = '$' . $v1->name . '=null';
                } else {
                    $comment .= "$sp4* @param $" . $v1->name . "[required]\n";
                    $vp[] = '$' . $v1->name;
                }
            }
            $comment .= "$sp4*/\n";
        }
        $modifiers = implode(
            ' ', Reflection::getModifierNames($v->getModifiers())
        );
        $comment .= sprintf(
            "$sp4%s function %s(%s){}\n\n", $modifiers, $v->name, join(',', $vp)
        );
        $all .= $comment;
    }
    return $all;
}

function getClassDef($class, ReflectionClass $ref, $version) {
    $prop_str = '';
    $props = $ref->getProperties();
    $sp4 = str_repeat(' ', 4);
    array_walk(
        $props, function ($v, $k) {
        global $prop_str, $sp4;
        $modifiers = implode(
            ' ', Reflection::getModifierNames($v->getModifiers())
        );
        $prop_str .= "$sp4/**\n$sp4*@var $" . $v->name . " " . $v->class
            . "\n$sp4*/\n$sp4 $modifiers  $" . $v->name . ";\n\n";
    }
    );
    if ($ref->getParentClass()) {
        $class .= ' extends \\' . $ref->getParentClass()->name;
    }
    $modifier = 'class';
    if ($ref->isInterface()) {
        $modifier = 'interface';
    }
    $mdefs = getMethodsDef($ref->getMethods(), $version);
    return sprintf(
        "/**\n*@since %s\n*/\n%s %s{\n%s%s\n}\n", $version, $modifier, $class,
        $prop_str, $mdefs
    );
}

function export_ext($ext) {
    $rf_ext = new ReflectionExtension($ext);
    $funcs = $rf_ext->getFunctions();
    $classes = $rf_ext->getClasses();
    $consts = $rf_ext->getConstants();
    $version = $rf_ext->getVersion();
    //class with namespace
    $classes_with_ns = [];
    foreach (array_keys($classes) as $class) {
        if (strpos($class, '\\') !== false) {
            $arr = explode('\\', $class);
            $_class = array_pop($arr);
            $_ns = implode('\\', $arr);
            $classes_with_ns[ $_ns ][ $_class ] = $classes[ $class ];
            unset($classes[ $class ]);
        }
    }
    ob_start();
    echo "<?php\n";
    if ($classes_with_ns) {
        echo "\nnamespace {\n\n";
    }
    foreach ($consts as $k => $v) {
        if (!is_numeric($v)) {
            $v = "'$v'";
        }
        echo "define('$k',$v);\n";
    }
    $fdefs = getFuncDef($funcs, $version);
    echo $fdefs;
    foreach ($classes as $k => $v) {
        echo getClassDef($k, $v, $version);
    }
    if ($classes_with_ns) {
        echo "\n}\n";
    }
    foreach ($classes_with_ns as $ns => $classes_short) {
        echo "\nnamespace {$ns} {\n\n";
        foreach ($classes_short as $class => $ref) {
            echo getClassDef($class, $ref, $version);
        }
        echo "\n}\n";
    }
    $out = ob_get_clean();
    file_put_contents(OUTPUT_FILE, $out);
}

export_ext('swoole');
echo "swoole version: " . swoole_version() . "\n";
echo "dump success.\n";

