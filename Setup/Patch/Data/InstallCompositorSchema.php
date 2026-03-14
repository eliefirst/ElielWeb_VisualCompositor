<?php
declare(strict_types=1);

namespace ElielWeb\VisualCompositor\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Product;

class InstallCompositorSchema implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {}

    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();
        $conn = $this->moduleDataSetup->getConnection();

        // Table familles
        if (!$conn->isTableExists('elielweb_compositor_family')) {
            $conn->query("CREATE TABLE `elielweb_compositor_family` (
                `family_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(64) NOT NULL,
                `label` VARCHAR(255) NOT NULL,
                `active` SMALLINT NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`family_id`),
                UNIQUE KEY `UNQ_FAMILY_CODE` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='VisualCompositor Families'");
        }

        // Table couches
        if (!$conn->isTableExists('elielweb_compositor_layer')) {
            $conn->query("CREATE TABLE `elielweb_compositor_layer` (
                `layer_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `family_id` INT UNSIGNED NOT NULL,
                `code` VARCHAR(64) NOT NULL,
                `label` VARCHAR(255) NOT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `layer_type` VARCHAR(32) NOT NULL DEFAULT 'fixed',
                `option_code` VARCHAR(128) DEFAULT NULL,
                `default_file` VARCHAR(512) DEFAULT NULL,
                `active` SMALLINT NOT NULL DEFAULT 1,
                PRIMARY KEY (`layer_id`),
                CONSTRAINT `FK_LAYER_FAMILY` FOREIGN KEY (`family_id`)
                    REFERENCES `elielweb_compositor_family` (`family_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='VisualCompositor Layers'");
        }

        // Table mappings
        if (!$conn->isTableExists('elielweb_compositor_mapping')) {
            $conn->query("CREATE TABLE `elielweb_compositor_mapping` (
                `mapping_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `layer_id` INT UNSIGNED NOT NULL,
                `option_value` VARCHAR(255) NOT NULL,
                `png_file` VARCHAR(512) NOT NULL,
                `product_sku` VARCHAR(64) DEFAULT NULL,
                PRIMARY KEY (`mapping_id`),
                CONSTRAINT `FK_MAPPING_LAYER` FOREIGN KEY (`layer_id`)
                    REFERENCES `elielweb_compositor_layer` (`layer_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='VisualCompositor Mappings'");
        }

        // Table produit/famille
        if (!$conn->isTableExists('elielweb_compositor_product')) {
            $conn->query("CREATE TABLE `elielweb_compositor_product` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_id` INT UNSIGNED NOT NULL,
                `family_id` INT UNSIGNED NOT NULL,
                `active` SMALLINT NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`),
                UNIQUE KEY `UNQ_PRODUCT` (`product_id`),
                CONSTRAINT `FK_PRODUCT_FAMILY` FOREIGN KEY (`family_id`)
                    REFERENCES `elielweb_compositor_family` (`family_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='VisualCompositor Product/Family'");
        }

        // Attribut EAV dynamic_image_enabled
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'dynamic_image_enabled')) {
            $eavSetup->addAttribute(Product::ENTITY, 'dynamic_image_enabled', [
                'type'                    => 'int',
                'label'                   => 'Dynamic Image Enabled',
                'input'                   => 'boolean',
                'source'                  => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                'required'                => false,
                'default'                 => 0,
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'user_defined'            => true,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => false,
                'unique'                  => false,
                'group'                   => 'General',
                'sort_order'              => 100,
            ]);
        }

        $this->moduleDataSetup->endSetup();
    }

    public static function getDependencies(): array { return []; }
    public function getAliases(): array { return []; }
}
