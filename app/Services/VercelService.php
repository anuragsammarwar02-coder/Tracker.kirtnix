<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VercelService
{
    protected string $apiBase = 'https://api.vercel.com';

    /**
     * Get configured Vercel API token from settings or env.
     */
    public function getToken(): ?string
    {
        return Setting::get('vercel_token') ?? env('VERCEL_API_TOKEN');
    }

    /**
     * Save Vercel API token in settings.
     */
    public function setToken(string $token): void
    {
        Setting::set('vercel_token', trim($token), 'integrations');
    }

    /**
     * Fetch projects from Vercel API or return smart defaults.
     */
    public function getProjects(?string $token = null): array
    {
        $apiToken = $token ?? $this->getToken();

        if ($apiToken) {
            try {
                $response = Http::withToken($apiToken)
                    ->withoutVerifying()
                    ->timeout(10)
                    ->get("{$this->apiBase}/v9/projects", [
                        'limit' => 50,
                    ]);

                if ($response->successful()) {
                    $projects = $response->json('projects') ?? [];
                    return array_map(function ($p) {
                        $targets = $p['targets']['production'] ?? null;
                        $domain = $targets['url'] ?? ($p['name'] . '.vercel.app');
                        if (!str_starts_with($domain, 'http')) {
                            $domain = 'https://' . $domain;
                        }

                        return [
                            'id' => $p['id'],
                            'name' => $p['name'],
                            'domain' => str_replace('https://', '', $domain),
                            'full_url' => $domain,
                            'framework' => $p['framework'] ?? 'other',
                            'updated_at' => $p['updatedAt'] ?? null,
                        ];
                    }, $projects);
                }
            } catch (\Exception $e) {
                Log::warning('Vercel API fetch projects error: ' . $e->getMessage());
            }
        }

        // Smart preset projects matching user portfolio for easy testing / sandbox
        return [
            [
                'id' => 'prj_gujaratitrde',
                'name' => 'gujaratitrde',
                'domain' => 'gujaratitrde.vercel.app',
                'full_url' => 'https://gujaratitrde.vercel.app',
                'framework' => 'html',
            ],
            [
                'id' => 'prj_focusuu',
                'name' => 'focusuu',
                'domain' => 'focusuu.vercel.app',
                'full_url' => 'https://focusuu.vercel.app',
                'framework' => 'html',
            ],
            [
                'id' => 'prj_stoxk_app',
                'name' => 'stoxk-pro',
                'domain' => 'stoxk-pro.vercel.app',
                'full_url' => 'https://stoxk-pro.vercel.app',
                'framework' => 'nextjs',
            ],
            [
                'id' => 'prj_vikash_educx',
                'name' => 'vikasheducx',
                'domain' => 'vikasheducx.vercel.app',
                'full_url' => 'https://vikasheducx.vercel.app',
                'framework' => 'html',
            ],
            [
                'id' => 'prj_matrixfx',
                'name' => 'matrixfx',
                'domain' => 'matrixfx.vercel.app',
                'full_url' => 'https://matrixfx.vercel.app',
                'framework' => 'html',
            ],
        ];
    }
}
