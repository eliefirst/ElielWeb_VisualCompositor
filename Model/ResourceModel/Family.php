<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Family extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('elielweb_compositor_family', 'family_id');
    }
}
