<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Model;

use ElielWeb\VisualCompositor\Api\Data\MappingInterface;
use Magento\Framework\Model\AbstractModel;

class Mapping extends AbstractModel implements MappingInterface
{
    protected function _construct(): void
    {
        $this->_init(\ElielWeb\VisualCompositor\Model\ResourceModel\Mapping::class);
    }

    public function getMappingId(): ?int { return $this->getData(self::MAPPING_ID) ? (int)$this->getData(self::MAPPING_ID) : null; }
    public function getLayerId(): int { return (int)$this->getData(self::LAYER_ID); }
    public function getOptionValue(): string { return (string)$this->getData(self::OPTION_VALUE); }
    public function getPngFile(): string { return (string)$this->getData(self::PNG_FILE); }
    public function getProductSku(): ?string { return $this->getData(self::PRODUCT_SKU); }
}
