<?php

namespace Boy132\Subdomains\Models;

use App\Models\Node;
use App\Models\Server;
use Boy132\Subdomains\Enums\RecordType;
use Boy132\Subdomains\Enums\SRVServiceType;
use Exception;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * @property int $id
 * @property string $name
 * @property string $prefix
 * @property ?string $cloudflare_id
 * @property Collection|Node[] $nodes
 * @property Collection<RecordType> $allowed_record_types
 */
class CloudflareDomain extends Model
{
    protected $fillable = [
        'name',
        'prefix',
        'cloudflare_id',
        'allowed_record_types',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (self $model) {
            $model->fetchCloudflareId();
        });

        static::saving(function (self $model): void {
            $model->allowed_record_types = $model->allowed_record_types->sort();
        });
    }

    protected function casts(): array
    {
        return [
            'allowed_record_types' => AsEnumCollection::of(RecordType::class),
        ];
    }

    public function subdomains(): HasMany
    {
        return $this->hasMany(Subdomain::class, 'domain_id');
    }

    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(Node::class);
    }

    public function nameWithPrefix(): string
    {
        return $this->prefix == '' ? $this->name : "$this->prefix.$this->name";
    }

    public function prependPrefix(string $subdomain): string
    {
        return $this->prefix == '' ? $subdomain : "$subdomain.$this->prefix";
    }

    /** @throws Exception */
    public function fetchCloudflareId(): void
    {
        // @phpstan-ignore staticMethod.notFound
        $response = Http::cloudflare()->get('zones', [
            'name' => $this->name,
        ])->json();

        if ($response['success']) {
            $zones = $response['result'];

            if (count($zones) > 0) {
                $this->update([
                    'cloudflare_id' => $zones[0]['id'],
                ]);
            } else {
                throw new Exception("No zone with name $this->name found.");
            }
        } else {
            if ($response['errors'] && count($response['errors']) > 0) {
                throw new Exception($response['errors'][0]['message']);
            }
        }
    }

    /**
     * @return Collection<string>
     */
    public function availableRecordTypes(Server $server): Collection
    {
        $allocation = $server->allocation;
        $subdomainTarget = $server->node->subdomain_target; // @phpstan-ignore property.notFound
        $allowedRecordTypes = $this->allowed_record_types;
        $allowedRecordsFilterDisabled = $allowedRecordTypes->isEmpty();
        $srvServiceType = SRVServiceType::fromServer($server);

        $types = new Collection();

        // Explicitly forbid ANY record creation when primary allocation is invalid
        if ($allocation && in_array($allocation->ip, ['0.0.0.0', '::'])) {
            return $types;
        }

        if (($allowedRecordsFilterDisabled || $allowedRecordTypes->contains(RecordType::A)) && $allocation && is_ipv4($allocation->ip)) {
            $types->add(RecordType::A);
        }

        if (($allowedRecordsFilterDisabled || $allowedRecordTypes->contains(RecordType::AAAA)) && $allocation && is_ipv6($allocation->ip)) {
            $types->add(RecordType::AAAA);
        }

        if (($allowedRecordsFilterDisabled || $allowedRecordTypes->contains(RecordType::CNAME)) && $subdomainTarget) {
            $types->add(RecordType::CNAME);
        }

        if (($allowedRecordsFilterDisabled || $allowedRecordTypes->contains(RecordType::SRV)) && $allocation && $subdomainTarget && $srvServiceType) {
            $types->add(RecordType::SRV);
        }

        return $types;
    }

    /**
     * @return Collection<self>
     */
    public static function availableDomains(Server $server): Collection
    {
        // Fetch all domains with this allowed node, or with no allowed nodes
        $viableDomains = CloudflareDomain::query()
            ->whereHas('nodes', fn ($query) => $query->whereKey($server->node->id))
            ->orWhereDoesntHave('nodes')
            ->get();

        $availableDomains = $viableDomains->filter(fn (self $item) => !$item->availableRecordTypes($server)->isEmpty());

        return $availableDomains;
    }
}
