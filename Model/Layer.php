<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Model;

use ElielWeb\VisualCompositor\Api\Data\LayerInterface;
use Magento\Framework\Model\AbstractModel;

class Layer extends AbstractModel implements LayerInterface
{
    protected function _construct(): void
    {
        $this->_init(\ElielWeb\VisualCompositor\Model\ResourceModel\Layer::class);
    }

    public function getLayerId(): ?int { return $this->getData(self::LAYER_ID) ? (int)$this->getData(self::LAYER_ID) : null; }
    public function getFamilyId(): int { return (int)$this->getData(self::FAMILY_ID); }
    public function getCode(): string { return (string)$this->getData(self::CODE); }
    public function getLabel(): string { return (string)$this->getData(self::LABEL); }
    public function getSortOrder(): int { return (int)$this->getData(self::SORT_ORDER); }
    public function getLayerType(): string { return (string)$this->getData(self::LAYER_TYPE); }
    public function getOptionCode(): ?string { return $this->getData(self::OPTION_CODE); }
    public function getDefaultFile(): ?string { return $this->getData(self::DEFAULT_FILE); }
    public function getActive(): bool { return (bool)$this->getData(self::ACTIVE); }
}
