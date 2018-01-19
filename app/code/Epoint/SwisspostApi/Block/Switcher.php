<?php

namespace Epoint\SwisspostApi\Block;

class Switcher extends \Magento\Store\Block\Switcher
{
    /**
     * @inheritdoc
     */
    public function getTargetStorePostData(\Magento\Store\Model\Store $store, $data = [])
    {
        $data[\Magento\Store\Api\StoreResolverInterface::PARAM_NAME] = $store->getCode();
        $url = $this->getUrl('stores/store/switch');
        return $this->_postDataHelper->getPostData(
            $url,
            $data
        );
    }
}