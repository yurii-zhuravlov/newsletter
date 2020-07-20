<?php

namespace YuriiZh\Newsletter\Plugin\Magento\Newsletter\Model;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Subscriber
 * @package YuriiZh\Newsletter\Plugin\Magento\Newsletter\Model
 */
class SubscriberPlugin
{
    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * SubscriberPlugin constructor.
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Subscriber $subject
     * @param $result
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     */
    public function afterSendConfirmationSuccessEmail(Subscriber $subject, $result)
    {
        if ($result->getIsCouponCodeSent() !== "1") {
            $this->sendCouponCode($subject);
            $result->setIsCouponCodeSent("1");
            $result->save();
        }
    }

    /**
     * Sends out confirmation success email
     *
     * @param $result
     * @return mixed $result
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     */
    public function sendCouponCode($result)
    {
        $this->inlineTranslation->suspend();

        $this->transportBuilder->setTemplateIdentifier(
            $this->scopeConfig->getValue(
                Subscriber::XML_PATH_SUCCESS_EMAIL_TEMPLATE,
                ScopeInterface::SCOPE_STORE
            )
        )->setTemplateOptions(
            [
                'area' => Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()->getId(),
            ]
        )->setTemplateVars(
            ['subscriber' => $result]
        )->setFrom(
            $this->scopeConfig->getValue(
                Subscriber::XML_PATH_SUCCESS_EMAIL_IDENTITY,
                ScopeInterface::SCOPE_STORE
            )
        )->addTo(
            $result->getEmail(),
            $result->getName()
        );
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();

        return $result;
    }
}
