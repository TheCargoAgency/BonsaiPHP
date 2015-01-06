<?php

namespace Bonsai\Render\PreProcess;

class TextToSlug extends PreProcessBase
{

    public function preProcess()
    {
        $patterns = array(
            '/<.+?>.+?<.+?>/',
            '/\d+/',
            '/(\w+\-)+\w+/',
            '/[^\w\s\-]/',
        );

        $replace = array(
            '',
            '',
            '',
            '',
        );

        $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $this->input);

        $slug = preg_replace($patterns, $replace, $slug);
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/\s+/', '-', $slug);

        return $slug;
    }

}
