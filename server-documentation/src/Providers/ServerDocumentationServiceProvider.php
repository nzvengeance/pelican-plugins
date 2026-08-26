<?php

declare(strict_types=1);

namespace Starter\ServerDocumentation\Providers;

use App\Filament\Admin\Resources\Servers\ServerResource;
use App\Models\Role;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Starter\ServerDocumentation\Filament\Admin\RelationManagers\DocumentsRelationManager;
use Starter\ServerDocumentation\Models\Document;
use Starter\ServerDocumentation\Services\DocumentService;
use Starter\ServerDocumentation\Services\MarkdownConverter;
use Starter\ServerDocumentation\Services\VariableProcessor;

/**
 * Pelican's plugin loader (PluginService::loadPlugins) registers this plugin's
 * config, translations, views, migrations and service providers by itself, and
 * Laravel resolves the policies by naming convention. Only what the panel cannot
 * infer on its own lives here.
 */
class ServerDocumentationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentService::class);
        $this->app->singleton(MarkdownConverter::class);
        $this->app->singleton(VariableProcessor::class);

        // Hooks into core resources must run in register(): Filament collects the
        // Livewire components of every resource while the panel is being registered,
        // which happens before any provider's boot().
        ServerResource::registerCustomRelations(DocumentsRelationManager::class);

        // Adds a "document" group (viewList / view / create / update / delete) to the
        // admin Role editor so document access can be granted per role.
        Role::registerCustomDefaultPermissions(Document::RESOURCE_NAME);
        Role::registerCustomModelIcon(Document::RESOURCE_NAME, 'tabler-file-text');
    }

    public function boot(): void
    {
        $this->registerDocumentPermissionFallback();

        $this->publishes([
            __DIR__.'/../../resources/css' => public_path('plugins/server-documentation/css'),
            __DIR__.'/../../resources/js' => public_path('plugins/server-documentation/js'),
        ], 'server-documentation-assets');

        $this->autoPublishAssets();

        Server::resolveRelationUsing('documents', function (Server $server) {
            return $server->belongsToMany(
                Document::class,
                'document_server',
                'server_id',
                'document_id'
            )->withPivot('sort_order')->withTimestamps()->orderByPivot('sort_order');
        });
    }

    /**
     * Copy CSS and JS assets into the public directory, refreshing them when the
     * bundled source is newer than the published copy.
     */
    protected function autoPublishAssets(): void
    {
        $assets = [
            'css/document-content.css',
            'js/highlight.min.js',
            'js/highlight-github-dark.min.css',
        ];

        foreach ($assets as $asset) {
            $sourcePath = __DIR__.'/../../resources/'.$asset;
            $publicPath = public_path('plugins/server-documentation/'.$asset);

            if (! file_exists($sourcePath)) {
                continue;
            }

            $publicDir = dirname($publicPath);
            if (! is_dir($publicDir)) {
                mkdir($publicDir, 0755, true);
            }

            if (! file_exists($publicPath) || filemtime($sourcePath) > filemtime($publicPath)) {
                copy($sourcePath, $publicPath);
            }
        }
    }

    /**
     * Fallback for document permissions that were not granted explicitly.
     *
     * A permission granted through the Role editor is resolved first (Pelican's
     * permission layer answers in a Gate::before hook). When the user has no such
     * grant, this gate decides:
     * - Root Admins: always allowed
     * - config('server-documentation.explicit_permissions') = true: denied
     * - otherwise: inherited from the user's 'update server' / 'create server' permissions
     */
    protected function registerDocumentPermissionFallback(): void
    {
        $permissions = [
            'viewList document',
            'view document',
            'create document',
            'update document',
            'delete document',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, function (User $user) {
                if ($user->isRootAdmin()) {
                    return true;
                }

                if (config('server-documentation.explicit_permissions', false)) {
                    return false;
                }

                return $user->can('update server') || $user->can('create server');
            });
        }
    }
}
