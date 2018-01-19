<?php

namespace Epoint\SwisspostApi\Test\Unit\Helper;

use Epoint\SwisspostApi\Helper\Data;

class DataTest extends \PHPUnit_Framework_TestCase
{

  /**
   * The helper object
   * @var
   */
  protected $dataHelper;

  /**
   * Implement setUp
   */
  public function setUp(){
    $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    $this->dataHelper = $objectManager->getObject('Epoint\SwisspostApi\Helper\Data');
  }

  function testApiConfigured(){
     return $this->assertEquals($this->dataHelper->getGeneralConfig('connection/jsonrpc_version') != '', true);
  }
}
