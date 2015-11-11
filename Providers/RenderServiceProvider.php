<?php namespace Mreschke\Render\Providers;

use Mreschke\Render\Render;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

/**
 * Provide Render services.
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class RenderServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		//
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// Register Facades
		$facade = AliasLoader::getInstance();
		$facade->alias('Render', 'Mreschke\Render\Facades\Render');

		// Main Binding
		$sql = $this->app->make("Mreschke\Dbal\\".studly_case(\Config::get('database.default', 'mysql')));
		$this->app->bind('Mreschke\Render\Render', function() use($sql) {
			return new Render($sql);
		});
		$this->app->alias('Mreschke\Render\Render', 'Mreschke\Render');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('Mreschke\Render\Render');
	}

}
