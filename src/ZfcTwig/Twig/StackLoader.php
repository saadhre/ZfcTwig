<?php

namespace ZfcTwig\Twig;

use Twig_Error_Loader;
use Twig_Loader_Filesystem;

/**
 * Class StackLoader
 *
 * @package ZfcTwig\Twig
 */
class StackLoader extends Twig_Loader_Filesystem
{
    /**
     * Default suffix to use
     *
     * Appends this suffix if the template requested does not use it.
     *
     * @var string
     */
    protected $defaultSuffix = 'twig';

    /**
     * Set default file suffix
     *
     * @param  string $defaultSuffix
     *
     * @return StackLoader
     */
    public function setDefaultSuffix($defaultSuffix)
    {
        $this->defaultSuffix = (string)$defaultSuffix;
        $this->defaultSuffix = ltrim($this->defaultSuffix, '.');

        return $this;
    }

    /**
     * Get default file suffix
     *
     * @return string
     */
    public function getDefaultSuffix()
    {
        return $this->defaultSuffix;
    }

    /**
     * @param $name
     *
     * @return bool|string
     * @throws Twig_Error_Loader
     */
    protected function findTemplate($name)
    {
        $throw = func_num_args() > 1 ? func_get_arg(1) : true;
        $name  = (string)$name;

        // normalize name
        $name = preg_replace('#/{2,}#', '/', strtr($name, '\\', '/'));

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        if (isset($this->errorCache[$name])) {
            if ( ! $throw) {
                return false;
            }
            throw new Twig_Error_Loader($this->errorCache[$name]);
        }

        // Ensure we have the expected file extension
        $defaultSuffix = $this->getDefaultSuffix();
        if (pathinfo($name, PATHINFO_EXTENSION) != $defaultSuffix) {
            $name .= '.' . $defaultSuffix;
        }

        $this->validateName($name);

        $namespace = '__main__';
        if (isset($name[0]) && '@' == $name[0]) {
            if (false === $pos = strpos($name, '/')) {
                $this->errorCache[$name] = sprintf(
                    'Malformed namespaced template name "%s" (expecting "@namespace/template_name").',
                    $name
                );

                if ( ! $throw) {
                    return false;
                }

                throw new Twig_Error_Loader($this->errorCache[$name]);
            }

            $namespace = substr($name, 1, $pos - 1);

            $name = substr($name, $pos + 1);
        }

        if ( ! isset($this->paths[$namespace])) {
            $this->errorCache[$name] = sprintf('There are no registered paths for namespace "%s".', $namespace);

            if ( ! $throw) {
                return false;
            }
            throw new Twig_Error_Loader($this->errorCache[$name]);
        }

        foreach ($this->paths[$namespace] as $path) {
            if (is_file($path . '/' . $name)) {
                return $this->cache[$name] = $path . '/' . $name;
            }
        }

        $this->errorCache[$name] = sprintf(
            'Unable to find template "%s" (looked into: %s).',
            $name,
            implode(
                ', ',
                $this->paths[$namespace]
            )
        );

        if ( ! $throw) {
            return false;
        }

        throw new Twig_Error_Loader($this->errorCache[$name]);
    }
}
