<?php

namespace PhpCalendar;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Framework extends HttpKernel\HttpKernel
{
	public $generator;

	public function __construct($routes)
	{
		$context = new RequestContext();
		$matcher = new UrlMatcher($routes, $context);
		$resolver = new HttpKernel\Controller\ControllerResolver();

		$dispatcher = new EventDispatcher();
		$dispatcher->addSubscriber(new HttpKernel\EventListener\RouterListener($matcher));
		$dispatcher->addSubscriber(new HttpKernel\EventListener\ResponseListener('UTF-8'));

		$generator = new UrlGenerator($routes, $context);

		parent::__construct($dispatcher, $resolver);
	}
}
