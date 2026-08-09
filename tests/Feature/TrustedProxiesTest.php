<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Tests\TestCase;

/**
 * Serving the app behind a tunnel.
 *
 * `herd share`, ngrok and Expose all terminate TLS at the proxy and forward to
 * the app over plain HTTP. Unless that proxy is trusted, Laravel believes the
 * request was insecure and builds every absolute URL as http:// — so on an
 * https:// tunnel the browser blocks the stylesheet and Livewire's JavaScript
 * as mixed content, and the panel loads unstyled and inert.
 *
 * The wiring is easy to get subtly wrong: the obvious home for it,
 * bootstrap/app.php's withMiddleware() closure, runs before Laravel parses the
 * .env file, so env() there is always null and the setting silently never
 * applies. It lives in AppServiceProvider::boot() instead. These tests pin down
 * that it works from there, and that it stays off unless asked for.
 */
class TrustedProxiesTest extends TestCase
{
    protected function tearDown(): void
    {
        TrustProxies::flushState();

        parent::tearDown();
    }

    /**
     * Boot a fresh application with the given config value, the way a real
     * request would, and report the scheme Laravel decides on.
     */
    private function schemeSeenBehindProxy(?string $trusted): string
    {
        TrustProxies::flushState();

        config(['proxies.trusted' => $trusted]);

        // Re-run the provider's boot so the config change is picked up.
        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $middleware = new TrustProxies();
        $request = \Illuminate\Http\Request::create('http://glof.test/login', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'glof.test',
        ]);

        $scheme = 'unset';
        $middleware->handle($request, function (\Illuminate\Http\Request $r) use (&$scheme) {
            $scheme = $r->getScheme();

            return response('ok');
        });

        return $scheme;
    }

    public function test_a_forwarded_https_request_is_treated_as_secure_when_the_proxy_is_trusted(): void
    {
        $this->assertSame('https', $this->schemeSeenBehindProxy('*'));
    }

    public function test_forwarded_headers_are_ignored_when_no_proxy_is_trusted(): void
    {
        // The default. An untrusted forwarded header can be spoofed, so nothing
        // is believed until a proxy is named.
        $this->assertSame('http', $this->schemeSeenBehindProxy(null));
    }

    public function test_an_empty_setting_is_treated_as_no_proxy(): void
    {
        $this->assertSame('http', $this->schemeSeenBehindProxy(''));
    }

    public function test_a_specific_proxy_address_can_be_trusted(): void
    {
        $this->assertSame('https', $this->schemeSeenBehindProxy('10.0.0.1'));
    }

    public function test_a_proxy_that_is_not_in_the_trusted_list_is_ignored(): void
    {
        $this->assertSame('http', $this->schemeSeenBehindProxy('192.168.50.1'));
    }

    public function test_a_comma_separated_list_is_accepted(): void
    {
        $this->assertSame('https', $this->schemeSeenBehindProxy('192.168.50.1, 10.0.0.1'));
    }

    /**
     * The setting must be absent by default, so that pulling this change cannot
     * quietly widen what an existing deployment trusts.
     */
    public function test_nothing_is_trusted_out_of_the_box(): void
    {
        $this->assertNull(config('proxies.trusted'));
    }
}
