<?php

namespace Epoint\SwisspostApi\Helper\Api\Curl;

use Epoint\SwisspostApi\Helper\Api\Result as SwissPostResult;

/**
 * Client SwissPost
 *
 */
class Result extends SwissPostResult {
  /**
   * Timeout error code.
   *
   * @const TIMEOUT_ERROR_CODE
   */
  const TIMEOUT_ERROR_CODE = 22;

  /**
   * @inheritdoc
   */
  public function isTimeout() {
    if (isset($this->debug['error_no']) && $this->debug['error_no'] && $this->debug['error_no'] == self::TIMEOUT_ERROR_CODE) {
      return TRUE;
    }
    return FALSE;
  }
}
