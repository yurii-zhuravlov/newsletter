<?php
declare(strict_types=1);

namespace YuriiZh\Newsletter\Setup;

use YuriiZh\Newsletter\Setup\SetupService\CreateCartPriceRuleService;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Exception;

/**
 * Class InstallData
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var CreateCartPriceRuleService
     */
    protected $createCartPriceRuleService;

    /**
     * InstallData constructor.
     * @param CreateCartPriceRuleService $createCartPriceRuleService
     */
    public function __construct(CreateCartPriceRuleService $createCartPriceRuleService)
    {
        $this->createCartPriceRuleService = $createCartPriceRuleService;
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->createCartPriceRuleService->execute();
    }
}
