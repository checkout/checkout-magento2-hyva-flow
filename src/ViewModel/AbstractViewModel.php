<?php
/**
 * Checkout.com Magento 2 Magento2 Payment.
 *
 * PHP version 8
 *
 * @category  Checkout.com
 * @package   Magento2
 * @author    Checkout.com Development Team <integration@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://www.checkout.com
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2Hyva\ViewModel;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Provider\AbstractSettingsProvider;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class AbstractViewModel extends AbstractSettingsProvider implements ArgumentInterface
{
    public const PRODUCTION = 'production';
    public const SANDBOX = 'sandbox';

    public function __construct(
        protected readonly Config $checkoutConfig,
        protected readonly UrlInterface $urlBuilder,
        protected readonly StoreManagerInterface $storeManager,
        protected readonly CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($scopeConfig);
    }

    public function getEnvironment(): string
    {
        return (int)$this->checkoutConfig->getValue('environment', null, '', ScopeInterface::SCOPE_STORE) === 1
            ? self::SANDBOX
            : self::PRODUCTION;
    }

    public function getPublicKey(): string
    {
        return (string)$this->checkoutConfig->getValue('public_key');
    }

    public function getQuoteCurrencyCode(): string
    {
        try {
            $quote = $this->checkoutSession->getQuote();

            if ($quote->getId()) {
                return (string)$quote->getQuoteCurrencyCode();
            }
        } catch (LocalizedException|NoSuchEntityException) {
            // Fall through to store currency.
        }

        try {
            return (string)$this->storeManager->getStore()->getCurrentCurrencyCode();
        } catch (NoSuchEntityException) {
            return '';
        }
    }

    protected function getStoreCode(): ?string
    {
        try {
            return $this->storeManager->getStore()->getCode();
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    protected function getWebsiteCode(): ?string
    {
        try {
            return $this->storeManager->getWebsite()->getCode();
        } catch (NoSuchEntityException) {
            return null;
        }
    }
}
