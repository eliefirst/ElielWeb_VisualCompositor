<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Layer extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('elielweb_compositor_layer', 'layer_id');
    }
}
