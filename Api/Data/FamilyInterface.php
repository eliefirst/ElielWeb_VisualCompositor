<?php
declare(strict_types=1);
namespace ElielWeb\VisualCompositor\Api\Data;

interface FamilyInterface
{
    const FAMILY_ID  = 'family_id';
    const CODE       = 'code';
    const LABEL      = 'label';
    const ACTIVE     = 'active';

    public function getFamilyId(): ?int;
    public function getCode(): string;
    public function getLabel(): string;
    public function getActive(): bool;
}
