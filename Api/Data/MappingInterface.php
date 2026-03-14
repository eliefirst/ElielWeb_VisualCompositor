<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Api\Data;

interface MappingInterface
{
    const MAPPING_ID   = 'mapping_id';
    const LAYER_ID     = 'layer_id';
    const OPTION_VALUE = 'option_value';
    const PNG_FILE     = 'png_file';
    const PRODUCT_SKU  = 'product_sku';

    public function getMappingId(): ?int;
    public function getLayerId(): int;
    public function getOptionValue(): string;
    public function getPngFile(): string;
    public function getProductSku(): ?string;
}
