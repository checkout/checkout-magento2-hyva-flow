<?php

declare(strict_types=1);

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

namespace CheckoutCom\Magento2Hyva\ViewModel;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Provider\ApplePaymentSettings;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class ApplePayConfig extends AbstractViewModel
{
    public const METHOD_ID = 'checkoutcom_apple_pay';
    public const CONTEXT_EXPRESS_CART = 'express_cart';
    public const CONTEXT_EXPRESS_MINICART = 'express_minicart';
    public const URL_APPLEPAY_VALIDATION = 'checkout_com/applepay/validation';
    public const URL_PAYMENT_PLACE_ORDER = 'checkout_com/payment/placeorder';
    public const URL_CHECKOUT_SUCCESS = 'checkout/onepage/success';

    private const APPLE_PAY_VERSION = 14;
    private const APPLE_PAY_FALLBACK_VERSION = 5;
    private const CONFIG_DEFAULT_COUNTRY = 'general/country/default';

    protected ?string $websiteCode = null;

    public function __construct(
        Config $checkoutConfig,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig,
    ) {
        parent::__construct($checkoutConfig, $urlBuilder, $storeManager, $checkoutSession, $scopeConfig);
        $this->websiteCode = $this->getWebsiteCode();
    }

    public function isEnabled(): bool
    {
        return (bool)$this->getWebsiteLevelConfiguration(ApplePaymentSettings::CONFIG_IS_ACTIVE, $this->websiteCode);
    }

    public function isCartButtonEnabled(): bool
    {
        return $this->isEnabled()
            && (bool)$this->getWebsiteLevelConfiguration(ApplePaymentSettings::CONFIG_ENABLED_ON_CART, $this->websiteCode);
    }

    public function isMiniCartButtonEnabled(): bool
    {
        return $this->isEnabled()
            && (bool)$this->getWebsiteLevelConfiguration(ApplePaymentSettings::CONFIG_ENABLED_ON_MINICART, $this->websiteCode);
    }

    public function isButtonEnabledForContext(string $context): bool
    {
        return match ($context) {
            self::CONTEXT_EXPRESS_CART => $this->isCartButtonEnabled(),
            self::CONTEXT_EXPRESS_MINICART => $this->isMiniCartButtonEnabled(),
            default => false,
        };
    }

    public function getMethodId(): string
    {
        return self::METHOD_ID;
    }

    public function getButtonStyle(): string
    {
        return (string)$this->getStoreLevelConfiguration(ApplePaymentSettings::CONFIG_BUTTON_STYLE, $this->getStoreCode());
    }

    public function getMerchantId(): string
    {
        return (string)$this->getWebsiteLevelConfiguration(ApplePaymentSettings::CONFIG_MERCHANT_ID, $this->websiteCode);
    }

    public function getSupportedNetworks(): array
    {
        return $this->parseCommaSeparatedList(
            (string)$this->getWebsiteLevelConfiguration(ApplePaymentSettings::CONFIG_SUPPORTED_NETWORK, $this->websiteCode)
        );
    }

    public function getMerchantCapabilities(): array
    {
        $configured = $this->parseCommaSeparatedList(
            (string)$this->getWebsiteLevelConfiguration(ApplePaymentSettings::CONFIG_MERCHANT_CAPABILITIES, $this->websiteCode)
        );

        return array_values(array_unique(array_merge(['supports3DS'], $configured)));
    }

    public function getCountryCode(): string
    {
        try {
            $storeCountry = (string)$this->checkoutConfig->getStoreCountry();

            return $storeCountry !== '' ? $storeCountry : $this->getDefaultCountryCode();
        } catch (NoSuchEntityException) {
            return $this->getDefaultCountryCode();
        }
    }

    public function getCurrencyCode(): string
    {
        return $this->getQuoteCurrencyCode();
    }

    public function getValidationUrl(): string
    {
        return rtrim($this->urlBuilder->getUrl(self::URL_APPLEPAY_VALIDATION), '/');
    }

    public function getPlaceOrderUrl(): string
    {
        return $this->urlBuilder->getUrl(self::URL_PAYMENT_PLACE_ORDER);
    }

    public function getSuccessUrl(): string
    {
        return $this->urlBuilder->getUrl(self::URL_CHECKOUT_SUCCESS);
    }

    public function getRestApiBaseUrl(): string
    {
        $storeCode = $this->getStoreCode();

        if ($storeCode === null) {
            return '';
        }

        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB)
            . 'rest/' . urlencode($storeCode) . '/V1/';
    }

    public function getApplePayVersion(): int
    {
        return self::APPLE_PAY_VERSION;
    }

    public function getApplePayFallbackVersion(): int
    {
        return self::APPLE_PAY_FALLBACK_VERSION;
    }

    /**
     * Returns the public-side Apple Pay config payload that the Alpine factory hydrates from.
     * Mirrors what the core Luma module exposes via window.checkoutConfig.payment.checkoutcom_apple_pay.
     */
    public function getApplePaySectionConfig(): array
    {
        return [
            'active' => $this->isEnabled() ? '1' : '0',
            'enabled_on_cart' => $this->isCartButtonEnabled() ? '1' : '0',
            'enabled_on_minicart' => $this->isMiniCartButtonEnabled() ? '1' : '0',
            'button_style' => $this->getButtonStyle(),
            'merchant_id' => $this->getMerchantId(),
            'supported_networks' => $this->getSupportedNetworks(),
            'merchant_capabilities' => $this->getMerchantCapabilities(),
            'country_code' => $this->getCountryCode(),
            'currency_code' => $this->getCurrencyCode(),
            'apple_pay_version' => $this->getApplePayVersion(),
            'apple_pay_fallback_version' => $this->getApplePayFallbackVersion(),
            'method_id' => $this->getMethodId(),
        ];
    }

    private function parseCommaSeparatedList(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    private function getDefaultCountryCode(): string
    {
        try {
            return (string)$this->storeManager->getStore()->getConfig(self::CONFIG_DEFAULT_COUNTRY);
        } catch (NoSuchEntityException) {
            return '';
        }
    }
}
