<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2Hyva\Plugin\Paypal;

use CheckoutCom\Magento2\Model\Service\PaymentContextRequestService;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * The PayPal payment context exposes the payer name under
 * payment_request.source.account_holder.full_name, but the base
 * refreshQuoteWithPaymentContext() only reads shipping.first_name (absent for
 * PayPal), leaving the quote address without firstname/lastname and breaking
 * place order. This fills the name from the payer when it is missing.
 */
class PopulatePayerName
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly CartRepositoryInterface $cartRepository
    ) {
    }

    public function afterRefreshQuoteWithPaymentContext(
        PaymentContextRequestService $subject,
        $result,
        array $contextDatas
    ) {
        $fullName = trim((string)($contextDatas['payment_request']['source']['account_holder']['full_name'] ?? ''));
        if ($fullName === '') {
            return $result;
        }

        $parts = explode(' ', $fullName, 2);
        $firstname = $parts[0];
        // Magento requires a non-empty lastname at order placement; PayPal can return a
        // single-token name, so fall back to the first name to keep the address valid.
        $lastname = isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : $firstname;

        $quote = $this->checkoutSession->getQuote();
        $updated = false;
        foreach ([$quote->getBillingAddress(), $quote->getShippingAddress()] as $address) {
            if (!$address) {
                continue;
            }
            if (!$address->getFirstname()) {
                $address->setFirstname($firstname);
                $updated = true;
            }
            if (!$address->getLastname()) {
                $address->setLastname($lastname);
                $updated = true;
            }
        }

        if ($updated) {
            $this->cartRepository->save($quote);
        }

        return $result;
    }
}
