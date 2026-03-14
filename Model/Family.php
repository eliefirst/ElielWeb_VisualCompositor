<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Model;

use ElielWeb\VisualCompositor\Api\Data\FamilyInterface;
use Magento\Framework\Model\AbstractModel;

class Family extends AbstractModel implements FamilyInterface
{
    protected function _construct(): void
    {
        $this->_init(\ElielWeb\VisualCompositor\Model\ResourceModel\Family::class);
    }

    public function getFamilyId(): ?int { return $this->getData(self::FAMILY_ID) ? (int)$this->getData(self::FAMILY_ID) : null; }
    public function getCode(): string { return (string)$this->getData(self::CODE); }
    public function getLabel(): string { return (string)$this->getData(self::LABEL); }
    public function getActive(): bool { return (bool)$this->getData(self::ACTIVE); }
}
