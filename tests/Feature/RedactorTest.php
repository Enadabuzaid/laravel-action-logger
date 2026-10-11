<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Feature;

use Enadstack\ActionLogger\Facades\ActionLogger;
use Enadstack\ActionLogger\Models\ActionLog;
use Enadstack\ActionLogger\Support\Redactor;
use Enadstack\ActionLogger\Tests\Support\Models\TestModel;
use Enadstack\ActionLogger\Tests\TestCase;

class RedactorTest extends TestCase
{
    /** @test */
    public function it_redacts_top_level_nested_and_list_keys_case_insensitively(): void
    {
        $r = new Redactor(['password', 'token_hash']);

        $out = $r->redact([
            'name' => 'A',
            'Password' => 'x',
            'user' => ['PASSWORD' => 'y', 'deep' => ['token_hash' => 'z', 'ok' => 1]],
            'list' => [['token_hash' => 'q'], ['n' => 2]],
        ]);

        $this->assertSame([
            'name' => 'A',
            'Password' => '[redacted]',
            'user' => ['PASSWORD' => '[redacted]', 'deep' => ['token_hash' => '[redacted]', 'ok' => 1]],
            'list' => [['token_hash' => '[redacted]'], ['n' => 2]],
        ], $out);
    }

    /** @test */
    public function non_array_values_and_null_are_untouched(): void
    {
        $r = new Redactor(['password']);

        $this->assertNull($r->redact(null));
        $this->assertSame(['a' => 1, 'b' => 'str', 'c' => null, 0 => 'zero'], $r->redact(['a' => 1, 'b' => 'str', 'c' => null, 0 => 'zero']));
    }

    /** @test */
    public function an_empty_key_list_leaves_values_unchanged(): void
    {
        $values = ['password' => 'x', 'n' => ['token' => 'y']];

        $this->assertSame($values, (new Redactor([]))->redact($values));
    }

    /** @test */
    public function secrets_are_never_stored(): void
    {
        $subject = TestModel::create(['name' => 'W', 'status' => 'active']);
        ActionLog::query()->delete();

        ActionLogger::log(
            $subject,
            null,
            'x.y',
            ['password' => 'old-secret'],
            ['password' => 'new-secret', 'nested' => ['two_factor_secret' => 'ABC']],
            ['token' => 'tok', 'info' => 'ok'],
        );

        $log = ActionLog::query()->first();
        $this->assertSame('[redacted]', $log->old_values['password']);
        $this->assertSame('[redacted]', $log->new_values['password']);
        $this->assertSame('[redacted]', $log->new_values['nested']['two_factor_secret']);
        $this->assertSame(['token' => '[redacted]', 'info' => 'ok'], $log->meta);

        $raw = json_encode(ActionLog::query()->toBase()->get()->all());
        foreach (['old-secret', 'new-secret', 'ABC', 'tok"'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw);
        }
    }

    /** @test */
    public function the_redactor_is_a_container_singleton_bound_from_config(): void
    {
        $this->assertSame(app(Redactor::class), app(Redactor::class));
        $this->assertSame('[redacted]', app(Redactor::class)->redact(['api_key' => 'k'])['api_key']);
    }
}
