<?php

namespace Boy132\MinecraftModrinth\Filament\Server\Pages;

use App\Filament\Server\Resources\Files\Pages\ListFiles;
use App\Models\Server;
use App\Traits\Filament\BlockAccessInConflict;
use Boy132\MinecraftModrinth\Enums\ModrinthProjectType;
use Boy132\MinecraftModrinth\Facades\MinecraftModrinth;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

abstract class MinecraftModrinthProjectPage extends Page implements HasTable
{
    use BlockAccessInConflict;
    use HasTabs;
    use InteractsWithTable;

    /** @var array<int, array{project_id: string, project_slug: string, project_title: string, version_id: string, version_number: string, filename: string, installed_at: string, author?: string}>|null */
    protected ?array $installedModsMetadata = null;

    /** @var array<string, array<int, mixed>> Cache for version data by project_id */
    protected array $versionsCache = [];

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-packages';

    protected static ?string $slug = 'modrinth';

    protected static ?int $navigationSort = 30;

    protected static ?ModrinthProjectType $modrinthProjectType = null;

    public static function canAccess(): bool
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return parent::canAccess() && static::$modrinthProjectType && in_array(static::$modrinthProjectType, ModrinthProjectType::fromServer($server));
    }

    public static function getNavigationLabel(): string
    {
        return static::$modrinthProjectType?->getLabel() ?? 'Modrinth';
    }

    public static function getModelLabel(): string
    {
        return static::getNavigationLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return static::getNavigationLabel();
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(trans('minecraft-modrinth::strings.page.view_all')),
            'installed' => Tab::make(trans('minecraft-modrinth::strings.page.view_installed')),
        ];
    }

    /** @return array<int, array{project_id: string, project_slug: string, project_title: string, version_id: string, version_number: string, filename: string, installed_at: string, author?: string}> */
    protected function getInstalledModsMetadata(): array
    {
        if ($this->installedModsMetadata === null) {
            /** @var Server $server */
            $server = Filament::getTenant();

            $this->installedModsMetadata = MinecraftModrinth::getInstalledModsMetadata($server, static::$modrinthProjectType);
        }

        return $this->installedModsMetadata;
    }

    /** @return array{project_id: string, project_slug: string, project_title: string, version_id: string, version_number: string, filename: string, installed_at: string, author?: string}|null */
    protected function getInstalledMod(string $projectId): ?array
    {
        $installedMods = $this->getInstalledModsMetadata();

        foreach ($installedMods as $mod) {
            if ($mod['project_id'] === $projectId) {
                return $mod;
            }
        }

        return null;
    }

    /** @return array<int, mixed> */
    protected function getCachedVersions(string $projectId): array
    {
        if (!isset($this->versionsCache[$projectId])) {
            /** @var Server $server */
            $server = Filament::getTenant();
            $this->versionsCache[$projectId] = MinecraftModrinth::getProjectVersions($projectId, $server);
        }

        return $this->versionsCache[$projectId];
    }

    /**
     * Fetch the versions of every project on the current page up front.
     *
     * Each installed row asks for its versions to decide between the "installed" and "update"
     * action, so without this the installed tab does one Modrinth request per row, one after
     * the other, and a full page can take longer than the request is allowed to run.
     *
     * @param  array<int|string, string>  $projectIds
     */
    protected function primeVersionsCache(array $projectIds): void
    {
        $projectIds = array_values(array_diff(array_unique(array_filter($projectIds)), array_keys($this->versionsCache)));

        if (count($projectIds) < 2) {
            return;
        }

        /** @var Server $server */
        $server = Filament::getTenant();

        $this->versionsCache += MinecraftModrinth::getProjectVersionsBulk($projectIds, $server);
    }

    /**
     * Drop everything read from the server so the next render reflects what is actually on disk.
     */
    protected function forgetInstalledState(bool $refresh = true): void
    {
        $this->installedModsMetadata = null;
        $this->versionsCache = [];

        if ($refresh) {
            $this->js('$wire.$refresh()');
        }
    }

    /**
     * @param  array<int, array{primary: bool, filename: string, url: string}>  $files
     * @return array{primary: bool, filename: string, url: string}|null
     */
    protected function getPrimaryFile(array $files): ?array
    {
        foreach ($files as $file) {
            if (!empty($file['primary'])) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    protected function validateFilename(string $filename): string
    {
        if ($filename === '' || $filename === '.' || str_contains($filename, "\0") || str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new Exception('Invalid filename: potential path traversal detected');
        }

        return basename($filename);
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $versionData
     * @param  array<string, mixed>  $primaryFile
     * @param  array<string, mixed>|null  $installedMod
     *
     * @throws Exception
     */
    private function performInstallOrUpdate(
        Server $server,
        array $record,
        array $versionData,
        array $primaryFile,
        ?array $installedMod = null
    ): void {
        $safeNewFilename = $this->validateFilename($primaryFile['filename']);
        $oldFilename = $installedMod ? $this->validateFilename($installedMod['filename']) : null;

        $folder = static::$modrinthProjectType->getFolder();

        MinecraftModrinth::downloadFile($server, $primaryFile['url'], $folder, $safeNewFilename);

        $saved = MinecraftModrinth::saveModMetadata(
            $server,
            static::$modrinthProjectType,
            $record['project_id'],
            $record['slug'],
            $record['title'],
            $versionData['id'],
            $versionData['version_number'],
            $safeNewFilename,
            $record['author'] ?? null
        );

        if (!$saved) {
            if (!$oldFilename || $oldFilename !== $safeNewFilename) {
                try {
                    MinecraftModrinth::deleteFile($server, $folder, $safeNewFilename);
                } catch (Exception $rollbackException) {
                    report($rollbackException);
                }
            }

            throw new Exception('Failed to save mod metadata');
        }

        if ($oldFilename && $oldFilename !== $safeNewFilename) {
            try {
                MinecraftModrinth::deleteFile($server, $folder, $oldFilename);
            } catch (Exception $deleteException) {
                try {
                    MinecraftModrinth::deleteFile($server, $folder, $safeNewFilename);
                } catch (Exception $rollbackException) {
                    report($rollbackException);
                }

                if ($installedMod && !MinecraftModrinth::saveModMetadata(
                    $server,
                    static::$modrinthProjectType,
                    $record['project_id'],
                    $installedMod['project_slug'],
                    $installedMod['project_title'],
                    $installedMod['version_id'],
                    $installedMod['version_number'],
                    $oldFilename,
                    $installedMod['author'] ?? null
                )) {
                    report(new Exception('Failed to restore old mod metadata during rollback'));
                }

                throw $deleteException;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->records(function (?string $search, int $page) {
                /** @var Server $server */
                $server = Filament::getTenant();

                if ($this->activeTab === 'installed') {
                    $installedMods = $this->getInstalledModsMetadata();

                    if ($search) {
                        $searchLower = strtolower($search);
                        $installedMods = array_values(array_filter($installedMods, function (array $mod) use ($searchLower) {
                            return str_contains(strtolower($mod['project_title']), $searchLower)
                                || str_contains(strtolower($mod['project_slug']), $searchLower);
                        }));
                    }

                    // Sort by title so a row keeps its place instead of moving around whenever its
                    // metadata entry is rewritten by an install, update or failed update.
                    usort($installedMods, fn (array $a, array $b) => strcasecmp($a['project_title'], $b['project_title']));

                    $projects = MinecraftModrinth::getInstalledModsFromModrinth($installedMods, $page);

                    $this->primeVersionsCache(array_column($projects, 'project_id'));

                    return new LengthAwarePaginator($projects, count($installedMods), 20, $page);
                } else {
                    $response = MinecraftModrinth::getProjects($server, static::$modrinthProjectType, $page, $search);

                    $installedIds = array_column($this->getInstalledModsMetadata(), 'project_id');
                    $this->primeVersionsCache(array_intersect(array_column($response['hits'], 'project_id'), $installedIds));

                    return new LengthAwarePaginator($response['hits'], $response['total_hits'], 20, $page);
                }
            })
            ->paginated([20])
            ->columns([
                ImageColumn::make('icon_url')
                    ->label(''),
                TextColumn::make('title')
                    ->searchable()
                    ->description(fn (array $record) => (strlen($record['description']) > 120) ? substr($record['description'], 0, 120).'...' : $record['description']),
                TextColumn::make('author')
                    ->url(fn ($state) => "https://modrinth.com/user/$state", true)
                    ->toggleable(),
                TextColumn::make('downloads')
                    ->icon('tabler-download')
                    ->numeric()
                    ->toggleable(),
                TextColumn::make('date_modified')
                    ->icon('tabler-calendar')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state, 'UTC')->diffForHumans() : '')
                    ->tooltip(fn ($state) => $state ? Carbon::parse($state, 'UTC')->timezone(user()->timezone ?? 'UTC')->format($table->getDefaultDateTimeDisplayFormat()) : '')
                    ->toggleable(),
            ])
            ->recordUrl(function (array $record) {
                if (!empty($record['unavailable'])) {
                    return null;
                }

                return "https://modrinth.com/{$record['project_type']}/{$record['slug']}";
            }, true)
            ->recordActions([
                Action::make('versions')
                    ->iconButton()
                    ->icon('tabler-list')
                    ->color('info')
                    ->tooltip(trans('minecraft-modrinth::strings.actions.versions'))
                    ->visible(fn (array $record) => empty($record['unavailable']))
                    ->modalSubmitAction(false)
                    ->schema(function (array $record) {
                        $versions = $this->getCachedVersions($record['project_id']);

                        $installedMod = $this->getInstalledMod($record['project_id']);
                        $installedVersionId = $installedMod['version_id'] ?? null;

                        $sections = [];
                        foreach ($versions as $versionIndex => $versionData) {
                            $primaryFile = $this->getPrimaryFile($versionData['files'] ?? []);

                            $sectionComponents = [
                                TextEntry::make('type_' . $versionIndex)
                                    ->label(trans('minecraft-modrinth::strings.version.type'))
                                    ->state($versionData['version_type'] ?? '')
                                    ->badge()
                                    ->color(match ($versionData['version_type'] ?? '') {
                                        'release' => 'success',
                                        'beta' => 'warning',
                                        'alpha' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('downloads_' . $versionIndex)
                                    ->label(trans('minecraft-modrinth::strings.version.downloads'))
                                    ->state($versionData['downloads'] ?? 0)
                                    ->icon('tabler-download')
                                    ->numeric(),
                                TextEntry::make('published_' . $versionIndex)
                                    ->label(trans('minecraft-modrinth::strings.version.published'))
                                    ->state(fn () => isset($versionData['date_published']) ? Carbon::parse($versionData['date_published'], 'UTC')->diffForHumans() : ''),
                            ];

                            if (!empty($versionData['changelog'])) {
                                $sectionComponents[] = TextEntry::make('changelog_' . $versionIndex)
                                    ->label(trans('minecraft-modrinth::strings.version.changelog'))
                                    ->state($versionData['changelog'])
                                    ->markdown();
                            }

                            if (($versionData['id'] ?? null) === $installedVersionId) {
                                $headerAction = Action::make('installed_' . $versionIndex)
                                    ->label(trans('minecraft-modrinth::strings.actions.installed'))
                                    ->icon('tabler-check')
                                    ->color('success')
                                    ->disabled();
                                $sectionIcon = 'tabler-check';
                                $sectionIconColor = 'success';
                            } else {
                                $headerAction = Action::make('install_version_' . $versionIndex)
                                    ->label(trans('minecraft-modrinth::strings.actions.install'))
                                    ->icon('tabler-download')
                                    ->visible($primaryFile !== null)
                                    ->action(function () use ($record, $versionData, $primaryFile) {
                                        try {
                                            /** @var Server $server */
                                            $server = Filament::getTenant();

                                            if (!$primaryFile) {
                                                throw new Exception('No downloadable file found');
                                            }

                                            $installedMod = $this->getInstalledMod($record['project_id']);

                                            $this->performInstallOrUpdate($server, $record, $versionData, $primaryFile, $installedMod);

                                            $this->forgetInstalledState();

                                            Notification::make()
                                                ->title(trans('minecraft-modrinth::strings.notifications.install_success'))
                                                ->body(trans('minecraft-modrinth::strings.notifications.install_success_body', [
                                                    'name' => $record['title'],
                                                    'version' => $versionData['version_number'],
                                                ]))
                                                ->success()
                                                ->send();
                                        } catch (Exception $exception) {
                                            report($exception);

                                            $this->forgetInstalledState();

                                            Notification::make()
                                                ->title(trans('minecraft-modrinth::strings.notifications.install_failed'))
                                                ->body(trans('minecraft-modrinth::strings.notifications.install_failed_body'))
                                                ->danger()
                                                ->send();
                                        }
                                    });
                                $sectionIcon = null;
                                $sectionIconColor = null;
                            }

                            $section = Section::make($versionData['version_number'] ?? '')
                                ->headerActions([$headerAction])
                                ->schema($sectionComponents)
                                ->collapsible()
                                ->collapsed(!($versionData['featured'] ?? false));

                            if ($sectionIcon !== null) {
                                $section = $section->icon($sectionIcon)->iconColor($sectionIconColor);
                            }

                            $sections[] = $section;
                        }

                        return $sections;
                    }),
                Action::make('install_latest')
                    ->iconButton()
                    ->icon('tabler-download')
                    ->color('success')
                    ->tooltip(trans('minecraft-modrinth::strings.actions.install_latest'))
                    ->visible(function (array $record) {
                        $installedMod = $this->getInstalledMod($record['project_id']);

                        return is_null($installedMod);
                    })
                    ->action(function (array $record) {
                        try {
                            /** @var Server $server */
                            $server = Filament::getTenant();

                            $versions = MinecraftModrinth::getProjectVersions($record['project_id'], $server);

                            if (empty($versions)) {
                                throw new Exception('No compatible versions found');
                            }

                            $latestVersion = $versions[0];

                            $primaryFile = $this->getPrimaryFile($latestVersion['files']);

                            if (!$primaryFile) {
                                throw new Exception('No downloadable file found');
                            }

                            $this->performInstallOrUpdate($server, $record, $latestVersion, $primaryFile);

                            $this->forgetInstalledState();

                            Notification::make()
                                ->title(trans('minecraft-modrinth::strings.notifications.install_success'))
                                ->body(trans('minecraft-modrinth::strings.notifications.install_success_body', [
                                    'name' => $record['title'],
                                    'version' => $latestVersion['version_number'],
                                ]))
                                ->success()
                                ->send();
                        } catch (Exception $exception) {
                            report($exception);

                            $this->forgetInstalledState();

                            Notification::make()
                                ->title(trans('minecraft-modrinth::strings.notifications.install_failed'))
                                ->body(trans('minecraft-modrinth::strings.notifications.install_failed_body'))
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('update')
                    ->iconButton()
                    ->icon('tabler-refresh')
                    ->color('warning')
                    ->tooltip(trans('minecraft-modrinth::strings.actions.update'))
                    ->visible(function (array $record) {
                        $installedMod = $this->getInstalledMod($record['project_id']);

                        if (is_null($installedMod)) {
                            return false;
                        }

                        $versions = $this->getCachedVersions($record['project_id']);

                        if (empty($versions)) {
                            return false;
                        }

                        return $installedMod['version_id'] !== $versions[0]['id'];
                    })
                    ->requiresConfirmation()
                    ->modalHeading(trans('minecraft-modrinth::strings.modals.update_heading'))
                    ->modalDescription(function (array $record) {
                        $installedMod = $this->getInstalledMod($record['project_id']);
                        $versions = $this->getCachedVersions($record['project_id']);

                        return trans('minecraft-modrinth::strings.modals.update_description', [
                            'old_version' => $installedMod['version_number'] ?? 'unknown',
                            'new_version' => $versions[0]['version_number'] ?? 'unknown',
                        ]);
                    })
                    ->action(function (array $record) {
                        try {
                            /** @var Server $server */
                            $server = Filament::getTenant();

                            $installedMod = $this->getInstalledMod($record['project_id']);

                            if (!$installedMod) {
                                throw new Exception('Mod not found in metadata');
                            }

                            $versions = MinecraftModrinth::getProjectVersions($record['project_id'], $server);

                            if (empty($versions)) {
                                throw new Exception('No compatible versions found');
                            }

                            $latestVersion = $versions[0];

                            $primaryFile = $this->getPrimaryFile($latestVersion['files']);

                            if (!$primaryFile) {
                                throw new Exception('No downloadable file found');
                            }

                            $this->performInstallOrUpdate($server, $record, $latestVersion, $primaryFile, $installedMod);

                            $this->forgetInstalledState();

                            Notification::make()
                                ->title(trans('minecraft-modrinth::strings.notifications.update_success'))
                                ->body(trans('minecraft-modrinth::strings.notifications.update_success_body', [
                                    'version' => $latestVersion['version_number'],
                                ]))
                                ->success()
                                ->send();
                        } catch (Exception $exception) {
                            report($exception);

                            $this->forgetInstalledState();

                            Notification::make()
                                ->title(trans('minecraft-modrinth::strings.notifications.update_failed'))
                                ->body(trans('minecraft-modrinth::strings.notifications.update_failed_body'))
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('installed')
                    ->iconButton()
                    ->icon('tabler-check')
                    ->color('success')
                    ->tooltip(trans('minecraft-modrinth::strings.actions.installed'))
                    ->disabled()
                    ->visible(function (array $record) {
                        $installedMod = $this->getInstalledMod($record['project_id']);

                        if (is_null($installedMod)) {
                            return false;
                        }

                        $versions = $this->getCachedVersions($record['project_id']);

                        if (empty($versions)) {
                            return true;
                        }

                        return $installedMod['version_id'] === $versions[0]['id'];
                    }),
                Action::make('uninstall')
                    ->iconButton()
                    ->icon('tabler-trash')
                    ->color('danger')
                    ->tooltip(trans('minecraft-modrinth::strings.actions.uninstall'))
                    ->visible(function (array $record) {
                        return !is_null($this->getInstalledMod($record['project_id']));
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (array $record) => trans('minecraft-modrinth::strings.modals.uninstall_heading'))
                    ->modalDescription(fn (array $record) => trans('minecraft-modrinth::strings.modals.uninstall_description', ['name' => $record['title']]))
                    ->action(function (array $record) {
                        try {
                            /** @var Server $server */
                            $server = Filament::getTenant();

                            $installedMod = $this->getInstalledMod($record['project_id']);

                            if (!$installedMod) {
                                throw new Exception('Mod not found in metadata');
                            }

                            $safeFilename = $this->validateFilename($installedMod['filename']);

                            $folder = static::$modrinthProjectType->getFolder();

                            MinecraftModrinth::deleteFile($server, $folder, $safeFilename);

                            $metadataRemoved = MinecraftModrinth::removeModMetadata($server, static::$modrinthProjectType, $record['project_id']);

                            $this->forgetInstalledState();

                            if (!$metadataRemoved) {
                                Log::warning('Failed to remove mod metadata after successful file deletion', [
                                    'project_id' => $record['project_id'],
                                    'server_id' => $server->id,
                                ]);

                                // The jar is gone but the metadata file still lists it, so the row
                                // stays. Say so instead of reporting a clean uninstall.
                                Notification::make()
                                    ->title(trans('minecraft-modrinth::strings.notifications.uninstall_partial'))
                                    ->body(trans('minecraft-modrinth::strings.notifications.uninstall_partial_body', [
                                        'name' => $record['title'],
                                    ]))
                                    ->warning()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title(trans('minecraft-modrinth::strings.notifications.uninstall_success'))
                                ->body(trans('minecraft-modrinth::strings.notifications.uninstall_success_body', [
                                    'name' => $record['title'],
                                ]))
                                ->success()
                                ->send();
                        } catch (Exception $exception) {
                            report($exception);

                            $this->forgetInstalledState();

                            Notification::make()
                                ->title(trans('minecraft-modrinth::strings.notifications.uninstall_failed'))
                                ->body(trans('minecraft-modrinth::strings.notifications.uninstall_failed_body'))
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        if (!static::$modrinthProjectType) {
            return [];
        }

        $folder = static::$modrinthProjectType->getFolder();

        return [
            Action::make('open_folder')
                ->tooltip(fn () => trans('minecraft-modrinth::strings.page.open_folder', ['folder' => $folder]))
                ->icon('tabler-folder-open')
                ->url(fn () => ListFiles::getUrl(['path' => $folder]), true),
        ];
    }

    public function content(Schema $schema): Schema
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('Minecraft Version')
                            ->state(fn () => MinecraftModrinth::getMinecraftVersion($server) ?? trans('minecraft-modrinth::strings.page.unknown'))
                            ->badge(),
                        TextEntry::make('Loader')
                            ->state(fn () => MinecraftModrinth::getLoaderFromServer($server)['display_name'] ?? trans('minecraft-modrinth::strings.page.unknown'))
                            ->icon(fn () => new HtmlString(MinecraftModrinth::getLoaderFromServer($server)['icon'] ?? ''))
                            ->badge(),
                        TextEntry::make('installed')
                            ->label(fn () => trans('minecraft-modrinth::strings.page.installed', ['type' => static::$modrinthProjectType?->getLabel() ?? 'Modrinth']))
                            ->state(function () use ($server) {
                                try {
                                    return collect(MinecraftModrinth::listFolder($server, static::$modrinthProjectType->getFolder()))
                                        ->filter(fn ($file) => ($file['mime'] ?? null) === 'application/jar' || str($file['name'] ?? '')->lower()->endsWith('.jar'))
                                        ->count();
                                } catch (Exception $exception) {
                                    report($exception);

                                    // Don't pass off an unreachable daemon as an empty folder.
                                    return trans('minecraft-modrinth::strings.page.unknown');
                                }
                            })
                            ->badge(),
                    ]),
                $this->getTabsContentComponent(),
                EmbeddedTable::make(),
            ]);
    }
}
