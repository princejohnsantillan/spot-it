<?php

test('the application returns a successful response', function () {
    $response = $this->get('/solo');

    $response->assertStatus(200);
});
