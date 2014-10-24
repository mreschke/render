<?php namespace Mreschke\Render;

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
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		\Lifecycle::add(__FILE__.' - '.__FUNCTION__);

		// Bind to IoC
		$sql = \App::make("Mreschke\Dbal\\".studly_case(\Config::get('my.db.default_type', 'Mysql')));
		$this->app->bind('Mreschke\Render\Render', function() use($sql) {
			return new Render($sql);
		});
	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		\Lifecycle::add(__FILE__.' - '.__FUNCTION__);
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
