<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Mapping extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('elielweb_compositor_mapping', 'mapping_id');
    }
}
