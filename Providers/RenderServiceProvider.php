<?php namespace Mreschke\Render\Providers;

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
		// Register Render Facades
		$loader = \Illuminate\Foundation\AliasLoader::getInstance();
		$loader->alias('Render', 'Mreschke\Render\Facades\Renbder');

		// Bind to IoC
		$sql = $this->app->make("Mreschke\Dbal\\".studly_case(\Config::get('database.default', 'mysql')));
		$this->app->bind('Mreschke\Render\Render', function() use($sql) {
			return new Render($sql);
		});
		$this->app->bind('Mreschke\Render', 'Mreschke\Render\Render');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('Mreschke\Render\Render', 'Mreschke\Render');
	}

}
