<?php

namespace phpBrute;

class ModuleFactory
{
    private $module = false;

    public function __construct(string $module_path)
    {
        $identifier = @pathinfo($module_path)['filename'];
        $module_class = '\\phpBrute\\Module\\' . $identifier;

        if (!is_readable($module_path)) {
            throw new \Exception("module not found");
        }

        require_once($module_path);

        if (!class_exists($module_class)) {
            throw new \Exception("module does not contain the '{$module_class}' class");
        }

        $this->module = new $module_class($identifier);

        return $this;
    }

    public function produce()
    {
        if ($this->module) {
            return clone $this->module;
        } else {
            throw new \Exception("failed to produce module");
        }
    }
}
