<?php

namespace Boy132\Subdomains\Services;

use Boy132\Subdomains\Models\Subdomain;
use Exception;

class SubdomainService
{
    /**
     * @param  array<mixed>  $data
     *
     * @throws Exception
     */
    public function handle(array $data, ?Subdomain $subdomain = null): Subdomain
    {
        $newSubdomain = true;
        $originalAttributes = [];

        if (is_null($subdomain)) {
            $subdomain = Subdomain::create($data);
        } else {
            $originalAttributes = $subdomain->getRawOriginal();
            $subdomain->update($data);
            $newSubdomain = false;
        }

        $subdomain->refresh();

        try {
            $subdomain->upsertOnCloudflare();
        } catch (Exception $exception) {
            if ($newSubdomain) {
                $subdomain->delete();
            } else {
                $subdomain->setRawAttributes($originalAttributes);
                $subdomain->saveQuietly();
            }

            throw $exception;
        }

        return $subdomain;
    }
}
