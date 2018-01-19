<?php

namespace Epoint\SwisspostApi\Model\Api\Data;

interface Translatable{

    /**
     * Translate Api response item
     * @param string $property
     *   The translatable property.
     * @param string $code
     *   The language 2 letter iso code.
     * @return string
     */
    public function translate($property, $code = '');
}
