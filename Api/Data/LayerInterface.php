<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Api\Data;

interface LayerInterface
{
    const LAYER_ID    = 'layer_id';
    const FAMILY_ID   = 'family_id';
    const CODE        = 'code';
    const LABEL       = 'label';
    const SORT_ORDER  = 'sort_order';
    const LAYER_TYPE  = 'layer_type';
    const OPTION_CODE = 'option_code';
    const DEFAULT_FILE = 'default_file';
    const ACTIVE      = 'active';

    const TYPE_FIXED  = 'fixed';
    const TYPE_OPTION = 'option';

    public function getLayerId(): ?int;
    public function getFamilyId(): int;
    public function getCode(): string;
    public function getLabel(): string;
    public function getSortOrder(): int;
    public function getLayerType(): string;
    public function getOptionCode(): ?string;
    public function getDefaultFile(): ?string;
    public function getActive(): bool;
}
