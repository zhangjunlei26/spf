#!/usr/bin/env php
<?php
$parser = new PBParser();
$parser->parse($argv[1]);

/**
 * Parse a .proto file and generates the classes in a file
 * @author zhangjunlei zhangjunlei26@gmail.com
 */
class PBParser {

    const PB_TYPE_DOUBLE = 1;
    const PB_TYPE_FIXED32 = 2;
    const PB_TYPE_FIXED64 = 3;
    const PB_TYPE_FLOAT = 4;
    const PB_TYPE_INT = 5;
    const PB_TYPE_SIGNED_INT = 6;
    const PB_TYPE_STRING = 7;
    const PB_TYPE_BOOL = 8;
    protected static $pb_types_def = [
        self::PB_TYPE_DOUBLE     => 'PB_TYPE_DOUBLE',
        self::PB_TYPE_FLOAT      => 'PB_TYPE_FLOAT',
        self::PB_TYPE_INT        => 'PB_TYPE_INTt',
        self::PB_TYPE_SIGNED_INT => 'PB_TYPE_SIGNED_INTt',
        self::PB_TYPE_FIXED32    => 'PB_TYPE_FIXED32',
        self::PB_TYPE_FIXED64    => 'PB_TYPE_FIXED64',
        self::PB_TYPE_BOOL       => 'PB_TYPE_BOOL',
        self::PB_TYPE_STRING     => 'PB_TYPE_STRING',
    ];
    protected static $scalar_types = [
        'double'   => self::PB_TYPE_DOUBLE,
        'float'    => self::PB_TYPE_FLOAT,
        'int32'    => self::PB_TYPE_INT,
        'int64'    => self::PB_TYPE_INT,
        'uint32'   => self::PB_TYPE_INT,
        'uint64'   => self::PB_TYPE_INT,
        'sint32'   => self::PB_TYPE_SIGNED_INT,
        'sint64'   => self::PB_TYPE_SIGNED_INT,
        'fixed32'  => self::PB_TYPE_FIXED32,
        'fixed64'  => self::PB_TYPE_FIXED64,
        'sfixed32' => self::PB_TYPE_FIXED32,
        'sfixed64' => self::PB_TYPE_FIXED64,
        'bool'     => self::PB_TYPE_BOOL,
        'string'   => self::PB_TYPE_STRING,
        'bytes'    => self::PB_TYPE_STRING,
    ];

    protected $packages = '';
    // the message types array of (field, param[]='repeated,required,optional'd
    // the message types array of (field, param[]='repeated,required,optional'
    protected $m_types = [];
    protected $m_index = [];

    /**
     * parses the profile and generates a filename with the name
     * pb_proto_[NAME]
     * @param String $protofile - the protofilename with the path
     */
    public function parse($protofile) {
        error_reporting(E_ALL);
        if (!is_file($protofile)) {
            exit("[File not exists:] {$protofile}.\n");
        }
        $string = file_get_contents($protofile);
        $this->strip_comments($string);
        $string = trim($string);
        $this->parse_message_type($string, '');
        $this->after_parse();
        // now create file with classes
        $fileInfo = pathinfo($protofile);
        $this->create_class_file('pb_proto_' . $fileInfo['filename'] . '.php');
    }

    /**
     * Strips the comments out
     * @param String $string the proton file as string
     */
    private function strip_comments(&$string) {
        $string = preg_replace('/\/\/.*$/', '', $string);
        // now replace empty lines and whitespaces in front
        $string = preg_replace('/\\r?\\n\s*/', "\n", $string);
    }

    /**
     * Parses the message
     * @param String $string the proton file as string
     */
    private function parse_message_type(&$string, $m_name, $path = '') {
        // now adding myarray to array
        $myarray = [];
        list($classname, $namespace) = $this->calc_class_name($path);
        while (strlen($string) > 0) {
            $next = ($this->next($string));
            if (strncasecmp($next, 'package', 7) === 0) {
                $string = trim(substr($string, 7));
                $name = $this->next($string);
                $string = trim(substr($string, strlen($name)));
                $this->packages = trim($name, '; ');
            } elseif (strncasecmp($next, 'message', 7) === 0) {
                $string = trim(substr($string, 7));
                $name = $this->next($string);
                $offset = $this->get_begin_end($string, "{", "}");
                //now extract the content and call parse_message again
                $content = trim(substr($string, $offset['begin'] + 1, $offset['end'] - $offset['begin'] - 2));
                $this->parse_message_type($content, $name, ($path ? $path . '.' . $name : $name));
                $string = '' . trim(substr($string, $offset['end']));
            } else {
                if (strncasecmp($next, 'enum', 4) === 0) {
                    $string = trim(substr($string, 4));
                    $name = $this->next($string);
                    $offset = $this->get_begin_end($string, "{", "}");
                    //now extract the content and call parse_message again
                    $content = trim(substr($string, $offset['begin'] + 1, $offset['end'] - $offset['begin'] - 2));
                    //now adding all to myarray
                    $fullname = ($path ? $path . '.' . $name : $name);
                    list($_classname, $_namespace) = $this->calc_class_name($fullname);
                    $this->m_types[] = [
                        'name'      => $fullname,
                        'type'      => 'enum',
                        'class'     => $_classname,
                        'namespace' => $_namespace,
                        'value'     => $this->parse_enum($content),
                    ];
                    //removing it from string
                    $string = '' . trim(substr($string, $offset['end']));
                } else {
                    //now a normal field
                    $match = preg_match('/(.*);\s?/', $string, $matches, PREG_OFFSET_CAPTURE);
                    if (!$match) {
                        throw new \Exception('Proto file missformed');
                    }
                    $myarray[] = ['type' => 'field', 'value' => $this->parse_field($matches[0][0], $myarray, $path)];
                    $string = trim(substr($string, $matches[0][1] + strlen($matches[0][0])));
                }
            }
        }
        $this->m_types[] = [
            'name'      => $path,
            'type'      => 'message',
            'class'     => $classname,
            'namespace' => $namespace,
            'value'     => $myarray,
        ];
    }

    protected function calc_class_name($classname) {
        $packages = $this->packages;
        //如果是子类,classneme = a.b.c,则将packages + a.b归入命名空间
        if (strpos($classname, '.') !== false) {
            $tmp = strrchr($classname, '.');//类名a.b.c则取.c
            $parent_node = substr($classname, 0, -strlen($tmp));//取a.b
            if ($packages) {
                $packages .= '.' . $parent_node;
            }
            $classname = trim($tmp, '.');
        }

        if ($packages) {
            $namespace = strtr($packages, '.', '\\');
        } else {
            $namespace = '';
        }
        return [$classname, $namespace];
    }

    /**
     * Gets the next String
     */
    private function next($string, $reg = false) {
        $match = preg_match('/([^\s^\{}]*)/', $string, $matches, PREG_OFFSET_CAPTURE);
        if (!$match) {
            return -1;
        }
        if (!$reg) {
            return (trim($matches[0][0]));
        } else {
            return $matches;
        }
    }

    /**
     * Returns the begin and endpos of the char
     * @param String $string  protofile as string
     * @param String $char    begin element such as '{'
     * @param String $charend end element such as '}'
     * @return array begin, end
     */
    private function get_begin_end($string, $char, $charend) {
        $offset_begin = strpos($string, $char);

        if ($offset_begin === false) {
            return ['begin' => -1, 'end' => -1];
        }

        $_offset_number = 1;
        $_offset = $offset_begin + 1;
        while ($_offset_number > 0 && $_offset > 0) {
            // now search after the end nested { }
            $offset_open = strpos($string, $char, $_offset);
            $offset_close = strpos($string, $charend, $_offset);
            if ($offset_open < $offset_close && !($offset_open === false)) {
                $_offset = $offset_open + 1;
                $_offset_number++;
            } else {
                if (!($offset_close === false)) {
                    $_offset = $offset_close + 1;
                    $_offset_number--;
                } else {
                    $_offset = -1;
                }
            }
        }

        if ($_offset == -1) {
            throw new \Exception('Protofile failure: ' . $char . ' not nested');
        }

        return ['begin' => $offset_begin, 'end' => $_offset];
    }

    /**
     * Parses enum
     * @param String $content content of the enum
     */
    private function parse_enum($content) {
        $myarray = [];
        $match = preg_match_all('/(.*);\s?/', $content, $matches);
        if (!$match) {
            throw new Execption('Semantic error in Enum!');
        }
        foreach ($matches[1] as $match) {
            $arr = explode("=", $match);
            $myarray[] = [trim($arr[0]), trim($arr[1])];
        }
        return $myarray;
    }

    /**
     * Parses a normal field
     * @param String $content - content
     */
    private function parse_field($content, $array, $path) {
        $myarray = [];

        // parse the default value
        //TODO::packed处理
        $match = preg_match('/\[\s?(default|packed)\s?=\s?([^\[]*)\]\s?;/', $content, $matches, PREG_OFFSET_CAPTURE);
        if ($match) {
            $myarray[ $matches[1][0] ] = $matches[2][0];
            $content = trim(substr($content, 0, $matches[0][1])) . ';';
        }

        // parse the value
        $match = preg_match('/=\s(.*);/', $content, $matches, PREG_OFFSET_CAPTURE);
        if ($match) {
            $myarray['value'] = trim($matches[1][0]);
            $content = trim(substr($content, 0, $matches[0][1]));
        } else {
            throw new \Exception('Protofile no value at ' . $content);
        }

        // parse all modifier
        $content = trim($content, '; ');
        $typeset = false;
        while (strlen($content) > 0) {
            $matches = $this->next($content, true);
            $name = $matches[0][0];
            if (strtolower($name) == 'optional') {
                $myarray['optional'] = true;
            } else {
                if (strtolower($name) == 'required') {
                    $myarray['required'] = true;
                } else {
                    if (strtolower($name) == 'repeated') {
                        $myarray['repeated'] = true;
                    } else {
                        if ($typeset == false) {
                            $myarray['type'] = $name;
                            if (isset(self::$scalar_types[ $name ])) {
                                $myarray['path'] = self::$scalar_types[ $name ];
                            } else {
                                $myarray['path'] = $this->check_type($name, $path);
                            }
                            $typeset = true;
                        } else {
                            $myarray['name'] = $name;
                        }
                    }
                }
            }
            $content = trim(substr($content, strlen($name)));
        }
        return $myarray;
    }

    /**
     * Checks if a type exists
     * @param String $type - the type
     */
    private function check_type($type, $path) {
        if (isset(self::$scalar_types[ $type ])) {
            return '';
        }
        // 绝对或相对路径,计算namespace
        $apath = explode('.', $path);
        //同级message
        if (count($apath) > 1) {
            array_pop($apath);
            $namespace = implode('.', $apath) . '.' . $type;
        } else {
            $namespace = $type;
        }

        // try the namespace
        foreach ($this->m_types as $message) {
            if ($message['name'] === $namespace) {
                return $namespace;
            }
        }
        //再查找当前级之下
        $namespace = trim($path . '.' . $namespace, '.');
        foreach ($this->m_types as $message) {
            if ($message['name'] === $namespace) {
                return $namespace;
            }
        }
        //认为是其它包引入的,注意:可能会有类型不存在的错误,请在定义文件上避免
        //if (strpos($type, '.') > 0) return [$type, ''];
        // @TODO TYPE CHECK
        throw new \Exception('Protofile type ' . $type . ' unknown!');
    }

    protected function after_parse() {
        $m_types = &$this->m_types;
        array_pop($m_types);
        foreach ($m_types as $i => $obj) {
            $this->m_index[ $obj['name'] ] = $i;
            if ($obj['type'] === 'enum') {
                $m_types[ $i ]['is_enum'] = true;
                continue;
            }
        }
        foreach ($m_types as &$class_obj) {
            if (isset($class_obj['is_enum'])) {
                continue;
            }
            $class_name = $class_obj['name'];
            $fields = &$class_obj['value'];
            foreach ($fields as &$field) {
                $val = &$field['value'];
                $path = $val['path'];
                if (!is_int($path)) {//用户定义类型
                    $filed_index = $this->m_index[ $path ];
                    $ref_to = $this->m_types[ $filed_index ];
                    $abbr = $this->calc_abbr($class_name, $path);
                    $val['class'] = '\\' . $ref_to['namespace'] . '\\' . $ref_to['class'];
                    if ($abbr) {
                        $val['abbr'] = $abbr;
                    }
                    if (isset($ref_to['is_enum'])) {
                        $val['is_enum'] = true;
                        if (isset($val['default'])) {
                            if ($abbr) {
                                $val['default'] = $abbr . '::' . $val['default'];
                            } else {
                                $val['default'] = $val['class'] . '::' . $val['default'];
                            }
                        }
                    }
                }
                unset($field, $val);
            }
            unset($class_obj);
        }
    }

    protected function calc_abbr($class_name, $path) {
        $pos = strrpos($class_name, '.');
        $prefix = ($pos !== false) ? substr($class_name, 0, $pos + 1) : $class_name;
        $len = strlen($prefix);
        if (strncasecmp($prefix, $path, $len) === 0) {
            return strtr(substr($path, $len), '.', '\\');
        }
        return null;
    }

    /**
     * Creates php class file for the proto file
     * @param String $filename - the filename of the php file
     */
    private function create_class_file($filename) {
        foreach ($this->m_types as $classfile) {
            $string = "<?php\n";
            $namespace = $classfile['namespace'];
            if ($namespace) {
                $string .= 'namespace ' . $namespace . ";\n\n";
            }
            $classname = $classfile['class'];
            if (isset($classfile['is_enum'])) {
                $string .= 'class ' . $classname . " {\n\n";
                $this->create_enum_definition($classfile['value'], $string);
                $string .= "\n}\n";
            } elseif ($classfile['type'] === 'message') {
                $string .= "class {$classname} extends \\ProtobufMessage {\n";
                $this->create_class_constructor($classfile['value'], $string, $namespace);
                $this->create_class_body($classfile['value'], $string, $classname);
                $string .= "\n}\n";
            }
            $dir = getcwd() . '/' . strtr($namespace, '\\', '/');
            if (!is_dir($dir)) {
                if(!@mkdir($dir, 0777, true) && !is_dir($dir)){
                    throw new \Exception("make dir '{$dir}' failed!");
                }
            }
            file_put_contents("{$dir}/{$classname}.php", $string);
            unset($string);
        }
    }

    /**
     * Creates the class definitions
     * @param Array  $classfile
     * @param String $string
     */
    private function create_enum_definition($classfile, &$string) {
        foreach ($classfile as $field) {
            $string .= '    const ' . $field['0'] . ' = ' . $field['1'] . ";\n";
        }
    }

    private function create_class_constructor($classfile, &$string, $namespace) {
        $string .= "\n    protected static \$fields = [\n";
        foreach ($classfile as $field) {
            $def = $field['value'];
            $required = !empty($def['required']) ? 'true' : 'false';
            $name = addslashes($def['name']);
            $string .= "        {$def['value']} => [\n";
            $string .= "            'name'=> '{$name}',\n";
            $string .= "            'required'=> {$required},\n";
            $path = $def['path'];
            if (is_int($path)) {
                $string .= "            'type'=> \\ProtobufMessage::" . self::$pb_types_def[ $path ] . ",\n";
            } elseif (isset($def['is_enum'])) {
                $string .= "            'type'=> \\ProtobufMessage::PB_TYPE_INT,\n";
            } else {
                $string .= "            'type'=> '" . $def['class'] . "',\n";
            }
            if (isset($def['default'])) {
                $string .= "            'default'=>{$def['default']},\n";
            }
            if (isset($def['repeated'])) {
                $string .= "            'repeated'=> true,\n";
            }
            if (isset($def['packed'])) {
                $string .= "            'packed'=> true\n";
            }
            $string .= "        ],\n";
            //packed
        }
        $string .= "    ];\n";
        //$string .= "\n    public function __construct(\$reader = null) {\n";
        //$string .= "        parent::__construct(\$reader);\n";
        $string .= "\n    public function __construct() {\n";
        $string .= "        \$this->reset();\n    }\n";
        $string .= "\n    public function reset() {\n";

        foreach ($classfile as $field) {
            $def = $field['value'];
            $number = $def['value'];
            // default value only for optional fields
            if (isset($def['repeated'])) {
                $string .= '        $this->values["' . $number . '"] = []' . ";\n";
            } elseif (isset($def['default'])) {
                $string .= '        $this->values["' . $number . '"] = ' . $def['default'] . ";\n";
            } else {
                $string .= '        $this->values["' . $number . '"] = null' . ";\n";
            }
        }
        $string .= "    }\n\n";
    }

    /**
     * Creates the class body with functions for each field
     * @param Array  $classfile
     * @param String $string
     * @param String $classname - classname
     */
    private function create_class_body($classfile, &$string, $classname) {
        foreach ($classfile as $field) {
            $def = $field['value'];
            $is_enum = isset($def['is_enum']);
            $method_name = $def['name'];
            //下划线变驼峰
            if (strpos($method_name, '_') !== false) {
                $arr = explode('_', $method_name);
                $method_name = '';
                foreach ($arr as $v) {
                    $v[0] = strtoupper($v[0]);
                    $method_name .= $v;
                }
            } else {
                $method_name = ucfirst($method_name);
            }
            $index = $def['value'];
            if (isset($def['repeated'])) {
                $string .= <<<HTML
    public function append{$method_name}(\$value) {
        return \$this->append({$index}, \$value);
    }
    
    public function clear{$method_name}() {
        return \$this->clear({$index});
    }

    public function get{$method_name}() {
        return \$this->get({$index});
    }

    public function get{$method_name}Iterator() {
        return new \ArrayIterator(\$this->get({$index}));
    }

    public function get{$method_name}At(\$offset) {
        return \$this->get({$index}, \$offset);
    }

    public function get{$method_name}Count() {
        return \$this->count({$index});
    }


HTML;
            } else {
                $string .= <<<HTML
    public function set{$method_name}(\$value) {
        return \$this->set({$index}, \$value);
    }
    
    public function get{$method_name}() {
        return \$this->get({$index});
    }


HTML;
            }
        }
    }

}