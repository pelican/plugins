<?php

namespace Boy132\MinecraftModrinth\Services;

use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use Boy132\MinecraftModrinth\Enums\ModrinthProjectType;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MinecraftModrinthService
{
    // Seconds to wait for the daemon while it downloads a file for us.
    protected const DOWNLOAD_TIMEOUT = 120;

    public function getMinecraftVersion(Server $server): ?string
    {
        $version = $server->variables()->where(fn ($builder) => $builder->where('env_variable', 'MINECRAFT_VERSION')->orWhere('env_variable', 'MC_VERSION'))->first()?->server_value;

        if (!$version || $version === 'latest') {
            return $this->getLatestMinecraftVersion();
        }

        return $version;
    }

    public function getLatestMinecraftVersion(): ?string
    {
        return cache()->remember('modrinth:latest_minecraft_version', now()->addHour(), function () {
            try {
                /** @var array<int, mixed> $versions */
                $versions = Http::asJson()
                    ->timeout(5)
                    ->connectTimeout(5)
                    ->throw()
                    ->get('https://api.modrinth.com/v2/tag/game_version')
                    ->json();

                return collect($versions)->filter(fn ($version) => $version['version_type'] === 'release')->first()['version'] ?? null;
            } catch (Exception $exception) {
                report($exception);

                return null;
            }
        });
    }

    /** @return array{icon: string, name: string, supported_project_types: string[], display_name: string}|null */
    public function getLoaderFromServer(Server $server): ?array
    {
        $server->loadMissing('egg');

        $tags = $server->egg->tags ?? [];

        if (!in_array('minecraft', $tags)) {
            return null;
        }

        $projectTypes = array_map(fn (ModrinthProjectType $projectType) => $projectType->value, ModrinthProjectType::fromServer($server));
        if (empty($projectTypes)) {
            return null;
        }

        $loaders = $this->getLoaders();
        foreach ($loaders as $loader) {
            if (!array_intersect($loader['supported_project_types'], $projectTypes)) {
                continue;
            }

            if (in_array($loader['name'], $tags)) {
                return array_merge($loader, ['display_name' => str($loader['name'])->title()->toString()]);
            }
        }

        return null;
    }

    /** @return array<int, array{icon: string, name: string, supported_project_types: string[]}> */
    public function getLoaders(): array
    {
        return cache()->remember('modrinth:loaders', now()->addHour(), function () {
            try {
                return Http::asJson()
                    ->timeout(5)
                    ->connectTimeout(5)
                    ->throw()
                    ->get('https://api.modrinth.com/v2/tag/loader')
                    ->json();
            } catch (Exception $exception) {
                report($exception);

                return [];
            }
        });
    }

    /** @return array{hits: array<int, array<string, mixed>>, total_hits: int} */
    public function getProjects(Server $server, ModrinthProjectType $modrinthProjectType, int $page = 1, ?string $search = null): array
    {
        $modrinthProjectType = $modrinthProjectType->value;
        $minecraftLoader = $this->getLoaderFromServer($server);

        if (!$minecraftLoader) {
            return [
                'hits' => [],
                'total_hits' => 0,
            ];
        }

        $minecraftVersion = $this->getMinecraftVersion($server);
        $minecraftLoader = $minecraftLoader['name'];

        $data = [
            'offset' => ($page - 1) * 20,
            'limit' => 20,
            'facets' => "[[\"categories:$minecraftLoader\"],[\"versions:$minecraftVersion\"],[\"project_type:{$modrinthProjectType}\"]]",
        ];

        $key = "modrinth_projects:{$modrinthProjectType}:$minecraftVersion:$minecraftLoader:$page";

        if ($search) {
            $data['query'] = $search;

            $key .= ':'.md5($search);
        }

        $cached = cache()->get($key);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::asJson()
                ->timeout(5)
                ->connectTimeout(5)
                ->throw()
                ->get('https://api.modrinth.com/v2/search', $data)
                ->json();
        } catch (Exception $exception) {
            report($exception);

            // not caching on purpose since caching a failure would keep the page empty for 30 minutes
            return [
                'hits' => [],
                'total_hits' => 0,
            ];
        }

        cache()->put($key, $response, now()->addMinutes(30));

        return $response;
    }

    /**
     * @param  array<int, array{project_id: string, project_slug: string, project_title: string, version_id: string, version_number: string, filename: string, installed_at: string, author?: string}>  $installedMods
     * @return array<int, array<string, mixed>>
     */
    public function getInstalledModsFromModrinth(array $installedMods, int $page = 1): array
    {
        if (empty($installedMods)) {
            return [];
        }

        $installedModsById = [];
        foreach ($installedMods as $mod) {
            if (!isset($installedModsById[$mod['project_id']])) {
                $installedModsById[$mod['project_id']] = $mod;
            }
        }

        $projectIds = array_keys($installedModsById);

        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $pageIds = array_slice($projectIds, $offset, $perPage);

        if (empty($pageIds)) {
            return [];
        }

        $idsParam = json_encode($pageIds, JSON_THROW_ON_ERROR);
        $cacheKey = 'modrinth_bulk:'.md5($idsParam);

        $modrinthProjects = cache()->get($cacheKey);

        if (!is_array($modrinthProjects)) {
            try {
                $modrinthProjects = Http::asJson()
                    ->timeout(10)
                    ->connectTimeout(5)
                    ->throw()
                    ->get('https://api.modrinth.com/v2/projects', [
                        'ids' => $idsParam,
                    ])
                    ->json();

                if (!is_array($modrinthProjects)) {
                    $modrinthProjects = [];
                }

                cache()->put($cacheKey, $modrinthProjects, now()->addMinutes(30));
            } catch (Exception $exception) {
                report($exception);

                $modrinthProjects = [];
            }
        }

        $modrinthMap = [];
        foreach ($modrinthProjects as $project) {
            if (isset($project['id'])) {
                $modrinthMap[$project['id']] = $project;
            }
        }

        $results = [];
        foreach ($pageIds as $projectId) {
            $installedMod = $installedModsById[$projectId];

            if (isset($modrinthMap[$projectId])) {
                $project = $modrinthMap[$projectId];
                $project['project_id'] = $project['id'];
                if (isset($project['updated']) && !isset($project['date_modified'])) {
                    $project['date_modified'] = $project['updated'];
                }
                if (isset($installedMod['author']) && !isset($project['author'])) {
                    $project['author'] = $installedMod['author'];
                }
                $results[] = $project;
            } else {
                $results[] = [
                    'project_id' => $installedMod['project_id'],
                    'slug' => $installedMod['project_slug'],
                    'title' => $installedMod['project_title'],
                    'description' => trans('minecraft-modrinth::strings.page.mod_unavailable'),
                    'icon_url' => null,
                    'author' => $installedMod['author'] ?? '',
                    'downloads' => 0,
                    'date_modified' => $installedMod['installed_at'],
                    'project_type' => '',
                    'unavailable' => true,
                ];
            }
        }

        return $results;
    }

    protected function getVersionsCacheKey(string $projectId, ?string $minecraftVersion, string $minecraftLoader): string
    {
        return "modrinth_versions:$projectId:$minecraftVersion:$minecraftLoader";
    }

    /** @return array{game_versions: string, loaders: string} */
    protected function getVersionsQuery(?string $minecraftVersion, string $minecraftLoader): array
    {
        return [
            'game_versions' => "[\"$minecraftVersion\"]",
            'loaders' => "[\"$minecraftLoader\"]",
        ];
    }

    /**
     * @param  array<int, mixed>  $versions
     * @return array<int, mixed>
     */
    protected function sortVersions(array $versions): array
    {
        usort($versions, fn ($a, $b) => strcmp($b['date_published'] ?? '', $a['date_published'] ?? ''));

        return $versions;
    }

    /** @return array<array{name: string, version_number: string, changelog: ?string, dependencies: array<mixed>, game_version: string[], version_type: string, loaders: string[], featured: bool, status: string, requested_status: ?string, id: string, project_id: string, author_id: string, date_published: string, downloads: int, changelog_url: ?string, files: array<mixed>}> */
    public function getProjectVersions(string $projectId, Server $server): array
    {
        $minecraftLoader = $this->getLoaderFromServer($server);

        if (!$minecraftLoader) {
            return [];
        }

        $minecraftVersion = $this->getMinecraftVersion($server);
        $minecraftLoader = $minecraftLoader['name'];

        $key = $this->getVersionsCacheKey($projectId, $minecraftVersion, $minecraftLoader);

        $cached = cache()->get($key);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $versions = Http::asJson()
                ->timeout(5)
                ->connectTimeout(5)
                ->throw()
                ->get("https://api.modrinth.com/v2/project/$projectId/version", $this->getVersionsQuery($minecraftVersion, $minecraftLoader))
                ->json();
        } catch (Exception $exception) {
            report($exception);

            // not cached on purpose since an empty list would hide the update action for 30 minutes.
            return [];
        }

        $versions = is_array($versions) ? $this->sortVersions($versions) : [];

        cache()->put($key, $versions, now()->addMinutes(30));

        return $versions;
    }

    /**
     * fetch compatible versions of several projects at once, a full installed
     * tab used to cost up to 20 sequential Modrinth round trips whilst this fetches in bulk.
     *
     * @param  array<int, string>  $projectIds
     * @return array<string, array<int, mixed>>
     */
    public function getProjectVersionsBulk(array $projectIds, Server $server): array
    {
        $projectIds = array_values(array_unique(array_filter($projectIds)));

        if (empty($projectIds)) {
            return [];
        }

        $minecraftLoader = $this->getLoaderFromServer($server);

        if (!$minecraftLoader) {
            return array_fill_keys($projectIds, []);
        }

        $minecraftVersion = $this->getMinecraftVersion($server);
        $minecraftLoader = $minecraftLoader['name'];
        $query = $this->getVersionsQuery($minecraftVersion, $minecraftLoader);

        $results = [];
        $missing = [];

        foreach ($projectIds as $projectId) {
            $cached = cache()->get($this->getVersionsCacheKey($projectId, $minecraftVersion, $minecraftLoader));

            if (is_array($cached)) {
                $results[$projectId] = $cached;
            } else {
                $missing[] = $projectId;
            }
        }

        if (empty($missing)) {
            return $results;
        }

        try {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn (string $projectId) => $pool->as($projectId)
                    ->asJson()
                    ->timeout(10)
                    ->connectTimeout(5)
                    ->get("https://api.modrinth.com/v2/project/$projectId/version", $query),
                $missing
            ));
        } catch (Exception $exception) {
            report($exception);

            $responses = [];
        }

        foreach ($missing as $projectId) {
            $response = $responses[$projectId] ?? null;

            if ($response instanceof Response && $response->successful()) {
                $versions = $response->json();
                $versions = is_array($versions) ? $this->sortVersions($versions) : [];

                cache()->put($this->getVersionsCacheKey($projectId, $minecraftVersion, $minecraftLoader), $versions, now()->addMinutes(30));

                $results[$projectId] = $versions;

                continue;
            }

            if ($response instanceof Exception) {
                report($response);
            }

            $results[$projectId] = [];
        }

        return $results;
    }

    /**
     * @throws Exception
     */
    protected function getMetadataFilePath(ModrinthProjectType $modrinthProjectType): string
    {
        return join_paths($modrinthProjectType->getFolder(), '.modrinth-metadata.json');
    }

    /** @return array<int, array{project_id: string, project_slug: string, project_title: string, version_id: string, version_number: string, filename: string, installed_at: string, author?: string}> */
    public function getInstalledModsMetadata(Server $server, ModrinthProjectType $modrinthProjectType): array
    {
        try {
            $fileRepository = app(DaemonFileRepository::class);

            $metadataPath = $this->getMetadataFilePath($modrinthProjectType);
            $content = $fileRepository->setServer($server)->getContent($metadataPath);
            $metadata = json_decode($content, true);
        } catch (FileNotFoundException) {
            return [];
        } catch (Exception $exception) {
            report($exception);

            return [];
        }

        if (!is_array($metadata) || !isset($metadata['installed_mods']) || !is_array($metadata['installed_mods'])) {
            return [];
        }

        $requiredKeysFlipped = array_flip([
            'project_id',
            'project_slug',
            'project_title',
            'version_id',
            'version_number',
            'filename',
            'installed_at',
        ]);

        $validInstalledMods = [];

        foreach ($metadata['installed_mods'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (!empty(array_diff_key($requiredKeysFlipped, $entry))) {
                continue;
            }

            if (isset($validInstalledMods[$entry['project_id']])) {
                continue;
            }

            $validInstalledMods[$entry['project_id']] = $entry;
        }

        return array_values($validInstalledMods);
    }

    public function saveModMetadata(
        Server $server,
        ModrinthProjectType $modrinthProjectType,
        string $projectId,
        string $projectSlug,
        string $projectTitle,
        string $versionId,
        string $versionNumber,
        string $filename,
        ?string $author = null
    ): bool {
        try {
            return Cache::lock("modrinth_metadata:{$server->id}", 10)->block(5, function () use ($server, $modrinthProjectType, $projectId, $projectSlug, $projectTitle, $versionId, $versionNumber, $filename, $author) {
                $fileRepository = app(DaemonFileRepository::class);

                $installedMods = $this->getInstalledModsMetadata($server, $modrinthProjectType);

                $existingIndex = null;
                foreach ($installedMods as $index => $mod) {
                    if ($mod['project_id'] === $projectId) {
                        $existingIndex = $index;

                        break;
                    }
                }

                $modEntry = [
                    'project_id' => $projectId,
                    'project_slug' => $projectSlug,
                    'project_title' => $projectTitle,
                    'version_id' => $versionId,
                    'version_number' => $versionNumber,
                    'filename' => $filename,
                    'installed_at' => $existingIndex !== null
                        ? $installedMods[$existingIndex]['installed_at']
                        : now()->toIso8601String(),
                ];

                if ($existingIndex !== null) {
                    $modEntry['updated_at'] = now()->toIso8601String();
                }

                if ($author !== null) {
                    $modEntry['author'] = $author;
                }

                if ($existingIndex !== null) {
                    // Replace in place. Removing and re-appending pushed the entry to the end of the
                    // list, which moved the row to the bottom of the installed tab on every update.
                    $installedMods[$existingIndex] = $modEntry;
                } else {
                    $installedMods[] = $modEntry;
                }

                $metadataPath = $this->getMetadataFilePath($modrinthProjectType);
                $response = $fileRepository->setServer($server)->putContent(
                    $metadataPath,
                    json_encode(['installed_mods' => array_values($installedMods)], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                );

                return !$response->failed();
            }) === true;
        } catch (Exception $exception) {
            report($exception);

            return false;
        }
    }

    public function removeModMetadata(Server $server, ModrinthProjectType $modrinthProjectType, string $projectId): bool
    {
        try {
            return Cache::lock("modrinth_metadata:{$server->id}", 10)->block(5, function () use ($server, $modrinthProjectType, $projectId) {
                $fileRepository = app(DaemonFileRepository::class);

                $installedMods = collect($this->getInstalledModsMetadata($server, $modrinthProjectType))
                    ->filter(fn ($mod) => $mod['project_id'] !== $projectId)
                    ->values()
                    ->toArray();

                $metadataPath = $this->getMetadataFilePath($modrinthProjectType);
                $response = $fileRepository->setServer($server)->putContent(
                    $metadataPath,
                    json_encode(['installed_mods' => $installedMods], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                );

                return !$response->failed();
            }) === true;
        } catch (Exception $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * Ask the daemon to download a file and wait until it is actually on disk.
     *
     * Without `foreground` the daemon downloads in the background, so the panel gets a success
     * response before the file exists and never hears about a failed download. The explicit file
     * name matters too: otherwise the daemon derives it from the URL, which can differ from the
     * name Modrinth reports and would leave the metadata pointing at a file that isn't there.
     *
     * @throws Exception
     */
    public function downloadFile(Server $server, string $url, string $folder, string $filename): void
    {
        try {
            app(DaemonFileRepository::class)
                ->setServer($server)
                ->getHttpClient()
                // The daemon holds the request open for the whole download, which easily outlives
                // the timeout used for the small file operations everything else here does.
                ->timeout(max((int) config('panel.guzzle.timeout'), self::DOWNLOAD_TIMEOUT))
                ->post("/api/servers/{$server->uuid}/files/pull", [
                    'url' => $url,
                    'root' => $folder,
                    'file_name' => $filename,
                    'foreground' => true,
                ]);
        } catch (Exception $exception) {
            // A slow download can outlive our request while still finishing on the node.
            if (!$this->fileExists($server, $folder, $filename)) {
                throw $exception;
            }

            return;
        }

        if (!$this->fileExists($server, $folder, $filename)) {
            throw new Exception("Daemon reported success but $folder/$filename is missing after downloading $url");
        }
    }

    /**
     * @throws Exception
     */
    public function deleteFile(Server $server, string $folder, string $filename): void
    {
        app(DaemonFileRepository::class)
            ->setServer($server)
            ->deleteFiles('/', [join_paths($folder, $filename)])
            ->throw();
    }

    public function fileExists(Server $server, string $folder, string $filename): bool
    {
        foreach ($this->listFolder($server, $folder) as $file) {
            if (is_array($file) && ($file['name'] ?? null) === $filename) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function listFolder(Server $server, string $folder): array
    {
        try {
            $files = app(DaemonFileRepository::class)->setServer($server)->getDirectory($folder);
        } catch (Exception) {
            // The folder may simply not exist yet.
            return [];
        }

        if (isset($files['error'])) {
            return [];
        }

        return $files;
    }

    /** @return array{project_id: string, project_slug: string, project_title: string, version_id: string, version_number: string, filename: string, installed_at: string, author?: string}|null */
    public function getInstalledMod(Server $server, ModrinthProjectType $modrinthProjectType, string $projectId): ?array
    {
        $installedMods = $this->getInstalledModsMetadata($server, $modrinthProjectType);

        foreach ($installedMods as $mod) {
            if ($mod['project_id'] === $projectId) {
                return $mod;
            }
        }

        return null;
    }

    /**
     * @param  array{version_id: string, version_number: string}  $installedMod
     * @param  array<int, array{id: string, version_number: string}>  $availableVersions
     */
    public function isUpdateAvailable(array $installedMod, array $availableVersions): bool
    {
        if (empty($availableVersions)) {
            return false;
        }

        $latestVersion = $availableVersions[0];

        return $installedMod['version_id'] !== $latestVersion['id'];
    }

    /**
     * @return array<string>
     */
    public function getInstalledMods(Server $server, ModrinthProjectType $modrinthProjectType): array
    {
        $metadata = $this->getInstalledModsMetadata($server, $modrinthProjectType);

        return collect($metadata)
            ->pluck('filename')
            ->map(fn ($name) => strtolower($name))
            ->toArray();
    }
}
