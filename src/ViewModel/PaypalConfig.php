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
use CheckoutCom\Magento2\Model\Methods\PaypalMethod;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class PaypalConfig extends AbstractViewModel
{
    public const AUTHORIZE = 'authorize';
    public const CAPTURE = 'capture';
    public const CONTEXT_EXPRESS_CART = 'express_cart';
    public const CONTEXT_EXPRESS_MINICART = 'express_minicart';
    public const URL_PAYPAL_CONTEXT = 'checkout_com/paypal/context';
    public const URL_PAYPAL_REVIEW = 'checkout_com/paypal/review';

    public function __construct(
        private readonly PaypalMethod $paypalMethod,
        Config $checkoutConfig,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($checkoutConfig, $urlBuilder, $storeManager, $checkoutSession, $scopeConfig);
    }

    public function isEnabled(): bool
    {
        return (bool)$this->paypalMethod->getConfigData('active');
    }

    public function isCartButtonEnabled(): bool
    {
        return (bool)$this->paypalMethod->getConfigData('express_cart');
    }

    public function isMiniCartButtonEnabled(): bool
    {
        return (bool)$this->paypalMethod->getConfigData('express_minicart');
    }

    public function isButtonEnabledForContext(string $context): bool
    {
        return match ($context) {
            self::CONTEXT_EXPRESS_CART => $this->isCartButtonEnabled(),
            self::CONTEXT_EXPRESS_MINICART => $this->isMiniCartButtonEnabled(),
            default => false,
        };
    }

    public function getContextUrl(): string
    {
        return $this->urlBuilder->getUrl(self::URL_PAYPAL_CONTEXT);
    }

    public function getReviewUrl(): string
    {
        return rtrim($this->urlBuilder->getUrl(self::URL_PAYPAL_REVIEW), '/');
    }

    public function getAllowedCurrencies(): array
    {
        $currencies = (string)$this->paypalMethod->getConfigData('specificcurrencies');

        return array_values(array_filter(explode(',', $currencies)));
    }

    /**
     * Mirrors checkoutcom_paypal payload from customer-data cart section (AddConfigDataToCart).
     */
    public function getPaypalSectionConfig(): array
    {
        return [
            'active' => $this->isEnabled() ? '1' : '0',
            'express_cart' => $this->isCartButtonEnabled() ? '1' : '0',
            'express_minicart' => $this->isMiniCartButtonEnabled() ? '1' : '0',
            'checkout_client_id' => (string)$this->paypalMethod->getConfigData('checkout_client_id'),
            'merchant_id' => (string)$this->paypalMethod->getConfigData('merchant_id'),
            'checkout_partner_attribution_id' => (string)$this->paypalMethod->getConfigData('checkout_partner_attribution_id'),
        ];
    }

    /**
     * Mirrors window.paypalCheckoutConfig from CheckoutCom\Magento2\Block\Paypal\Script (express mode).
     */
    public function getPaypalSdkWindowConfig(string $context): array
    {
        return [
            'intent' => $this->checkoutConfig->needsAutoCapture() ? self::CAPTURE : self::AUTHORIZE,
            'commit' => 'false',
            'pageType' => $context === self::CONTEXT_EXPRESS_MINICART ? 'mini-cart' : 'cart',
        ];
    }
}
