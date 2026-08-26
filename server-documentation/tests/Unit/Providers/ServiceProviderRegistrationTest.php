<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Servers\ServerResource;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Starter\ServerDocumentation\Filament\Admin\RelationManagers\DocumentsRelationManager;
use Starter\ServerDocumentation\Models\Document;
use Starter\ServerDocumentation\Models\DocumentVersion;
use Starter\ServerDocumentation\Policies\DocumentPolicy;
use Starter\ServerDocumentation\Policies\DocumentVersionPolicy;

describe('ServerDocumentationServiceProvider', function () {
    it('attaches the documents relation manager to ServerResource', function () {
        expect(ServerResource::$customRelations)->toContain(DocumentsRelationManager::class);
    });

    it('registers document permissions with the Role editor', function () {
        expect(Role::$customDefaultPermissions)->toContain('document');
        expect(Role::$customModelIcons)->toHaveKey('document');
    });

    it('relies on Laravel policy discovery instead of Gate::policy', function () {
        expect(Gate::getPolicyFor(Document::class))->toBeInstanceOf(DocumentPolicy::class);
        expect(Gate::getPolicyFor(DocumentVersion::class))->toBeInstanceOf(DocumentVersionPolicy::class);
    });

    it('defines the permission fallback gates', function () {
        foreach (['viewList', 'view', 'create', 'update', 'delete'] as $prefix) {
            expect(Gate::has("{$prefix} document"))->toBeTrue();
        }
    });

    it('uses config, translations and views registered by the host', function () {
        expect(config('server-documentation.cache_ttl'))->not->toBeNull();
        expect(trans('server-documentation::strings.navigation.documents'))
            ->not->toBe('server-documentation::strings.navigation.documents');
        expect(view()->exists('server-documentation::filament.partials.content-preview'))->toBeTrue();
    });
});
