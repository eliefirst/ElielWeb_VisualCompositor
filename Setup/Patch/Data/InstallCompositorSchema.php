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
        $setup = $this->moduleDataSetup;
        $conn  = $setup->getConnection();

        // Table familles
        if (!$conn->isTableExists($setup->getTable('elielweb_compositor_family'))) {
            $table = $conn->newTable($setup->getTable('elielweb_compositor_family'))
                ->addColumn('family_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true])
                ->addColumn('code', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 64, ['nullable' => false])
                ->addColumn('label', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, ['nullable' => false])
                ->addColumn('active', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, ['nullable' => false, 'default' => 1])
                ->addColumn('created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT])
                ->addIndex($setup->getIdxName('elielweb_compositor_family', ['code']), ['code'], ['type' => 'unique'])
                ->setComment('VisualCompositor Families');
            $conn->createTable($table);
        }

        // Table couches
        if (!$conn->isTableExists($setup->getTable('elielweb_compositor_layer'))) {
            $table = $conn->newTable($setup->getTable('elielweb_compositor_layer'))
                ->addColumn('layer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true])
                ->addColumn('family_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false])
                ->addColumn('code', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 64, ['nullable' => false])
                ->addColumn('label', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, ['nullable' => false])
                ->addColumn('sort_order', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['nullable' => false, 'default' => 0])
                ->addColumn('layer_type', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 32, ['nullable' => false, 'default' => 'fixed'])
                ->addColumn('option_code', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 128, ['nullable' => true])
                ->addColumn('default_file', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 512, ['nullable' => true])
                ->addColumn('active', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, ['nullable' => false, 'default' => 1])
                ->addForeignKey(
                    $setup->getFkName('elielweb_compositor_layer', 'family_id', 'elielweb_compositor_family', 'family_id'),
                    'family_id', $setup->getTable('elielweb_compositor_family'), 'family_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('VisualCompositor Layers');
            $conn->createTable($table);
        }

        // Table mappings
        if (!$conn->isTableExists($setup->getTable('elielweb_compositor_mapping'))) {
            $table = $conn->newTable($setup->getTable('elielweb_compositor_mapping'))
                ->addColumn('mapping_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true])
                ->addColumn('layer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false])
                ->addColumn('option_value', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, ['nullable' => false])
                ->addColumn('png_file', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 512, ['nullable' => false])
                ->addColumn('product_sku', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 64, ['nullable' => true])
                ->addForeignKey(
                    $setup->getFkName('elielweb_compositor_mapping', 'layer_id', 'elielweb_compositor_layer', 'layer_id'),
                    'layer_id', $setup->getTable('elielweb_compositor_layer'), 'layer_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('VisualCompositor Mappings');
            $conn->createTable($table);
        }

        // Table produit/famille
        if (!$conn->isTableExists($setup->getTable('elielweb_compositor_product'))) {
            $table = $conn->newTable($setup->getTable('elielweb_compositor_product'))
                ->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true])
                ->addColumn('product_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false])
                ->addColumn('family_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false])
                ->addColumn('active', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, ['nullable' => false, 'default' => 1])
                ->addIndex(
                    $setup->getIdxName('elielweb_compositor_product', ['product_id'], 'unique'),
                    ['product_id'], ['type' => 'unique']
                )
                ->addForeignKey(
                    $setup->getFkName('elielweb_compositor_product', 'family_id', 'elielweb_compositor_family', 'family_id'),
                    'family_id', $setup->getTable('elielweb_compositor_family'), 'family_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('VisualCompositor Product/Family associations');
            $conn->createTable($table);
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
