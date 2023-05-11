<?php declare(strict_types=1);

namespace Polywood\ViewCustomerCart\ViewModel;

use Magento\Checkout\Model\SessionFactory as CheckoutSessionFactory;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class GetQuote implements ArgumentInterface
{
    private $checkoutSessionFactory;

    public function __construct(
        CheckoutSessionFactory $checkoutSessionFactory
    ) {
        $this->checkoutSessionFactory = $checkoutSessionFactory;
    }

    public function getQuoteId()
    {
        $checkoutSession = $this->checkoutSessionFactory->create();
        return (int)$checkoutSession->getQuote()->getId();
    }
}
