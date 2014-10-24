<?php namespace Mreschke\Render\Facades;

/**
 * Provides the facade for Mreschke\Render\Render.
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class Render extends \Illuminate\Support\Facades\Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'Mreschke\Render\Render'; }

}
