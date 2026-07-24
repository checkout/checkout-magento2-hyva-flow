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

namespace CheckoutCom\Magento2Hyva\CustomerData;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;

class ExpressCheckout implements SectionSourceInterface
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly CustomerSession $customerSession,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly QuoteIdMaskResource $quoteIdMaskResource
    ) {
    }

    /**
     * @return array{is_logged_in: bool, masked_quote_id: string}
     */
    public function getSectionData(): array
    {
        $isLoggedIn = $this->customerSession->isLoggedIn();

        $data = [
            'is_logged_in' => $isLoggedIn,
            'masked_quote_id' => '',
        ];
        
        if ($isLoggedIn) {
            return $data;
        }

        $quoteId = (int)$this->checkoutSession->getQuoteId();

        if ($quoteId === 0) {
            return $data;
        }

        $data['masked_quote_id'] = $this->resolveMaskedQuoteId($quoteId);

        return $data;
    }

    /**
     * Resolve the masked id for a guest quote, creating the quote_id_mask row
     * when it is missing.
     *
     * @param int $quoteId
     * @return string
     */
    private function resolveMaskedQuoteId(int $quoteId): string
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $this->quoteIdMaskResource->load($quoteIdMask, $quoteId, 'quote_id');

        if (!$quoteIdMask->getMaskedId()) {
            $quoteIdMask->setQuoteId($quoteId);
            $this->quoteIdMaskResource->save($quoteIdMask);
        }

        return (string)$quoteIdMask->getMaskedId();
    }
}
