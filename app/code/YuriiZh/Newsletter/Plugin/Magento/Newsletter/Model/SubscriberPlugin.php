<?php
declare(strict_types=1);

namespace YuriiZh\Newsletter\Plugin\Magento\Newsletter\Model;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\ResourceModel\Subscriber as SubscriberResourceModel;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use YuriiZh\Newsletter\Service\GenerateCouponCodesService;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Exception;

/**
 * Class Subscriber
 * @package YuriiZh\Newsletter\Plugin\Magento\Newsletter\Model
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SubscriberPlugin
{
    public const XML_PATH_COUPON_CODE = 'newsletter/subscription/coupon_code';

    public const COUPON_CODES_QTY = 1;

    public const LENGTH = 6;

    public const PREFIX = 'LITO-2020-';

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var GenerateCouponCodesService
     */
    protected $generateCouponCodesService;

    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var SubscriberResourceModel
     */
    protected $subscriberResourceModel;

    /**
     * SubscriberPlugin constructor.
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param GenerateCouponCodesService $generateCouponCodesService
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param SubscriberResourceModel $subscriberResourceModel
     */
    public function __construct(
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        GenerateCouponCodesService $generateCouponCodesService,
        RuleCollectionFactory $ruleCollectionFactory,
        SubscriberResourceModel $subscriberResourceModel
    ) {
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->generateCouponCodesService = $generateCouponCodesService;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->subscriberResourceModel = $subscriberResourceModel;
    }

    /**
     * Sends out confirmation success email
     *
     * @param Subscriber $subject
     * @return $this
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function aroundSendConfirmationSuccessEmail(Subscriber $subject): self
    {
        $couponCode = null;
        if ($subject->getImportMode()) {
            return $this;
        }

        if (!$this->scopeConfig->getValue(
            Subscriber::XML_PATH_SUCCESS_EMAIL_TEMPLATE,
            ScopeInterface::SCOPE_STORE
        ) || !$this->scopeConfig->getValue(
            Subscriber::XML_PATH_SUCCESS_EMAIL_IDENTITY,
            ScopeInterface::SCOPE_STORE
        ) || !$this->scopeConfig->getValue(
            self::XML_PATH_COUPON_CODE,
            ScopeInterface::SCOPE_STORE
        )) {
            return $this;
        }

        if ($subject->getIsCouponCodeSent() !== "1") {
            $couponCode = $this->generateCouponCode();
            $subject->setIsCouponCodeSent("1");
            $this->subscriberResourceModel->save($subject);
        }

        $this->inlineTranslation->suspend();

        $this->transportBuilder->setTemplateIdentifier(
            $this->scopeConfig->getValue(
                self::XML_PATH_COUPON_CODE,
                ScopeInterface::SCOPE_STORE
            )
        )->setTemplateOptions(
            [
                'area' => Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()->getId(),
            ]
        )->setTemplateVars(
            [
                'subscriber' => $subject,
                'coupon_code' => $couponCode
            ]
        )->setFromByScope(
            $this->scopeConfig->getValue(
                Subscriber::XML_PATH_SUCCESS_EMAIL_IDENTITY,
                ScopeInterface::SCOPE_STORE
            )
        )->addTo(
            $subject->getEmail(),
            $subject->getName()
        );
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();

        return $this;
    }

    /**
     * @return string
     */
    private function generateCouponCode(): string
    {
        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('name', ['eq' => GenerateCouponCodesService::CART_PRICE_RULE_NAME]);
        $rule = $ruleCollection->getFirstItem();
        $params = ['length' => self::LENGTH, 'prefix' => self::PREFIX];
        $couponCodes = $this->generateCouponCodesService
            ->execute(self::COUPON_CODES_QTY, (int) $rule->getRuleId(), $params);
        return reset($couponCodes);
    }
}
