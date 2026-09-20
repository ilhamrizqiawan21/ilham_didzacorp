<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests verify Laravel responses and business rules; they do
        // not need a compiled frontend manifest to render Inertia pages.
        $this->withoutVite();
    }
}
