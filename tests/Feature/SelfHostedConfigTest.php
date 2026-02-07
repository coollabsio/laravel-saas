<?php

it('defaults to self-hosted mode', function () {
    expect(config('saas.self_hosted'))->toBeTrue();
});

it('can be set to hosted mode', function () {
    config(['saas.self_hosted' => false]);

    expect(config('saas.self_hosted'))->toBeFalse();
});
