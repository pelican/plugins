<?php

namespace Boy132\Subdomains\Models;

use App\Models\Server;
use Boy132\Subdomains\Enums\SRVServiceType;
use Exception;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;

/**
 * @property int $id
 * @property string $name
 * @property string $record_type
 * @property ?string $cloudflare_id
 * @property int $domain_id
 * @property CloudflareDomain $domain
 * @property int $server_id
 * @property Server $server
 */
class Subdomain extends Model implements HasLabel
{
    protected $fillable = [
        'name',
        'record_type',
        'cloudflare_id',
        'domain_id',
        'server_id',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::deleted(function (self $model) {
            $model->deleteOnCloudflare();
        });
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(CloudflareDomain::class, 'domain_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->name . '.' . $this->domain->nameWithPrefix();
    }

    /** @throws Exception */
    public function upsertOnCloudflare(): void
    {
        if (!$this->server->allocation) {
            throw new Exception('Server has no allocation');
        }

        if ($this->record_type === 'SRV') {
            $srvTarget = $this->server->node->srv_target; // @phpstan-ignore property.notFound

            if (!$srvTarget) {
                throw new Exception('Node has no SRV target');
            }

            $srvServiceType = SRVServiceType::fromServer($this->server);

            if (!$srvServiceType) {
                throw new Exception('Server has no SRV type');
            }

            $searchName = $this->domain->prependPrefix("$srvServiceType->value.$this->name");

            $payload = [
                'name' => $searchName,
                'type' => $this->record_type,
                'comment' => 'Created by Pelican Subdomains plugin',
                'data' => [
                    'port' => $this->server->allocation->port,
                    'priority' => 0,
                    'target' => $srvTarget,
                    'weight' => 0,
                ],
                'proxied' => false,
            ];
        } else {
            if (in_array($this->server->allocation->ip, ['0.0.0.0', '::'])) {
                throw new Exception('Server has invalid allocation ip (0.0.0.0 or ::)');
            }

            $searchName = $this->domain->prependPrefix($this->name);

            $payload = [
                'name' => $searchName,
                'type' => $this->record_type,
                'comment' => 'Created by Pelican Subdomains plugin',
                'content' => $this->server->allocation->ip,
                'proxied' => false,
            ];
        }

        // @phpstan-ignore staticMethod.notFound
        $searchResponse = Http::cloudflare()->get("zones/{$this->domain->cloudflare_id}/dns_records", [
            'name' => $searchName,
            'type' => $this->record_type,
        ])->json();

        if ($searchResponse['success']) {
            $results = $searchResponse['result'] ?? [];

            foreach ($results as $record) {
                if ($record['id'] !== $this->cloudflare_id) {
                    throw new Exception('A subdomain with that name already exists');
                }
            }
        } else {
            if ($searchResponse['errors'] && count($searchResponse['errors']) > 0) {
                throw new Exception($searchResponse['errors'][0]['message']);
            }
        }

        if ($this->cloudflare_id) {
            // @phpstan-ignore staticMethod.notFound
            $response = Http::cloudflare()->patch("zones/{$this->domain->cloudflare_id}/dns_records/$this->cloudflare_id", $payload)->json();
        } else {
            // @phpstan-ignore staticMethod.notFound
            $response = Http::cloudflare()->post("zones/{$this->domain->cloudflare_id}/dns_records", $payload)->json();

            if ($response['success']) {
                $dnsRecord = $response['result'];

                $this->updateQuietly([
                    'cloudflare_id' => $dnsRecord['id'],
                ]);
            }
        }

        if (!$response['success']) {
            if ($response['errors'] && count($response['errors']) > 0) {
                throw new Exception($response['errors'][0]['message']);
            }
        }
    }

    protected function deleteOnCloudflare(): void
    {
        if ($this->cloudflare_id) {
            // @phpstan-ignore staticMethod.notFound
            Http::cloudflare()->delete("zones/{$this->domain->cloudflare_id}/dns_records/$this->cloudflare_id");
        }
    }
}
