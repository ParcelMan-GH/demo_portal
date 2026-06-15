<?php

test('api guests receive json unauthenticated response without json accept header', function () {
    $this->post('/api/v1/driver/start-deliveries')
        ->assertUnauthorized()
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
});
