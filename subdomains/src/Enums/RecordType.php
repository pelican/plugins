<?php

namespace Boy132\Subdomains\Enums;

use Filament\Support\Contracts\HasLabel;

enum RecordType: string implements HasLabel
{
    case A = 'A';
    case AAAA = 'AAAA';
    case CNAME = 'CNAME';
    case SRV = 'SRV';

    public function getLabel(): string
    {
        return $this->name;
    }
}
