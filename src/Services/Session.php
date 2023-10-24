<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */


namespace Devlee\WakerORM\Services;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Waker-ORM
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

	/**
	 * Set a Toast message
	 */
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

	/**
	 * Get Toast message
	 */
	public function getToast($key)
	{
		$toast = $_SESSION[self::TOAST_KEY][$key] ?? false;
		$this->removeToastMessages();
		return $toast;
		// unset($_SESSION[self::TOAST_KEY][$key]);
	}

	/**
	 * Start a session
	 */
	public function set(string $key, mixed  $value)
	{
		$_SESSION[$key] = $value;
	}

	/**
	 * Get a session token
	 */
	public function get(string $key)
	{
		return $_SESSION[$key] ?? false;
	}

	/**
	 * Clear or remove a session token
	 */
	public function clear(string  $key)
	{
		unset($_SESSION[$key]);
	}

	/**
	 * Remove session token
	 */
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
