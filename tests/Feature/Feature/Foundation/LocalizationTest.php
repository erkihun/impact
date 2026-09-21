<?php

declare(strict_types=1);

it('renders the public site in English and Amharic', function (): void {
    $this->get('/en')
        ->assertOk()
        ->assertSee('Evidence for the decisions');

    $this->get('/am')
        ->assertOk()
        ->assertSee('ሃሳቦችን ወደ');
});

it('rejects unsupported locale prefixes', function (): void {
    $this->get('/fr/services')->assertNotFound();
});
