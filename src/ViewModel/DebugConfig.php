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

use Magento\Framework\App\State;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class DebugConfig implements ArgumentInterface
{
    public function __construct(private readonly State $appState)
    {
    }

    public function isDebugEnabled(): bool
    {
        return $this->appState->getMode() === State::MODE_DEVELOPER;
    }
}
