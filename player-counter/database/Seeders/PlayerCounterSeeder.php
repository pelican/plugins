<?php

namespace Database\Seeders;

use App\Models\Egg;
use Boy132\PlayerCounter\Models\EggGameQuery;
use Boy132\PlayerCounter\Models\GameQuery;
use Exception;
use Illuminate\Database\Seeder;

class PlayerCounterSeeder extends Seeder
{
    public const MAPPINGS = [
        [
            'names' => 'Squad',
            'query_type' => 'source',
            'query_port_offset' => 19378,
            'query_port_variable' => null,
        ],
        [
            'names' => 'Barotrauma',
            'query_type' => 'source',
            'query_port_offset' => 1,
            'query_port_variable' => null,
        ],
        [
            'names' => 'Valheim',
            'query_type' => 'source',
            'query_port_offset' => 1,
            'query_port_variable' => null,
        ],
        [
            'names' => ['V Rising', 'V-Rising', 'VRising'],
            'query_type' => 'source',
            'query_port_offset' => 1,
            'query_port_variable' => null,
        ],
        [
            'names' => ['The Forest', 'TheForest'],
            'query_type' => 'source',
            'query_port_offset' => 1,
            'query_port_variable' => null,
        ],
        [
            'names' => ['Arma 3', 'Arma3'],
            'query_type' => 'source',
            'query_port_offset' => 1,
            'query_port_variable' => null,
        ],
        [
            'names' => ['Arma Reforger', 'ArmaReforger'],
            'query_type' => 'source',
            'query_port_offset' => 15776,
            'query_port_variable' => null,
        ],
        [
            'names' => ['ARK: Survival Evolved', 'ARK: SurvivalEvolved', 'ARK Survival Evolved', 'ARK SurvivalEvolved', 'ARKSurvivalEvolved'],
            'query_type' => 'source',
            'query_port_offset' => 19238,
            'query_port_variable' => null,
        ],
        [
            'names' => 'Unturned',
            'query_type' => 'source',
            'query_port_offset' => 1,
            'query_port_variable' => null,
        ],
        [
            'names' => ['Insurgency: Sandstorm', 'Insurgency Sandstorm', 'InsurgencySandstorm'],
            'query_type' => 'source',
            'query_port_offset' => 29,
            'query_port_variable' => null,
        ],
        [
            'names' => 'Palworld',
            'query_type' => 'palworld',
            'query_port_offset' => null,
            'query_port_variable' => null,
        ],
        [
            'tag' => 'bedrock',
            'query_type' => 'minecraft_bedrock',
            'query_port_offset' => null,
            'query_port_variable' => null,
        ],
        [
            'tag' => 'minecraft',
            'query_type' => 'minecraft_java',
            'query_port_offset' => null,
            'query_port_variable' => null,
        ],
        [
            'tag' => 'source',
            'query_type' => 'source',
            'query_port_offset' => null,
            'query_port_variable' => null,
        ],
    ];

    public function run(): void
    {
        foreach (Egg::all() as $egg) {
            $tags = $egg->tags ?? [];

            foreach (self::MAPPINGS as $mapping) {
                if ((array_key_exists('names', $mapping) && in_array($egg->name, array_wrap($mapping['names']))) || (array_key_exists('tag', $mapping) && in_array($mapping['tag'], $tags))) {
                    try {
                        $query = GameQuery::firstOrCreate([
                            'query_type' => $mapping['query_type'],
                            'query_port_offset' => $mapping['query_port_offset'],
                            'query_port_variable' => $mapping['query_port_variable'],
                        ]);

                        EggGameQuery::firstOrCreate([
                            'egg_id' => $egg->id,
                        ], [
                            'game_query_id' => $query->id,
                        ]);
                    } catch (Exception) {
                    }
                }
            }
        }

        if ($this->command) {
            $this->command->info('Created game query types for existing eggs');
        }
    }
}
