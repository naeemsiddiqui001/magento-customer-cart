<?php declare(strict_types=1);

namespace Polywood\ViewCustomerCart\ViewModel;

use Exception;
use Magento\Backend\Model\UrlInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Backend\Setup\ConfigOptionsList as BackendConfigOptionsList;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\Manager;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManager;

class QuoteInformation implements ArgumentInterface
{
    private $product;

    private $quoteRepository;

    private $cartItemRepository;

    private $connection;

    private $redirectFactory;

    private $priceHelper;

    private $storeManager;

    private $deploymentConfig;

    private $redirect;

    private $messageManager;

    private $urlInterface;

    public function __construct(
        RequestInterface $request,
        Product $product,
        CartRepositoryInterface $quoteRepository,
        CartItemRepositoryInterface $cartItemRepository,
        ResourceConnection $connection,
        RedirectFactory $redirectFactory,
        PriceHelper $priceHelper,
        StoreManager $storeManager,
        DeploymentConfig $deploymentConfig,
        RedirectInterface $redirect,
        UrlInterface $urlInterface,
        Manager $messageManager
    ) {
        $this->request = $request;
        $this->product = $product;
        $this->quoteRepository = $quoteRepository;
        $this->cartItemRepository = $cartItemRepository;
        $this->connection = $connection;
        $this->redirectFactory = $redirectFactory;
        $this->priceHelper = $priceHelper;
        $this->storeManager = $storeManager;
        $this->deploymentConfig = $deploymentConfig;
        $this->redirect = $redirect;
        $this->urlInterface = $urlInterface;
        $this->messageManager = $messageManager;
    }

    public function getQuoteData()
    {
        $data = $this->request->getPostValue();

        $quoteId = isset($data['general']['quote_id']) ? $data['general']['quote_id'] : null;
        $quoteData = [];

        if ($data) {
            try {
                if ($quoteId != null) {
                    $quote = $this->quoteRepository->get($quoteId);
                    $subTotal = $quote->getSubtotal();
                    $subTotalWithDiscount = $quote->getSubtotalWithDiscount();
                    $cartDiscount = $subTotal - $subTotalWithDiscount;
                    $grandTotal = $quote->getGrandTotal();

                    $quoteData['quote_id'] = $quoteId;
                    $quoteData['cart_subtotal'] = $this->priceHelper->currency($subTotal, true, false);
                    $quoteData['grand_total'] = $this->priceHelper->currency($grandTotal, true, false);
                    $quoteData['cart_discount'] = $this->priceHelper->currency($cartDiscount, true, false);

                    $cartItems = $this->cartItemRepository->getList($quote->getId());
                    /** @var Item $item */
                    foreach ($cartItems as $item) {
                        $quoteData['items_data'][] = [
                            'product_name' => $item->getName(),
                            'product_sku' => $item->getSku(),
                            'product_url' => $this->getProductUrl($item->getSku()),
                            'product_qty' => $item->getQty(),
                            'product_price' => $this->priceHelper->currency($item->getPrice(), true, false)
                        ];

                        if ($item->getProduct()->getData('has_options')) {
                            $extensionAttributes = $item->getData('product_option')->getData('extension_attributes');
                            $configurableItemOptions = $extensionAttributes->getConfigurableItemOptions();
                            foreach ($configurableItemOptions as $ItemOption) {
                                $optionLabel = $this->getOptionLabel((int)$ItemOption['option_value']);
                                $optionValue = $this->getOptionValue((int)$ItemOption['option_value']);
                                $quoteData['items_data'][count($quoteData['items_data'])-1]['options'][$optionLabel] = $optionValue;
                            }
                        }
                    }
                }
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $redirectResult = $this->redirectFactory->create();
                return $redirectResult->setUrl(
                    $this->redirect->getRedirectUrl($this->urlInterface->getUrl('polywood/quote/index/'))
                );
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $quoteData;
    }

    private function getProductUrl($productSku)
    {
        $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        $adminPath = $this->deploymentConfig->get(BackendConfigOptionsList::CONFIG_PATH_BACKEND_FRONTNAME);
        return $storeUrl . $adminPath . '/catalog/product/edit/id/'. $this->product->getIdBySku($productSku);
    }

    private function getOptionLabel(int $optionId)
    {
        $connection = $this->connection->getConnection();
        $select = $connection->select()
            ->from($this->connection->getTableName('eav_attribute_option'), 'attribute_id')
            ->where('option_id = ?', $optionId);

        $result = $connection->fetchOne($select);

        $connection = $this->connection->getConnection();
        $select = $connection->select()
            ->from($this->connection->getTableName('eav_attribute'), 'frontend_label')
            ->where('attribute_id = ?', $result);

        $result = $connection->fetchOne($select);

        if ($result !== false) {
            return $result;
        }

        return null;
    }

    private function getOptionValue(int $optionId)
    {
        $connection = $this->connection->getConnection();
        $select = $connection->select()
            ->from($this->connection->getTableName('eav_attribute_option_value'), 'value')
            ->where('option_id = ?', $optionId);

        $result = $connection->fetchOne($select);

        if (isset($result) == true) {
            return $result;
        }

        return null;
    }
}
