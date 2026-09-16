<?php

namespace Boy132\Subdomains\Enums;

use App\Models\Allocation;
use App\Models\Server;
use Boy132\Subdomains\Models\CloudflareDomain;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Collection;

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

    /**
     * Returns errors that prevent this record type from being used with the provided server and domain.
     * If empty, then this record type is allowed to be used.
     * Most important error is always returned first.
     *
     * @return Collection<string>
     */
    public function canBeUsedErrors(Server $server, CloudflareDomain $domain): Collection
    {
        $errors = new Collection();

        $allocation = $server->allocation;

        $targetAddress = '';
        if ($allocation) {
            if (in_array($allocation->ip, ['0.0.0.0', '::'])) {
                $errors->add('Allocation ip is invalid (0.0.0.0 or ::)');
            }

            $targetAddress = $server->node->subdomain_use_alias ? $allocation->ip_alias : $allocation->ip; // @phpstan-ignore property.notFound
        }

        $subdomainTarget = $server->node->subdomain_target; // @phpstan-ignore property.notFound
        $srvServiceType = SRVServiceType::fromServer($server);

        $node_id = $server->node->id;

        // General restrictions checks

        if (!($domain->nodes->isEmpty() || $domain->nodes()->where('nodes.id', $node_id)->exists())) {
            $errors->add('Domain ' . $domain->nameWithPrefix() . ' is not permitted on node ' . $server->node->name);
        }

        if (!($domain->allowed_record_types->isEmpty() || $domain->allowed_record_types->contains($this))) {
            $errors->add('Record type ' . $this->value . ' is not permitted on domain ' . $domain->nameWithPrefix());
        }

        // Allocation checks

        if (in_array($this, [self::A, self::AAAA, self::SRV]) && !$allocation) {
            $errors->add('Server has no allocation');
        }

        if (in_array($targetAddress, ['0.0.0.0', '::'])) {
            $errors->add('Allocation target address is invalid (0.0.0.0 or ::)');
        }

        if ($this == self::A && !is_ipv4($targetAddress)) {
            $errors->add('Allocation target address ' . $targetAddress . ' is not a valid IPv4 address');
        }

        if ($this == self::AAAA && !is_ipv6($targetAddress)) {
            $errors->add('Allocation target address ' . $targetAddress . ' is not a valid IPv6 address');
        }

        // Subdomain target checks

        if (in_array($this, [self::CNAME, self::SRV]) && !$subdomainTarget) {
            $errors->add('Server has no Subdomain target');
        }

        // Other checks

        if ($this == self::SRV && !$srvServiceType) {
            $errors->add('Server has no SRV service type');
        }

        return $errors;
    }
}
