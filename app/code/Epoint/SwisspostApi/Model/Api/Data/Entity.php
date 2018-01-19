<?php

namespace Epoint\SwisspostApi\Model\Api\Data;

interface Entity{
    /**
     * Based on external object, return a local one.
     *
     * @return mixed
     */
    public function toLocal();
    public function getExternalId();
    public function toLocalByExternalId($externalId);
    public function getLocalId();
    public function isLocalSaved();
    public function connect($localId);
}
