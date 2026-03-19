<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\ViewModel;

use ElielWeb\VisualCompositor\Service\CompositionService;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductCompositor implements ArgumentInterface
{
    public function __construct(
        private readonly CompositionService   $compositionService,
        private readonly StoreManagerInterface $storeManager,
        private readonly Registry             $registry
    ) {}

    /**
     * Retourne le produit courant depuis le registre Magento
     */
    public function getCurrentProduct(): ?ProductInterface
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Vérifie si le compositor est actif pour ce produit
     */
    public function isEnabled(?ProductInterface $product): bool
    {
        return $this->compositionService->isEligible($product);
    }

    /**
     * Retourne la config JSON pour Alpine.js
     */
    public function getConfigJson(ProductInterface $product): string
    {
        $config = $this->compositionService->buildFrontConfig($product);
        if (empty($config)) {
            return '{}';
        }

        // Préfixer les chemins de fichiers avec l'URL media de base
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );

        // Transformer file_bounds (clé = chemin relatif) en bounds (clé = URL complète)
        $fileBounds = $config['file_bounds'] ?? [];
        unset($config['file_bounds']);
        $urlBounds = [];

        foreach ($config['layers'] as &$layer) {
            if (!empty($layer['default_file'])) {
                $layer['default_url'] = $mediaUrl . 'compositor/' . $layer['default_file'];
            }
            foreach ($layer['mappings'] as $value => $file) {
                $url = $mediaUrl . 'compositor/' . $file;
                $layer['mappings'][$value] = $url;
                if (isset($fileBounds[$file])) {
                    $urlBounds[$url] = $fileBounds[$file];
                }
            }
        }
        unset($layer);

        if (!empty($urlBounds)) {
            $config['bounds'] = $urlBounds;
        }

        return json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Retourne l'URL media de base du compositor
     */
    public function getMediaBaseUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . 'compositor/';
    }
}
