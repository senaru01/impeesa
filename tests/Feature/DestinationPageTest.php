<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_rinjani_page_shows_partner_info_and_links_to_partner_site(): void
    {
        $response = $this->get(route('destinasi', 'rinjani'));

        $response->assertOk();
        $response->assertSee('Gunung Rinjani');
        $response->assertSee('Iza Rinjani');
        $response->assertSee('Sembalun');
        $response->assertSee('href="https://izarinjani.com/"', false);
        $response->assertSee('https://wa.me/6287732525591', false);
    }

    public function test_destination_without_partner_returns_not_found(): void
    {
        $this->get(route('destinasi', 'semeru'))->assertNotFound();
    }

    public function test_unknown_destination_returns_not_found(): void
    {
        $this->get(route('destinasi', 'tidak-ada'))->assertNotFound();
    }

    public function test_landing_rinjani_card_links_to_detail_page(): void
    {
        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('data-href="'.route('destinasi', 'rinjani').'"', false);
        $response->assertDontSee('data-href="'.route('destinasi', 'semeru').'"', false);
    }

    public function test_landing_rinjani_recommendation_links_to_detail_page(): void
    {
        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('href="'.route('destinasi', 'rinjani').'" class="rec__link"', false);
        $response->assertDontSee('href="'.route('destinasi', 'semeru').'" class="rec__link"', false);
    }
}
