<?php
declare(strict_types=1);

namespace YuriiZh\Newsletter\Setup\SetupService;

use Exception;
use Magento\Backend\App\Area\FrontNameResolver as BackendFrontNameResolver;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Framework\App\State as AppState;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\Data\RuleInterfaceFactory;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use YuriiZh\Newsletter\Service\GenerateCouponCodesService;

/**
 * Class CreateCartPriceRuleService
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class CreateCartPriceRuleService
{
    /**
     * Rule Repository
     *
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * Store Manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Catalog Price Rule Factory
     *
     * @var RuleInterfaceFactory
     */
    protected $cartPriceRuleFactory;

    /**
     * Customer Group Collection Factory
     *
     * @var CustomerGroupCollectionFactory
     */
    protected $customerGroupCollectionFactory;

    /**
     * App State
     *
     * @var AppState
     */
    protected $appState;

    /**
     * CreateCartPriceRuleService constructor
     *
     * @param RuleRepositoryInterface $ruleRepository
     * @param StoreManagerInterface $storeManager
     * @param AppState $appState
     * @param RuleInterfaceFactory $cartPriceRuleFactory
     * @param CustomerGroupCollectionFactory $customerGroupCollectionFactory
     */
    public function __construct(
        RuleRepositoryInterface $ruleRepository,
        StoreManagerInterface $storeManager,
        AppState $appState,
        RuleInterfaceFactory $cartPriceRuleFactory,
        CustomerGroupCollectionFactory $customerGroupCollectionFactory
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->storeManager = $storeManager;
        $this->appState = $appState;
        $this->cartPriceRuleFactory = $cartPriceRuleFactory;
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
    }

    /**
     * Create cart price rule and generate coupon codes
     *
     * @return void
     *
     * @throws Exception
     */
    public function execute(): void
    {
        $customerGroupIds = $this->getAvailableCustomerGroupIds();
        $websiteIds = $this->getAvailableWebsiteIds();
        $cartPriceRule = $this->cartPriceRuleFactory->create();
        $cartPriceRule->setName(GenerateCouponCodesService::CART_PRICE_RULE_NAME);
        $cartPriceRule->setIsActive(true);
        $cartPriceRule->setCouponType(RuleInterface::COUPON_TYPE_SPECIFIC_COUPON);
        $cartPriceRule->setCustomerGroupIds($customerGroupIds);
        $cartPriceRule->setWebsiteIds($websiteIds);
        $cartPriceRule->setUsesPerCustomer(1);
        $cartPriceRule->setUseAutoGeneration(true);
        $this->appState->emulateAreaCode(
            BackendFrontNameResolver::AREA_CODE,
            [$this->ruleRepository, 'save'],
            [$cartPriceRule]
        );
    }

    /**
     * @return int[]
     */
    protected function getAvailableCustomerGroupIds(): array
    {
        $collection = $this->customerGroupCollectionFactory->create();
        $collection->addFieldToSelect('customer_group_id');
        return $collection->getAllIds();
    }

    /**
     * @return int[]
     */
    protected function getAvailableWebsiteIds(): array
    {
        $websiteIds = [];
        $websites = $this->storeManager->getWebsites();
        foreach ($websites as $website) {
            $websiteIds[] = $website->getId();
        }

        return $websiteIds;
    }
}
