<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */


namespace Devlee\PHPMVCCore;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\Session
 */


class Session
{
	protected const TOAST_KEY = 'toast_messages';

	public function __construct()
	{
		// session_start();
		$toastMessages = $_SESSION[self::TOAST_KEY] ?? [];
		foreach ($toastMessages as $key => &$toastMessage) {
			$toastMessage['remove'] = true;
		}
		$_SESSION[self::TOAST_KEY] = $toastMessages;
	}

	public function setToast($key = "toast", $body, ?string $redirect_url = null)
	{
		$_SESSION[self::TOAST_KEY][$key] = [
			'message' => $body,
			'remove' => false,
		];

		if ($redirect_url) {
			header("Location: $redirect_url");
			exit;
		}
	}

	public function getToast($key)
	{
		$toast = $_SESSION[self::TOAST_KEY][$key] ?? false;
		$this->removeToastMessages();
		return $toast;
		// unset($_SESSION[self::TOAST_KEY][$key]);
	}

	public function set($key, $value)
	{
		$_SESSION[$key] = $value;
	}

	public function get($key)
	{
		return $_SESSION[$key] ?? false;
	}

	public function clear($key)
	{
		unset($_SESSION[$key]);
	}

	private function removeToastMessages()
	{
		$toastMessages = $_SESSION[self::TOAST_KEY] ?? [];
		foreach ($toastMessages as $key => $toastMessage) {
			if ($toastMessage['remove'] || $toastMessage['remove'] === 1) {
				unset($toastMessages[$key]);
			}
		}
		$_SESSION[self::TOAST_KEY] = $toastMessages;
	}
}
