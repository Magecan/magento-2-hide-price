<?php
namespace Magecan\HidePrice\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Store\Model\ScopeInterface;
 
class HidePrice
{
    protected $customerSession;
    protected $scopeConfig;
    protected $shouldHidePrice = null;

    public function __construct(
        Session $customerSession,
		ScopeConfigInterface $scopeConfig
    ) {
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
    }

	public function afterGetList(\Magento\Catalog\Model\Layer\FilterableAttributeListInterface $subject, $result)
	{
		if ($this->getShouldHidePrice() && $result instanceof \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection) {
			$items = $result->getItems();
			foreach ($items as $key => $item) {
				if ($item->getAttributeCode() == 'price') {
					unset($items[$key]);
					return $items;
				}
			}
		}

		return $result;
	}

	public function afterGetAttributesUsedForSortBy(\Magento\Catalog\Model\Config $subject, $result)
	{
		if ($this->getShouldHidePrice() && is_array($result)) {
			unset($result['price']);
		}

		return $result;
	}
	
	public function afterIsSaleable(\Magento\Catalog\Model\Product $subject, $result)
	{
		if (!$this->getShouldHidePrice()) {
			return $result;
		}

        return false;
	}

    /**
     * Modify the HTML output after the block renders
     *
     * @param \Magento\Framework\Pricing\Render\PriceBox
     * @param string $result
     * @return string
     */
    public function afterToHtml(\Magento\Framework\Pricing\Render\PriceBox $subject, $result)
    {
		if (!$this->getShouldHidePrice()) {
			return $result;
		}

        return $this->getConfigValue('hide_price/general/hide_price_text');
    }

    private function getShouldHidePrice()
    {
		if (!is_bool($this->shouldHidePrice)) {
			if (!$this->getConfigValue('hide_price/general/enabled')) {
				$this->shouldHidePrice = false;
				return $this->shouldHidePrice;
			}

			if ($this->customerSession->isLoggedIn()) {
				$customerGroupId = $this->customerSession->getCustomer()->getGroupId();
			} else {
				$customerGroupId = \Magento\Customer\Model\GroupManagement::NOT_LOGGED_IN_ID;
			}

			$customerGroups = $this->getConfigValue('hide_price/general/customer_groups');
			$customerGroups = $customerGroups ? explode(',', $customerGroups) : [];
	 
			$this->shouldHidePrice = in_array($customerGroupId, $customerGroups);			
		}
		
		return $this->shouldHidePrice;
	}

	private function getConfigValue($path, $storeId = null)
	{
		return $this->scopeConfig->getValue(
			$path,
			ScopeInterface::SCOPE_STORE,
			$storeId
		);
	}
}
