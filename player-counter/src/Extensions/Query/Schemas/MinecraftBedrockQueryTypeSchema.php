<?php

namespace Boy132\PlayerCounter\Extensions\Query\Schemas;

use App\Models\Server;
use Boy132\PlayerCounter\Extensions\Query\QueryTypeSchemaInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use xPaw\MinecraftQuery;

class MinecraftBedrockQueryTypeSchema implements QueryTypeSchemaInterface
{
    public function getId(): string
    {
        return 'minecraft_bedrock';
    }

    public function getName(): string
    {
        return 'Minecraft (Bedrock)';
    }

    /** @return ?array{hostname: string, map: string, current_players: int, max_players: int, players: ?array<array{id: string, name: string}>} */
    public function process(Server $server, string $ip, int $port): ?array
    {
        return $this->tryQueryNetherNet($ip, $port) ?? $this->tryQueryRakNet($ip, $port);
    }

    /** @return ?array{hostname: string, map: string, current_players: int, max_players: int, players: null} */
    private function tryQueryNetherNet(string $ip, int $port): ?array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout(2)
                ->timeout(5)
                ->withoutRedirecting()
                ->get("http://{$ip}:{$port}/v1/join");

            if (!$response->successful()) {
                return null;
            }

            $info = $response->json();

            if (
                !is_array($info)
                || !isset(
                    $info['name'],
                    $info['level'],
                    $info['players'],
                    $info['maxPlayers'],
                )
            ) {
                return null;
            }

            return [
                'hostname' => $info['name'],
                'map' => $info['level'],
                'current_players' => $info['players'],
                'max_players' => $info['maxPlayers'],
                'players' => null, // Bedrock has no player list
            ];
        } catch (Exception) {
            return null; // Either invalid or server may use RakNet
        }
    }

    /** @return ?array{hostname: string, map: string, current_players: int, max_players: int, players: null} */
    private function tryQueryRakNet(string $ip, int $port): ?array
    {
        $query = new MinecraftQuery();

        try {
            $query->ConnectBedrock($ip, $port, 5, true);
            $info = $query->GetInfo();

            if (!is_array($info)) {
                return null;
            }

            return [
                'hostname' => $info['HostName'],
                'map' => $info['Map'],
                'current_players' => $info['Players'],
                'max_players' => $info['MaxPlayers'],
                'players' => null, // Bedrock has no player list
            ];
        } catch (Exception $exception) {
            report($exception);

            return null;
        }
    }
}
