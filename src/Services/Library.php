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

class Library
{

  // CUSTOM METHODS
  public static function generateToken(int $length = 9): string
  {
    $token =  bin2hex(random_bytes($length));
    return $token;
    // return strtoupper($token);
  }
  // Create a profile image banner
  public static function generateRGB()
  {
    return "rgb(" . rand(0, 255) . "," . rand(0, 255) . "," . rand(0, 255) . ")";
  }
  // Create a profile image banner
  public static function generateImage($text = false)
  {
    $color = "rgb(" . rand(0, 255) . "," . rand(0, 255) . "," . rand(0, 255) . ")";

    $displayText = $text ? '<div class="alt-profile clr-bg"><h3>' . $text . '</h3></div>' : '<div class="alt clr-bg"><h4>IMAGE</h4><span>' . $color . '</span></div>';
    $image = '<div class="box-image flex" style="--color: ' . $color . '">' . $displayText . '</div>';
    return $image;
  }
}
