<?php

namespace JeffersonGoncalves\Privacy\Consent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Base of every suite's consent endpoint for front-end banners: POST {purpose, granted}. Guests are identified
 * by a cookie holding a random id. A suite sets `protected string $manager = ConsentManager::class;`.
 */
abstract class ConsentController
{
    /** @var class-string<ConsentManager> */
    protected string $manager;

    public function __invoke(Request $request): Response
    {
        /** @var ConsentManager $consents */
        $consents = app($this->manager);
        $config = $consents->config();
        $purposes = array_keys((array) config("{$config}.purposes", []));

        $data = $request->validate([
            'purpose' => ['required', 'string', 'max:100', ...($purposes === [] ? [] : [Rule::in($purposes)])],
            'granted' => ['required', 'boolean'],
        ]);

        $cookie = (string) config("{$config}.route.cookie", str_replace('-', '_', $config).'_id');
        $user = $request->user();
        $subject = $user instanceof Model ? $user : ($request->cookie($cookie) ?: (string) Str::uuid());
        $purpose = (string) $data['purpose'];

        $request->boolean('granted')
            ? $consents->grant($subject, $purpose, source: 'route')
            : $consents->revoke($subject, $purpose, source: 'route');

        $response = response()->noContent();

        if (is_string($subject)) {
            $response->withCookie(cookie()->forever($cookie, $subject));
        }

        return $response;
    }
}
