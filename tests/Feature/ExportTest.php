<?php

use Illuminate\Support\Facades\DB;

test('it can export to excel', function () {
    $this->get('/users')->assertOk();
    $batchId = $this->getAjax('/users?action=exportQueue')->getContent();

    $this->assertTrue(DB::table('job_batches')->where('id', $batchId)->exists());
});
