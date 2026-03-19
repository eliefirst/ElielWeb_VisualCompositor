<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Service;

use ElielWeb\VisualCompositor\Model\ResourceModel\Family\CollectionFactory as FamilyCollectionFactory;
use ElielWeb\VisualCompositor\Model\ResourceModel\Layer\CollectionFactory as LayerCollectionFactory;
use ElielWeb\VisualCompositor\Model\ResourceModel\Mapping\CollectionFactory as MappingCollectionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;

class CompositionService
{
    public function __construct(
        private readonly FamilyCollectionFactory  $familyCollectionFactory,
        private readonly LayerCollectionFactory   $layerCollectionFactory,
        private readonly MappingCollectionFactory $mappingCollectionFactory,
        private readonly ResourceConnection       $resourceConnection,
        private readonly Filesystem               $filesystem
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

        // Collecte des valeurs par défaut des attributs produit (ex: gold_color → "Or Blanc")
        $defaultValues = [];

        foreach ($layers as $layer) {
            $layerData = [
                'id'          => $layer->getLayerId(),
                'code'        => $layer->getCode(),
                'type'        => $layer->getLayerType(),
                'sort_order'  => $layer->getSortOrder(),
                'option_code' => $layer->getOptionCode(),
                'default_file'=> $layer->getDefaultFile(),
                'mappings'    => []
            ];

            // Pour les couches de type 'option', charger tous les mappings disponibles
            if ($layer->getLayerType() === 'option') {
                // Priorité 1 : mappings famille (product_sku = NULL)
                $familyMappings = $this->mappingCollectionFactory->create();
                $familyMappings->addFieldToFilter('layer_id', $layer->getLayerId())
                               ->addFieldToFilter('product_sku', ['null' => true]);
                foreach ($familyMappings as $mapping) {
                    $layerData['mappings'][$mapping->getOptionValue()] = $mapping->getPngFile();
                }

                // Priorité 2 : mappings produit-spécifiques écrasent les mappings famille
                $productMappings = $this->mappingCollectionFactory->create();
                $productMappings->addFieldToFilter('layer_id', $layer->getLayerId())
                                ->addFieldToFilter('product_sku', ['eq' => $product->getSku()]);
                foreach ($productMappings as $mapping) {
                    $layerData['mappings'][$mapping->getOptionValue()] = $mapping->getPngFile();
                }

                // Détecte la valeur par défaut depuis l'attribut produit (option_code non-numérique)
                $optionCode = $layer->getOptionCode();
                if ($optionCode && !is_numeric($optionCode) && !isset($defaultValues[$optionCode])) {
                    // getAttributeText() retourne la valeur texte (ex: "Or Blanc"), jamais un ID numérique
                    $attrText = $product->getAttributeText($optionCode);
                    if ($attrText && is_string($attrText)) {
                        $defaultValues[$optionCode] = $attrText;
                    } elseif ($attrText && is_object($attrText) && method_exists($attrText, '__toString')) {
                        // Magento\Framework\Phrase ou similaire
                        $defaultValues[$optionCode] = (string)$attrText;
                    } else {
                        // Fallback : passer par le source model pour convertir l'ID en label
                        $optionId = $product->getData($optionCode);
                        if ($optionId) {
                            $attribute = $product->getResource()->getAttribute($optionCode);
                            if ($attribute && $attribute->usesSource()) {
                                $label = $attribute->getSource()->getOptionText($optionId);
                                if ($label) {
                                    $defaultValues[$optionCode] = is_string($label) ? $label : (string)$label;
                                }
                            }
                        }
                    }
                }
            }

            $config['layers'][] = $layerData;
        }

        if (!empty($defaultValues)) {
            $config['defaultValues'] = $defaultValues;
        }

        // Calcul des bornes de contenu (trim transparence) pour chaque image (mappings + default_file)
        $fileBounds = [];
        $mediaDir   = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        $collectBounds = function (string $file) use (&$fileBounds, $mediaDir): void {
            if ($file === '' || isset($fileBounds[$file])) {
                return;
            }
            $absPath = $mediaDir->getAbsolutePath('compositor/' . $file);
            $bounds  = $this->computePngBounds($absPath);
            if ($bounds !== null) {
                $fileBounds[$file] = $bounds;
            }
        };

        foreach ($config['layers'] as $layer) {
            // Couches fixes : default_file
            if (!empty($layer['default_file'])) {
                $collectBounds($layer['default_file']);
            }
            // Couches option : tous les mappings
            foreach ($layer['mappings'] as $file) {
                $collectBounds($file);
            }
        }
        if (!empty($fileBounds)) {
            $config['file_bounds'] = $fileBounds;
        }

        return $config;
    }

    /**
     * Calcule la boîte englobante des pixels non-transparents d'un PNG.
     * Met en cache le résultat dans un fichier sidecar .bounds.json.
     *
     * @return array{x:int,y:int,w:int,h:int,iw:int,ih:int}|null
     */
    private function computePngBounds(string $absPath): ?array
    {
        if (!file_exists($absPath)) {
            return null;
        }

        // Sidecar cache : évite de re-scanner à chaque page load
        $cacheFile = $absPath . '.bounds.json';
        if (file_exists($cacheFile) && filemtime($cacheFile) >= filemtime($absPath)) {
            $cached = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['iw'])) {
                return $cached;
            }
        }

        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $content = @file_get_contents($absPath);
        if ($content === false) {
            return null;
        }

        $img = @imagecreatefromstring($content);
        if (!$img) {
            return null;
        }

        $iw   = imagesx($img);
        $ih   = imagesy($img);
        $minX = $iw;
        $minY = $ih;
        $maxX = -1;
        $maxY = -1;

        // GD alpha : 0 = opaque, 127 = fully transparent ; seuil < 64 ≈ ≥ 50% opaque
        for ($y = 0; $y < $ih; $y++) {
            for ($x = 0; $x < $iw; $x++) {
                $alpha = (imagecolorat($img, $x, $y) >> 24) & 0x7F;
                if ($alpha < 64) {
                    if ($x < $minX) $minX = $x;
                    if ($x > $maxX) $maxX = $x;
                    if ($y < $minY) $minY = $y;
                    if ($y > $maxY) $maxY = $y;
                }
            }
        }

        imagedestroy($img);

        if ($maxX < 0) {
            return null; // image entièrement transparente
        }

        $bounds = [
            'x'  => $minX,
            'y'  => $minY,
            'w'  => $maxX - $minX + 1,
            'h'  => $maxY - $minY + 1,
            'iw' => $iw,
            'ih' => $ih,
        ];

        @file_put_contents($cacheFile, json_encode($bounds));

        return $bounds;
    }
}
