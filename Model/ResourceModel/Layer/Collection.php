<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Model\ResourceModel\Layer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(
            \ElielWeb\VisualCompositor\Model\Layer::class,
            \ElielWeb\VisualCompositor\Model\ResourceModel\Layer::class
        );
    }
}
