<?php

namespace Boy132\Subdomains\Filament\Admin\Resources\SubdomainTargets;

use App\Filament\Admin\Resources\Nodes\Pages\EditNode;
use App\Models\Node;
use Boy132\Subdomains\Filament\Admin\Resources\SubdomainTargets\Pages\ManageSubdomainTargets;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class SubdomainTargetResource extends Resource
{
    protected static ?string $model = Node::class;

    protected static ?string $slug = 'subdomain-targets';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-world-www';

    public static function getModelLabel(): string
    {
        return trans('subdomains::strings.subdomain_target');
    }

    public static function getNavigationGroup(): ?string
    {
        return trans_choice('subdomains::strings.subdomain', 2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(trans('admin/node.table.name'))
                    ->url(fn (Node $node) => user()?->can('update', $node) ? EditNode::getUrl(['record' => $node]) : null),
                TextColumn::make('fqdn')
                    ->label(trans('admin/node.table.address')),
                TextInputColumn::make('subdomain_target')
                    ->label(trans('subdomains::strings.subdomain_target'))
                    ->placeholder(trans('subdomains::strings.no_subdomain_target'))
                    ->updateStateUsing(function (Node $node, $state) {
                        $node->forceFill([
                            'subdomain_target' => $state,
                        ])->save();
                    }),
                ToggleColumn::make('subdomain_use_alias')
                    ->label(trans('subdomains::strings.use_allocation_alias'))
                    ->updateStateUsing(function (Node $node, $state) {
                        $node->forceFill([
                            'subdomain_use_alias' => $state,
                        ])->save();
                    }),
            ])
            ->emptyStateIcon('tabler-world-www')
            ->emptyStateDescription('')
            ->emptyStateHeading(trans('admin/node.no_nodes'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSubdomainTargets::route('/'),
        ];
    }
}
