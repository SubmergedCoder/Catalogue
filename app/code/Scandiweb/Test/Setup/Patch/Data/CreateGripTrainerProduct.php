<?php

namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

class CreateGripTrainerProduct implements DataPatchInterface
{
    protected ModuleDataSetupInterface $setup;
    protected ProductInterfaceFactory $productInterfaceFactory;
    protected ProductRepositoryInterface $productRepository;
    protected State $appState;
    protected EavSetup $eavSetup;
    protected StoreManagerInterface $storeManager;
    protected SourceItemInterfaceFactory $sourceItemFactory;
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;
    protected CategoryLinkManagementInterface $categoryLink;

    public function __construct(
        ModuleDataSetupInterface $setup,
        ProductInterfaceFactory $productInterfaceFactory,
        ProductRepositoryInterface $productRepository,
        State $appState,
        StoreManagerInterface $storeManager,
        EavSetup $eavSetup,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        CategoryLinkManagementInterface $categoryLink
    ) {
        $this->appState = $appState;
        $this->productInterfaceFactory = $productInterfaceFactory;
        $this->productRepository = $productRepository;
        $this->setup = $setup;
        $this->eavSetup = $eavSetup;
        $this->storeManager = $storeManager;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->categoryLink = $categoryLink;
    }

    public function apply()
    {
        $this->setup->startSetup();
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, [$this, 'execute']);
        $this->setup->endSetup();
    }

    public function execute()
    {
        // Create the product
        $product = $this->productInterfaceFactory->create();

        // Check if the product already exists
        if ($product->getIdBySku('grip-trainer')) {
            return;
        }

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');

        // Set attributes
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId($attributeSetId)
            ->setName('Grip Trainer')
            ->setSku('#123')
            ->setUrlKey('griptrainer')
            ->setPrice(3.33)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED);

        // Save the product to the repository
        $product = $this->productRepository->save($product);


        // Assign the product to the categories
        $this->categoryLink->assignProductToCategories($product->getSku(), [11]);


        // Initialize the sourceItems array
        $sourceItems = [];

        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default');
        // Set the quantity of items in stock
        $sourceItem->setQuantity(100);
        // Add the product's SKU that will be linked to this source item
        $sourceItem->setSku($product->getSku());
        // Set the stock status
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $sourceItems[] = $sourceItem;

        // Save the source items
        $this->sourceItemsSaveInterface->execute($sourceItems);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
