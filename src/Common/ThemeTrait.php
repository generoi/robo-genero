<?php

namespace Generoi\Robo\Common;

use Robo\Robo;

trait ThemeTrait
{
    /**
     * Get the path of the theme
     *
     * @return string
     */
    protected function getThemePath()
    {
        return Robo::config()->get('theme_path');
    }
}
