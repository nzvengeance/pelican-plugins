<?php

declare(strict_types=1);

namespace Starter\ServerDocumentation\Providers;

use App\Filament\Admin\Resources\Servers\ServerResource;
use App\Models\Role;
use App\Models\Server;
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
        // admin Role editor. This is the only way document management is granted;
        // Root Admins are allowed everything by the panel itself.
        Role::registerCustomDefaultPermissions(Document::RESOURCE_NAME);
        Role::registerCustomModelIcon(Document::RESOURCE_NAME, 'tabler-file-text');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../resources/css' => public_path('plugins/server-documentation/css'),
            __DIR__ . '/../../resources/js' => public_path('plugins/server-documentation/js'),
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
            $sourcePath = __DIR__ . '/../../resources/' . $asset;
            $publicPath = public_path('plugins/server-documentation/' . $asset);

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
}
