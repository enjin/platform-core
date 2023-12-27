<?php

namespace Enjin\Platform\Providers;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;

class GitHubServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $githubHttp = new Factory();
        $githubHttp = $githubHttp->baseUrl('https://api.github.com/');

        if ($githubToken = config('enjin-platform.github_token')) {
            $githubHttp = $githubHttp->withOptions([
                'headers' => [
                    'Authorization' => 'Bearer ' . $githubToken,
                    'Accept' => 'application/vnd.github.v3+json',
                ],
            ]);
        }

        $this->app->instance('github.http', $githubHttp);
    }
}
