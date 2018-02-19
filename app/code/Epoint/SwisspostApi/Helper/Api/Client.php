<?php

namespace Epoint\SwisspostApi\Helper\Api;

Use  Epoint\SwisspostApi\Helper\Api\Result as SwissPostResult;

/**
 * Client SwissPost
 *
 */
abstract class Client
{

    protected $debug = [];

    protected $results = [];

    /**
     * Call the service
     *
     * @param       $method_url
     * @param array $data
     *
     * @return SwissPostResult
     */
    abstract public function call($method_url, $data = []);

    /**
     * @return mixed
     */
    abstract public function connect();

    /**
     * @return SwissPostResult
     */
    abstract public function getLastResult();

}
