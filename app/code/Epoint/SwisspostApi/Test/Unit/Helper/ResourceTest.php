<?php

namespace Epoint\SwisspostApi\Test\Unit\Helper;

use Epoint\SwisspostApi\Helper\Resource;

class ResourceTest extends \PHPUnit_Framework_TestCase
{

  /**
   * The helper object
   * @var
   */
  protected $dataResource;

  /**
   * Implement setUp
   */
  public function setUp(){
    $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    $this->dataResource =  $objectManager->getObject('Epoint\SwisspostApi\Helper\Resource');
  }

  function testSessionAuthenticate(){
    return $this->assertEquals( $this->dataResource->sessionAuthenticate()->get('session_id') ? true : false, true);
  }
}
