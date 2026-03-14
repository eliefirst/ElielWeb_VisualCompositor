<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Service;

use ElielWeb\VisualCompositor\Model\ResourceModel\Family\CollectionFactory as FamilyCollectionFactory;
use ElielWeb\VisualCompositor\Model\ResourceModel\Layer\CollectionFactory as LayerCollectionFactory;
use ElielWeb\VisualCompositor\Model\ResourceModel\Mapping\CollectionFactory as MappingCollectionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;

class CompositionService
{
    public function __construct(
        private readonly FamilyCollectionFactory  $familyCollectionFactory,
        private readonly LayerCollectionFactory   $layerCollectionFactory,
        private readonly MappingCollectionFactory $mappingCollectionFactory,
        private readonly ResourceConnection       $resourceConnection
    ) {}

    /**
     * Vérifie si le produit est éligible au compositor
     */
    public function isEligible(ProductInterface $product): bool
    {
        return (bool)$product->getData('dynamic_image_enabled');
    }

    /**
     * Récupère la famille associée au produit
     */
    public function getFamilyForProduct(ProductInterface $product): ?array
    {
        $conn  = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('elielweb_compositor_product');
        $familyTable = $this->resourceConnection->getTableName('elielweb_compositor_family');

        $select = $conn->select()
            ->from(['cp' => $table])
            ->join(['f' => $familyTable], 'cp.family_id = f.family_id', ['code', 'label'])
            ->where('cp.product_id = ?', (int)$product->getId())
            ->where('cp.active = 1')
            ->where('f.active = 1');

        return $conn->fetchRow($select) ?: null;
    }

    /**
     * Récupère les couches d'une famille triées par sort_order
     */
    public function getLayersForFamily(int $familyId): array
    {
        $collection = $this->layerCollectionFactory->create();
        $collection->addFieldToFilter('family_id', $familyId)
                   ->addFieldToFilter('active', 1)
                   ->setOrder('sort_order', 'ASC');

        return $collection->getItems();
    }

    /**
     * Récupère le fichier PNG pour une couche + valeur option donnée
     * Fallback : mapping produit spécifique → mapping famille → default_file
     */
    public function resolveLayerFile(int $layerId, string $optionValue, ?string $productSku = null): ?string
    {
        $collection = $this->mappingCollectionFactory->create();
        $collection->addFieldToFilter('layer_id', $layerId)
                   ->addFieldToFilter('option_value', $optionValue);

        // Priorité 1 : mapping spécifique produit
        if ($productSku) {
            $specific = clone $collection;
            $specific->addFieldToFilter('product_sku', $productSku);
            if ($specific->getSize() > 0) {
                return $specific->getFirstItem()->getPngFile();
            }
        }

        // Priorité 2 : mapping générique famille (product_sku = NULL)
        $generic = clone $collection;
        $generic->addFieldToFilter('product_sku', ['null' => true]);
        if ($generic->getSize() > 0) {
            return $generic->getFirstItem()->getPngFile();
        }

        return null;
    }

    /**
     * Construit la configuration complète des couches pour le front Alpine.js
     * Retourne un tableau prêt à être encodé en JSON
     */
    public function buildFrontConfig(ProductInterface $product): array
    {
        if (!$this->isEligible($product)) {
            return [];
        }

        $family = $this->getFamilyForProduct($product);
        if (!$family) {
            return [];
        }

        $layers = $this->getLayersForFamily((int)$family['family_id']);
        $config = [
            'enabled'    => true,
            'family'     => $family['code'],
            'productSku' => $product->getSku(),
            'layers'     => []
        ];

        foreach ($layers as $layer) {
            $layerData = [
                'id'          => $layer->getLayerId(),
                'code'        => $layer->getCode(),
                'type'        => $layer->getLayerType(),
                'option_code' => $layer->getOptionCode(),
                'default_file'=> $layer->getDefaultFile(),
                'mappings'    => []
            ];

            // Pour les couches de type 'option', charger tous les mappings disponibles
            if ($layer->getLayerType() === 'option') {
                $mappings = $this->mappingCollectionFactory->create();
                $mappings->addFieldToFilter('layer_id', $layer->getLayerId());

                // Mappings spécifiques produit en priorité
                $skuFilter = [
                    ['null' => true],
                    ['eq'   => $product->getSku()]
                ];
                $mappings->addFieldToFilter('product_sku', $skuFilter);

                foreach ($mappings as $mapping) {
                    $layerData['mappings'][$mapping->getOptionValue()] = $mapping->getPngFile();
                }
            }

            $config['layers'][] = $layerData;
        }

        return $config;
    }
}
