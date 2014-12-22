<?php

namespace Bonsai\Mapper\Converter;

use \Bonsai\Module\Vocab;

class GetVocab implements Converter
{

    public function __construct()
    {
        
    }

    public function convert($output)
    {
        return Vocab::translate($output);
    }

    public static function fetchKeys()
    {
        $raw = \Content\ContentRegistryQuery::create()->filterByContentTypeId(2)->select('description')->find()->getData();

        $keys = array();
        foreach ($raw as $key) {
            $keys[$key] = $key;
        }

        return $keys;
    }

    public static function addKeys(&$toarray)
    {
        $keys = static::fetchKeys();
        $expected = count($keys) + count($toarray);
        $toarray = array_merge($keys, $toarray);

        if (count($toarray) != $expected) {
            Cargo_Tools::localLog('warn', 'Probable key conflict while adding vocab to array, original keys unaltered.');
        }
    }

}
