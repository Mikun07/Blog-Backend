<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_api_documentation_page_is_served_by_laravel(): void
    {
        $this->get('/docs/api')
            ->assertOk()
            ->assertSee('Blog Backend API');
    }

    public function test_openapi_document_is_served_as_json(): void
    {
        $this->getJson('/docs/api/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonPath('info.title', 'Blog Backend API')
            ->assertJsonPath('paths./auth/login.post.summary', 'Login')
            ->assertJsonPath('paths./admin/users/{user}.patch.summary', 'Update user')
            ->assertJsonPath('paths./admin/users/{user}/status.patch.summary', 'Update user status')
            ->assertJsonPath('components.securitySchemes.bearerAuth.type', 'http');
    }
}
