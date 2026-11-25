<?php

use App\Domain\Leads\LeadSubmission;

it('reads the ways people type a mobile number as the same 10 digits', function (string $typed) {
    expect(LeadSubmission::normalizePhone($typed))->toBe('0944555957');
})->with(['0944555957', '0944 555 957', '0944.555.957', '0944-555-957', ' 0944  555 957 ', '+84944555957', '+84 944 555 957', '+84.944.555.957', '84944555957', "0944\u{00A0}555\u{00A0}957"]);

it('refuses anything that is not one Vietnamese mobile number', function (string $typed) {
    expect(LeadSubmission::normalizePhone($typed))->toBeNull()
        ->and(LeadSubmission::isPhone($typed))->toBeFalse();
})->with(['', '123', '094455595', '09445559578', '0244555957', '0144555957', '0944abc957', '0944555957a', 'abc 0944555957', '(0944)555957', '0944_555_957',
    '+1 944 555 957', '+0944555957', '+840944555957', '0944555957 0912345678', '0944 555 957 - - - - - - - - - 1', "0944555957\n0912", '-0944555957', '0944555957-']);
