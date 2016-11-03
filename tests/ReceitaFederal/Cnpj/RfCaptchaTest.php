<?php

class RfCaptchaTest extends \PHPUnit_Framework_TestCase
{
	public function testGetCaptcha()
	{
		$captcha = new \ReceitaFederal\Cnpj\RfCaptcha(dirname(__FILE__));
		$image = $captcha->getCaptcha();
		$this->assertNotNull($image);
	}
}